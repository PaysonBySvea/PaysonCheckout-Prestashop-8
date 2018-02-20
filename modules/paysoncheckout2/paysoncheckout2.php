<?php
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PaysonCheckout2 extends PaymentModule
{
    public $moduleVersion;

    public function __construct()
    {
        $this->name = 'paysoncheckout2';
        $this->tab = 'payments_gateways';
        $this->version = '2.0.1';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->author = 'Payson AB';

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Payson Checkout 2.0');
        $this->description = $this->l('Pay with Payson via invoice, card, internet bank, partial payment or sms.');
        
        $this->moduleVersion = sprintf('payson_checkout2_prestashop|%s|%s', $this->version, _PS_VERSION_);

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }
        
        if (!defined('_PCO_LOG_')) {
            define('_PCO_LOG_', Configuration::get('PAYSONCHECKOUT2_LOG'));
        }
    }

    public function install() {
        if (parent::install() == false 
            || !$this->registerHook('paymentOptions') 
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('actionOrderStatusUpdate')
            ) {
            return false;
        }
        
        // Set some defaults
        Configuration::updateValue('PAYSONCHECKOUT2_MODULE_ENABLED', 1);
        Configuration::updateValue('PAYSONCHECKOUT2_REQUIRE_PHONE', 1);
        Configuration::updateValue('PAYSONCHECKOUT2_SHOW_PHONE', 1);
        Configuration::updateValue('PAYSONCHECKOUT2_ONE_PAGE', 1);
        Configuration::updateValue('PAYSONCHECKOUT2_MODE', 1);
        Configuration::updateValue('PAYSONCHECKOUT2_TEMPLATE', 'one_page_l');
        Configuration::updateValue('PAYSONCHECKOUT2_SHOW_CONFIRMATION', 1);
        
        $this->createPaysonOrderTable();

        $orderStates = OrderState::getOrderStates(Configuration::get('PS_LANG_DEFAULT'));
        $name = $this->l('Betald med Payson Checkout 2.0');
        $config_name = 'PAYSONCHECKOUT2_ORDER_STATE_PAID';
        $this->createPaysonOrderStates($name, $orderStates, $config_name, true);
        
        return true;
    }
    
    public function uninstall() {
       if (parent::uninstall() == false ||
            Configuration::deleteByName('PAYSONCHECKOUT2_AGENTID') == false ||
            Configuration::deleteByName('PAYSONCHECKOUT2_APIKEY') == false ||
            Configuration::deleteByName('PAYSONCHECKOUT2_MODE') == false ||
            Configuration::deleteByName('PAYSONCHECKOUT2_SHOW_CONFIRMATION') == false ||
            Configuration::deleteByName('PAYSONCHECKOUT2_LOG') == false ||
            Configuration::deleteByName('PAYSONCHECKOUT2_ONE_PAGE') == false ||
            Configuration::deleteByName('PAYSONCHECKOUT2_VERIFICATION') == false ||
            Configuration::deleteByName('PAYSONCHECKOUT2_COLOR_SCHEME') == false ||
            Configuration::deleteByName('PAYSONCHECKOUT2_REQUIRE_PHONE') == false ||
            Configuration::deleteByName('PAYSONCHECKOUT2_MODULE_ENABLED') == false ||
            Configuration::deleteByName('PAYSON_ORDER_SHIPPED_STATE') == false ||
            Configuration::deleteByName('PAYSON_ORDER_CANCEL_STATE') == false ||
            Configuration::deleteByName('PAYSONCHECKOUT2_TEMPLATE') == false ||
            Configuration::deleteByName('PAYSONCHECKOUT2_MODULE_ENABLED') == false ||
            Configuration::deleteByName('PAYSONCHECKOUT2_TESTAGENTID') == false ||
            Configuration::deleteByName('PAYSONCHECKOUT2_TESTAPIKEY') == false ||
            Configuration::deleteByName('PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS') == false
           ) {
           return false;
       }
       
       return true;
    }
    
    public function hookPaymentOptions($params) {
        if (!$this->active || (int) Configuration::get('PAYSONCHECKOUT2_MODULE_ENABLED') == 0 || (int)Configuration::get('PAYSONCHECKOUT2_ONE_PAGE') == 1) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $payment_options = [$this->getIframePaymentOption()];

        return $payment_options;
    }

    public function checkCurrency($cart) {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }
    
    public function checkCurrencyName($cartCurrency, $callPaysonApi, $paysonCheckoutId) {
        $checkout = $callPaysonApi->GetCheckout($paysonCheckoutId);

        if(strtoupper($cartCurrency) == strtoupper($checkout->payData->currency)){
            return true;
        }else{
            return false;
        }
    }

     public function getContent() {
        $saved = false;
        $errors = '';

        if (Tools::isSubmit('btnSettingsSubmit')) {
            Configuration::updateValue('PAYSONCHECKOUT2_AGENTID', Tools::getValue('PAYSONCHECKOUT2_AGENTID'));
            Configuration::updateValue('PAYSONCHECKOUT2_APIKEY', Tools::getValue('PAYSONCHECKOUT2_APIKEY'));
            Configuration::updateValue('PAYSONCHECKOUT2_MODE', (int) Tools::getValue('PAYSONCHECKOUT2_MODE'));
            Configuration::updateValue('PAYSONCHECKOUT2_SHOW_CONFIRMATION', 1);
            Configuration::updateValue('PAYSONCHECKOUT2_LOG', (int) Tools::getValue('PAYSONCHECKOUT2_LOG'));
            Configuration::updateValue('PAYSONCHECKOUT2_ONE_PAGE', 1);
            Configuration::updateValue('PAYSONCHECKOUT2_VERIFICATION', (int) Tools::getValue('PAYSONCHECKOUT2_VERIFICATION'));
            Configuration::updateValue('PAYSONCHECKOUT2_COLOR_SCHEME', Tools::getValue('PAYSONCHECKOUT2_COLOR_SCHEME'));
            Configuration::updateValue('PAYSONCHECKOUT2_MODULE_ENABLED',1);
            Configuration::updateValue('PAYSON_ORDER_SHIPPED_STATE', (int) Tools::getValue('PAYSON_ORDER_SHIPPED_STATE'));
            Configuration::updateValue('PAYSON_ORDER_CANCEL_STATE', (int) Tools::getValue('PAYSON_ORDER_CANCEL_STATE'));
            Configuration::updateValue('PAYSONCHECKOUT2_TEMPLATE', Tools::getValue('PAYSONCHECKOUT2_TEMPLATE'));
            Configuration::updateValue('PAYSONCHECKOUT2_REQUIRE_PHONE', 1);
            //Configuration::updateValue('PAYSONCHECKOUT2_SHOW_PHONE', (int) Tools::getValue('PAYSONCHECKOUT2_SHOW_PHONE'));
            Configuration::updateValue('PAYSONCHECKOUT2_TESTAGENTID', (int) Tools::getValue('PAYSONCHECKOUT2_TESTAGENTID'));
            Configuration::updateValue('PAYSONCHECKOUT2_TESTAPIKEY', Tools::getValue('PAYSONCHECKOUT2_TESTAPIKEY'));
            Configuration::updateValue('PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS', Tools::getValue('PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS'));
            $saved = true;
        }

        $this->context->smarty->assign(array(
            'errorMSG' => $errors,
            'isSaved' => $saved,
            'commonform' => $this->createSettingsForm(),
        ));
        
        return $this->display(__FILE__, 'views/templates/admin/payson_admin.tpl');
    }
    
    public function createSettingsForm() {
        $orderStates = OrderState::getOrderStates((int) $this->context->cookie->id_lang);
        $orderStates[] = array('id_order_state' => '-1', 'name' => $this->l('Deactivated'));
        
        $fields_form = array();
        $fields_form[0]['form'] = array(
                'legend' => array(
                    'title' => '',
                    'icon' => '',
                  ),
                'input' => array(
//                array(
//                    'type' => 'switch',
//                    'label' => $this->l('Enabled'),
//                    'name' => 'PAYSONCHECKOUT2_MODULE_ENABLED',
//                    'is_bool' => true,
//                    'values' => array(
//                        array(
//                            'id' => 'enabled_on',
//                            'value' => 1,
//                            'label' => $this->l('Yes'), ),
//                        array(
//                            'id' => 'enabled_off',
//                            'value' => 0,
//                            'label' => $this->l('No'), ),
//                    ),
//                    'desc' => $this->l('Enable Payson Checkout 2.0'),
//                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Test mode'),
                    'name' => 'PAYSONCHECKOUT2_MODE',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'testmode_on',
                            'value' => 1,
                            'label' => $this->l('Yes'), ),
                        array(
                            'id' => 'testmode_off',
                            'value' => 0,
                            'label' => $this->l('No'), ),
                    ),
                    'desc' => $this->l('Verify your installation in test mode before going live. In test mode no Agent ID or API-key is required'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Agent ID'),
                    'name' => 'PAYSONCHECKOUT2_AGENTID',
                    'class' => 'fixed-width-lg',
                    'required' => false,
                    'desc' => $this->l('Enter your Agent ID for Payson Checkout 2.0'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('API-key'),
                    'name' => 'PAYSONCHECKOUT2_APIKEY',
                    'class' => 'fixed-width-lg',
                    'required' => false,
                    'desc' => $this->l('Enter your API-key for Payson Checkout 2.0'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('TestAgent ID'),
                    'name' => 'PAYSONCHECKOUT2_TESTAGENTID',
                    'class' => 'fixed-width-lg',
                    'required' => false,
                    'desc' => $this->l('Enter your TestAgent ID for Payson Checkout 2.0'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('TestAgent API-key'),
                    'name' => 'PAYSONCHECKOUT2_TESTAPIKEY',
                    'class' => 'fixed-width-lg',
                    'required' => false,
                    'desc' => $this->l('Enter your TestAgent API-key for Payson Checkout 2.0'),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Canceled order status'),
                    'name' => 'PAYSON_ORDER_CANCEL_STATE',
                    'desc' => $this->l('Order status Canceled will be sent to Payson when this order status is set.'),
                    'options' => array(
                        'query' => $orderStates,
                        'id' => 'id_order_state',
                        'name' => 'name',
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Shipped order status'),
                    'name' => 'PAYSON_ORDER_SHIPPED_STATE',
                    'desc' => $this->l('Order status Shipped will be sent to Payson when this order status is set.'),
                    'options' => array(
                        'query' => $orderStates,
                        'id' => 'id_order_state',
                        'name' => 'name',
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Color scheme'),
                    'name' => 'PAYSONCHECKOUT2_COLOR_SCHEME',
                    'desc' => $this->l('Payment window color scheme'),
                    'options' => array(
                        'query' => array(
                        array(
                            'value' => 'gray',
                            'label' => $this->l('White form on gray background (default)'), ),
                        array(
                            'value' => 'blue',
                            'label' => $this->l('Blue form on white background'), ), 
                        array(
                            'value' => 'white',
                            'label' => $this->l('White form on white background'), ),
                        array(
                            'value' => 'GrayTextLogos',
                            'label' => $this->l('White form on gray background with text bank logotypes'), ),
                        array(
                            'value' => 'BlueTextLogos',
                            'label' => $this->l('Blue form on white background with text bank logotypes'), ),
                        array(
                            'value' => 'WhiteTextLogos',
                            'label' => $this->l('White form on white background with text bank logotypes'), ),
                        array(
                            'value' => 'GrayNoFooter',
                            'label' => $this->l('Gray form on white background with no bank logotypes and no footer'), ),
                        array(
                            'value' => 'BlueNoFooter',
                            'label' => $this->l('Blue form on white background with no bank logotypes and no footer'), ),
                        array(
                            'value' => 'WhiteNoFooter',
                            'label' => $this->l('White form on white background with no bank logotypes and no footer'), ),
                    ),
                        
                        'id' => 'value',
                        'name' => 'label',
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Checkout template'),
                    'name' => 'PAYSONCHECKOUT2_TEMPLATE',
                    'desc' => $this->l('Checkout layout template.'),
                    'options' => array(
                        'query' => array(array('id_option' => 'one_page_l', 'name' => $this->l('Left aligned')), array('id_option' => 'one_page_r', 'name' => $this->l('Right aligned'))),
                        'id' => 'id_option',
                        'name' => 'name',
                    ),
                ),
//                array(
//                    'type' => 'switch',
//                    'label' => $this->l('One Page Checkout'),
//                    'name' => 'PAYSONCHECKOUT2_ONE_PAGE',
//                    'is_bool' => true,
//                    'values' => array(
//                        array(
//                            'id' => 'op_on',
//                            'value' => 1,
//                            'label' => $this->l('Yes'), ),
//                        array(
//                            'id' => 'op_off',
//                            'value' => 0,
//                            'label' => $this->l('No'), ),
//                    ),
//                    'desc' => $this->l('Select Yes to show the payment window on the checkout page'),
//                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('BankID'),
                    'name' => 'PAYSONCHECKOUT2_VERIFICATION',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'veri_on',
                            'value' => 1,
                            'label' => $this->l('Yes'), ),
                        array(
                            'id' => 'veri_off',
                            'value' => 0,
                            'label' => $this->l('No'), ),
                    ),
                    'desc' => $this->l('Select Yes to force customer identification by BankID'),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Show other payment methods'),
                    'name' => 'PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'link_pay_on',
                            'value' => 1,
                            'label' => $this->l('Yes'), ),
                        array(
                            'id' => 'link_pay_off',
                            'value' => 0,
                            'label' => $this->l('No'), ),
                    ),
                    'desc' => $this->l('Select Yes to show a link to other payment methods'),
                ),
//                array(
//                    'type' => 'switch',
//                    'label' => $this->l('Payson order confirmation page for all customers'),
//                    'name' => 'PAYSONCHECKOUT2_SHOW_CONFIRMATION',
//                    'is_bool' => true,
//                    'values' => array(
//                        array(
//                            'id' => 'conf_on',
//                            'value' => 1,
//                            'label' => $this->l('Yes'), ),
//                        array(
//                            'id' => 'conf_off',
//                            'value' => 0,
//                            'label' => $this->l('No'), ),
//                    ),
//                    'desc' => $this->l('Select No to show store default confirmation page for logged in customers'),
//                ),
//                array(
//                    'type' => 'switch',
//                    'label' => $this->l('Show phone'),
//                    'name' => 'PAYSONCHECKOUT2_SHOW_PHONE',
//                    'is_bool' => true,
//                    'values' => array(
//                        array(
//                            'id' => 'show_phone_on',
//                            'value' => 1,
//                            'label' => $this->l('Yes'), ),
//                        array(
//                            'id' => 'show_phone_off',
//                            'value' => 0,
//                            'label' => $this->l('No'), ),
//                    ),
//                    'desc' => $this->l('Show the phone field in the payment window'),
//                ),
//                array(
//                    'type' => 'switch',
//                    'label' => $this->l('Require phone'),
//                    'name' => 'PAYSONCHECKOUT2_REQUIRE_PHONE',
//                    'is_bool' => true,
//                    'values' => array(
//                        array(
//                            'id' => 'req_phone_on',
//                            'value' => 1,
//                            'label' => $this->l('Yes'), ),
//                        array(
//                            'id' => 'req_phone_off',
//                            'value' => 0,
//                            'label' => $this->l('No'), ),
//                    ),
//                    'desc' => $this->l('Require the customer to enter a phone number'),
//                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Log messages'),
                    'name' => 'PAYSONCHECKOUT2_LOG',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'log_on',
                            'value' => 1,
                            'label' => $this->l('Yes'), ),
                        array(
                            'id' => 'log_off',
                            'value' => 0,
                            'label' => $this->l('No'), ),
                    ),
                    'desc' => $this->l('Check to log messages from Payson Checkout 2.0'),
                ),

                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        if (Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')) {
            $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        } else {
            $helper->allow_employee_form_lang = 0;
        }
        
        $helper->submit_action = 'btnSettingsSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).
        '&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm($fields_form);
    }
    
    // Get values for module configuration
    public function getConfigFieldsValues() {
        return array(
           'PAYSONCHECKOUT2_MODE' => Tools::getValue('PAYSONCHECKOUT2_MODE', Configuration::get('PAYSONCHECKOUT2_MODE')),
           //'PAYSONCHECKOUT2_MODE' => Configuration::get('PAYSONCHECKOUT2_MODE', null, null, null, 1),
           'PAYSONCHECKOUT2_COLOR_SCHEME' => Tools::getValue('PAYSONCHECKOUT2_COLOR_SCHEME', Configuration::get('PAYSONCHECKOUT2_COLOR_SCHEME')),
           'PAYSONCHECKOUT2_AGENTID' => Tools::getValue('PAYSONCHECKOUT2_AGENTID',Configuration::get('PAYSONCHECKOUT2_AGENTID')),
           'PAYSONCHECKOUT2_APIKEY' => Tools::getValue('PAYSONCHECKOUT2_APIKEY',Configuration::get('PAYSONCHECKOUT2_APIKEY')),
           'PAYSONCHECKOUT2_SHOW_CONFIRMATION' => Tools::getValue('PAYSONCHECKOUT2_SHOW_CONFIRMATION',1),
           'PAYSONCHECKOUT2_ONE_PAGE' => Tools::getValue('PAYSONCHECKOUT2_ONE_PAGE', Configuration::get('PAYSONCHECKOUT2_ONE_PAGE')),
           'PAYSONCHECKOUT2_VERIFICATION' => Tools::getValue('PAYSONCHECKOUT2_VERIFICATION', Configuration::get('PAYSONCHECKOUT2_VERIFICATION')),
           'PAYSONCHECKOUT2_LOG' => Tools::getValue('PAYSONCHECKOUT2_LOG',Configuration::get('PAYSONCHECKOUT2_LOG')),
           'PAYSONCHECKOUT2_SHOW_PHONE' => Tools::getValue('PAYSONCHECKOUT2_SHOW_PHONE',Configuration::get('PAYSONCHECKOUT2_SHOW_PHONE')),
           'PAYSONCHECKOUT2_REQUIRE_PHONE' => Tools::getValue('PAYSONCHECKOUT2_REQUIRE_PHONE',Configuration::get('PAYSONCHECKOUT2_REQUIRE_PHONE')),
           'PAYSONCHECKOUT2_MODULE_ENABLED' => Tools::getValue('PAYSONCHECKOUT2_MODULE_ENABLED',Configuration::get('PAYSONCHECKOUT2_MODULE_ENABLED')),
           'PAYSON_ORDER_CANCEL_STATE' => Tools::getValue('PAYSON_ORDER_CANCEL_STATE',Configuration::get('PAYSON_ORDER_CANCEL_STATE')),
           'PAYSON_ORDER_SHIPPED_STATE' => Tools::getValue('PAYSON_ORDER_SHIPPED_STATE',Configuration::get('PAYSON_ORDER_SHIPPED_STATE')),
           'PAYSONCHECKOUT2_TEMPLATE' => Tools::getValue('PAYSONCHECKOUT2_TEMPLATE',Configuration::get('PAYSONCHECKOUT2_TEMPLATE')),
           'PAYSONCHECKOUT2_TESTAGENTID' => Tools::getValue('PAYSONCHECKOUT2_TESTAGENTID',Configuration::get('PAYSONCHECKOUT2_TESTAGENTID')),
           'PAYSONCHECKOUT2_TESTAPIKEY' => Tools::getValue('PAYSONCHECKOUT2_TESTAPIKEY',Configuration::get('PAYSONCHECKOUT2_TESTAPIKEY')),
           'PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS' => Tools::getValue('PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS',Configuration::get('PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS')),
        );
    }
    
    private function createPaysonOrderStates($name, $orderStates, $config_name, $paid) {
        $exists = false;
        foreach ($orderStates as $state) {
            if ($state['name'] == $name) {
                $exists = true;
                Configuration::updateValue($config_name, $state['id_order_state']);

                return;
            }
        }
        
        $names = array();
        if ($exists == false) {
            $orderstate = new OrderState();
            foreach (Language::getLanguages(false) as $language) {
                $names[$language['id_lang']] = $name;
            }
            $orderstate->name = $names;
            $orderstate->send_email = false;
            $orderstate->invoice = true;
            $orderstate->color = '#448102';
            $orderstate->unremovable = true;
            $orderstate->module_name = 'paysoncheckout2';
            $orderstate->delivery = false;
            $orderstate->shipped = false;
            $orderstate->deleted = false;
            $orderstate->hidden = true;
            $orderstate->logable = true;
            $orderstate->paid = $paid;
            $orderstate->save();
            
            Configuration::updateValue($config_name, $orderstate->id);

            if (!copy(dirname(__FILE__).'/views/images/payson_os.gif',_PS_IMG_DIR_.'os/'.$orderstate->id.'.gif')) {
                return false;
            }
        }
    }
    
    private function createPaysonOrderTable() {
       $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'payson_embedded_order` (
	        `payson_embedded_id` int(11) auto_increment,
                `cart_id` int(15) NOT NULL,
		`order_id` int(15) DEFAULT NULL,
                `checkout_id` varchar(40) DEFAULT NULL,
                `purchase_id` varchar(50) DEFAULT NULL,
		`payment_status` varchar(20) DEFAULT NULL,
		`added` datetime DEFAULT NULL,
		`updated` datetime DEFAULT NULL,
		`sender_email` varchar(50) DEFAULT NULL,
		`currency_code` varchar(5) DEFAULT NULL,
		`tracking_id`  varchar(100) DEFAULT NULL,
		`type` varchar(50) DEFAULT NULL,
		`shippingAddress_name` varchar(50) DEFAULT NULL,
		`shippingAddress_lastname` varchar(50) DEFAULT NULL,
		`shippingAddress_street_address` varchar(60) DEFAULT NULL,
		`shippingAddress_postal_code` varchar(20) DEFAULT NULL,
		`shippingAddress_city` varchar(60) DEFAULT NULL,
		`shippingAddress_country` varchar(60) DEFAULT NULL,
		PRIMARY KEY  (`payson_embedded_id`)
	        ) ENGINE='._MYSQL_ENGINE_;
       
       if (Db::getInstance()->execute($sql) == false) {
            return false;
        }
    }

    public function getIframePaymentOption()
    {
        if ((int)Configuration::get('PAYSONCHECKOUT2_MODULE_ENABLED') == 1) {
            $iframeOption = new PaymentOption();
            $iframeOption->setCallToActionText($this->l('Payson Checkout 2.0'))
                         ->setAction($this->context->link->getModuleLink($this->name, 'payment', array(), true))
                         ->setAdditionalInformation($this->context->smarty->fetch('module:paysoncheckout2/views/templates/front/payment_infos.tpl'));
                         //->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/logo.png'));

            return $iframeOption;
        }
    }

    public function canUpdate($paysonApi, $paysonCheckoutId) {
        $checkout = $paysonApi->GetCheckout($paysonCheckoutId);
        switch ($checkout->status){
            case 'created':
                return true;
                break;
            case 'readyToPay':
                return true;
                break;
            case 'processingPayment':
                return true;
                break;

            case 'readyToShip':
                return false;
                break;
            case 'formsFiled':
                return false;
                break;
            default: 
                return false;
        }
        return false;  
    }
    
    public function createPaysonCheckout($customer, $cart, $payson, $currency, $id_lang, $address) {
        $trackingId = time();
        
        $checkoutUri = $this->context->link->getModuleLink('paysoncheckout2', 'pconepage', array('trackingId' => $trackingId, 'id_cart' => $cart->id));
        $confirmationUri = $this->context->link->getModuleLink('paysoncheckout2', 'confirmation', array('trackingId' => $trackingId, 'id_cart' => $cart->id, 'call' => 'confirmation'));
        $notificationUri = $this->context->link->getModuleLink('paysoncheckout2', 'notifications', array('trackingId' => $trackingId, 'id_cart' => $cart->id, 'call' => 'notification'));
        $cms = new CMS((int) (Configuration::get('PS_CONDITIONS_CMS_ID')), (int) ($this->context->cookie->id_lang));
        $termsUri = $this->context->link->getCMSLink($cms, $cms->link_rewrite, true);
        $validationUri = NULL;
        if(_PCO_LOG_){Logger::addLog('REMOTE_ADDR: ' . print_r($_SERVER['REMOTE_ADDR'], true), 1, NULL, NULL, NULL, true);}
        if (!in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1','::1'))) {
            // Validation URI needs to be publicly accessible 
            $validationUri = $this->context->link->getModuleLink('paysoncheckout2', 'validation', array('trackingId' => $trackingId, 'id_cart' => $cart->id, 'call' => 'validation'));
            if(_PCO_LOG_){Logger::addLog('This is not localhost, use validation URI: ' . $validationUri, 1, NULL, NULL, NULL, true);}
        }
        
        $paysonMerchant = new PaysonEmbedded\Merchant($checkoutUri, $confirmationUri, $notificationUri, $termsUri, NULL, $payson->moduleVersion);
        $paysonMerchant->reference = $cart->id;
        $paysonMerchant->validationUri = $validationUri;
        
        if(_PCO_LOG_){Logger::addLog('PCO Merchant: ' . print_r($paysonMerchant, TRUE), 1, NULL, NULL, NULL, true);}
        
        $paysonOrder = new PaysonEmbedded\PayData($currency->iso_code);
        $paysonOrder->items = $this->orderItemsList($cart, $payson, $currency);
        
        if(_PCO_LOG_){Logger::addLog('PCO Order: ' . print_r($paysonOrder, TRUE), 1, NULL, NULL, NULL, true);}
        
        //$deliveryCountries = Carrier::getDeliveredCountries($this->context->language->id, true, true);
        $activeCountries = Country::getCountries($this->context->language->id, true, false, false);
        $moduleCountries = $this->getModuleAllowedCountries((int) $this->getPaysonModuleID(), (int) $this->context->shop->id);
        if(_PCO_LOG_){Logger::addLog('Language ID: ' . $this->context->language->id, 1, NULL, NULL, NULL, true);}
        if(_PCO_LOG_){Logger::addLog('Active countries: ' . print_r($activeCountries, true), 1, NULL, NULL, NULL, true);}
        if(_PCO_LOG_){Logger::addLog('Module countries: ' . print_r($moduleCountries, true), 1, NULL, NULL, NULL, true);}
        $allowedDeliveryCountries = array();
        foreach ($activeCountries as $country) {
            if (in_array($country['iso_code'], $moduleCountries)) {
                $allowedDeliveryCountries[] = $country['iso_code'];
            }
        }
        if(_PCO_LOG_){Logger::addLog('Valid countries: ' . print_r($allowedDeliveryCountries, true), 1, NULL, NULL, NULL, true);}
        
        if (!is_array($allowedDeliveryCountries) || count($allowedDeliveryCountries) < 1) {
            // NULL will show all countries
            $allowedDeliveryCountries = NULL;
        }
        
        $paysonGui = new PaysonEmbedded\Gui(
                $this->languagePayson(Language::getIsoById($id_lang)),
                Configuration::get('PAYSONCHECKOUT2_COLOR_SCHEME'),
                Configuration::get('PAYSONCHECKOUT2_VERIFICATION'),
                (int) Configuration::get('PAYSONCHECKOUT2_REQUIRE_PHONE'),
                $allowedDeliveryCountries,
                NULL
                );
        
        if(_PCO_LOG_){Logger::addLog('PCO GUI: ' . print_r($paysonGui, TRUE), 1, NULL, NULL, NULL, true);}
        
        if (Configuration::get('PAYSONCHECKOUT2_MODE') == 1) {
            // Create test customer
            $paysonCustomer = new PaysonEmbedded\Customer('Tess T', 'Persson', 'test@payson.se', 1111111, "4605092222", 'Stan', 'SE', '99999', '');
        } else {
            $paysonCustomer = $customer->email == Null ? Null :new PaysonEmbedded\Customer($customer->firstname, $customer->lastname, $customer->email, $address->phone, "", $address->city, Country::getIsoById($address->id_country), $address->postcode, $address->address1);
        }

        $checkout = new PaysonEmbedded\Checkout($paysonMerchant, $paysonOrder, $paysonGui, $paysonCustomer);

        return $checkout;
    }
    
    /**
     * Get ISO codes for modules country restrictions
     *
     * @param int $module_id Module ID
     * 
     * @param int $shop_id Shop ID
     *
     * @return array ISO country codes
     *
     */
    public function getModuleAllowedCountries($module_id = NULL, $shop_id = NULL) {
        $sql = "SELECT id_country FROM `" ._DB_PREFIX_ . "module_country`" . "WHERE id_shop=$shop_id AND id_module=$module_id";
        $moduleCountries = Db::getInstance()->ExecuteS($sql);
    
        foreach ($moduleCountries as $moduleCountry) {
            $isoCodes[] = Country::getIsoById($moduleCountry['id_country']);
        }
        
        return $isoCodes;
    }
    
    /**
     * Get Payson Checkout module ID
     *
     * @return module ID
     *
     */
    public function getPaysonModuleID() {
        $sql = "SELECT id_module FROM `" . _DB_PREFIX_ . "module`" . "WHERE name='paysoncheckout2'";
        $moduleId = Db::getInstance()->getValue($sql);
    
        return $moduleId;
    }
    
    public function updatePaysonCheckout($checkout, $customer, $cart, $payson, $address, $currency) {   
        if($customer->email != Null && $checkout->status !=  'readyToPay'){
            $checkout->customer->firstName = $customer->firstname ;
            $checkout->customer->lastName = $customer->lastname;
            $checkout->customer->email = $customer->email;
            $checkout->customer->phone = $address->phone;
            $checkout->customer->city = $address->city; 
            $checkout->customer->countryCode = Country::getIsoById($address->id_country);
            $checkout->customer->postalCode = $address->postcode;
            $checkout->customer->street = $address->address1;
        }

        $checkout->payData->items = $this->orderItemsList($cart, $payson, $currency);

        return $checkout;
    }
    
    /**
     * Create PS order
     *
     * @return boolean
     *
     */
    public function createOrderPS($cart_id, $checkout) {
        if (!isset($this->context)) {
            $this->context = Context::getContext();
        }
        
        if(_PCO_LOG_){Logger::addLog('Start create order.', 1, NULL, NULL, NULL, true);}
        
        // Load cart
        $cart = new Cart((int) $cart_id);
        //$cart = $this->context->cart;
        
        if(_PCO_LOG_){Logger::addLog('Cart ID: ' . $cart_id, 1, NULL, NULL, NULL, true);}
        if(_PCO_LOG_){Logger::addLog('Checkout ID: ' .  $checkout->id, 1, NULL, NULL, NULL, true);}
       
        try {
            // Check if order exists
            if ($cart->OrderExists() == false) {
                $currency = new Currency($cart->id_currency);

                // Create or update customer
                $id_customer = (int) (Customer::customerExists($checkout->customer->email, true, true));
                if ($id_customer > 0) {
                    $customer = new Customer($id_customer);
                    $address = $this->updatePaysonAddressPS(Country::getByIso($checkout->customer->countryCode), $checkout, $customer->id);
                    if (!Validate::isLoadedObject($address)) {
                        // Registred customer has no addres in PS, create new
                        $address = $this->addPaysonAddressPS(Country::getByIso($checkout->customer->countryCode), $checkout, $customer->id);
                    }
                } else {
                    // Create a new customer in PS
                    $customer = $this->addPaysonCustomerPS($cart->id, $checkout);
                    // Create a new customer address in PS
                    $address = $this->addPaysonAddressPS(Country::getByIso($checkout->customer->countryCode), $checkout, $customer->id);
                }

                $cart->secure_key = $customer->secure_key;
                $cart->id_customer = $customer->id;
                $cart->save();
                
                $cache_id = 'objectmodel_cart_'.$cart->id.'*';
                Cache::clean($cache_id);
                $cart = new Cart($cart->id);

                $comment = $this->l('Checkout ID:') . ' ' . $checkout->id . "\n";
                $comment .= $this->l('Checkout Status:') . ' ' . $checkout->status . "\n";
                $comment .= $this->l('Cart ID:') . ' ' . $customer->id . "\n";

                // Order total
                //$total = (float) $cart->getOrderTotal(true, Cart::BOTH) < $checkout->payData->totalPriceIncludingTax + 2 && (float) $cart->getOrderTotal(true, Cart::BOTH) > $checkout->payData->totalPriceIncludingTax - 2? (float) $cart->getOrderTotal(true, Cart::BOTH) : $checkout->payData->totalPriceIncludingTax;
                $total = $cart->getOrderTotal(true, Cart::BOTH);
                
                if(_PCO_LOG_){Logger::addLog('Address ID: ' . $address->id, 1, NULL, NULL, NULL, true);}
                if(_PCO_LOG_){Logger::addLog('Carrier ID: ' . $cart->id_carrier, 1, NULL, NULL, NULL, true);}
                if(_PCO_LOG_){Logger::addLog('Cart total: ' . $total, 1, NULL, NULL, NULL, true);}
                if(_PCO_LOG_){Logger::addLog('CreateOrder - Checkout total: ' . $checkout->payData->totalPriceIncludingTax, 1, NULL, NULL, NULL, true);}
                if(_PCO_LOG_){Logger::addLog('Cart delivery cost: ' . $cart->getOrderTotal(true, Cart::ONLY_SHIPPING), 1, NULL, NULL, NULL, true);}

                // Create order
                $this->validateOrder((int) $cart->id, Configuration::get("PAYSONCHECKOUT2_ORDER_STATE_PAID"), $total, $this->displayName, $comment . '<br />', array(), (int) $currency->id, false, $customer->secure_key);
                
                // Get new order ID
                $order = Order::getOrderByCartId((int)($cart->id));
                
                // Save order number in DB
                $this->updatePaysonOrderEvent($checkout, $cart->id, (int) $order);
                
                return $order;
            } else {
                 Logger::addLog('PS order already exits.', 2, NULL, NULL, NULL, true);
            }
        } catch (Exception $ex) {
            Logger::addLog('PS failed to create order: ' . $ex->getMessage(), 1, NULL, NULL, NULL, true);
        }
        return FALSE;
    }
    
    public function PaysonorderExists($purchaseid) {
        $result = (bool) Db::getInstance()->getValue('SELECT count(*) FROM `' . _DB_PREFIX_ . 'payson_embedded_order` WHERE `purchase_id` = ' . (int) $purchaseid);
        return $result;
    }
    
    public function getPaysonOrderEventId($cartId) {
        $result = Db::getInstance()->getValue('SELECT checkout_id FROM `' . _DB_PREFIX_ . 'payson_embedded_order` WHERE `cart_id` = ' . (int) $cartId);
        return $result;
    }
    
    /*
     * @return void
     * @param checkoutId
     * @param $currentCartId
     * @disc The function save the parameters in the database
     */

    public function createPaysonOrderEvent($checkoutId, $cartId = 0) {
        $alreadyCreated = $this->getPaysonOrderEventId($cartId);
        if (!isset($alreadyCreated) || (int) $alreadyCreated < 1) {
            Db::getInstance()->insert('payson_embedded_order', array(
                'cart_id' => (int) $cartId,
                'checkout_id' => $checkoutId,
                'purchase_id' => $checkoutId,
                'payment_status' => 'created',
                'added' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s')
                    )
            );
        } else {
           Db::getInstance()->update('payson_embedded_order', array(
                'checkout_id' => $checkoutId,
                'purchase_id' => $checkoutId,
                'payment_status' => 'created',
                'added' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s')
               ), 'cart_id = '.(int) $cartId);
        }
    }

    /*
     * @return void
     * @param $checkout
     * @param $ccartId
     * @param $psOrder
     * @disc The function update the parameters in the database
     */

    public function updatePaysonOrderEvent($checkout, $cartId = 0, $psOrder = 0) {
        
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'payson_embedded_order` SET
            `cart_id` = "' . (int) $cartId . '",';
             if ($psOrder > 0) {
                $sql .= '`order_id` = "' . (int) $psOrder . '",';
              }
            $sql .= '`payment_status` = "' . $checkout->status . '",
            `updated` = NOW(),
            `sender_email` = "' . $checkout->customer->email . '", 
            `currency_code` = "' . $checkout->payData->currency . '",
            `tracking_id` = "",
            `type` = "embedded",
            `shippingAddress_name` = "' . $checkout->customer->firstName . '",
            `shippingAddress_lastname` = "' . $checkout->customer->lastName . '",
            `shippingAddress_street_address` = "' . $checkout->customer->street . '",
            `shippingAddress_postal_code` = "' . $checkout->customer->postalCode . '",
            `shippingAddress_city` = "' . $checkout->customer->city . '",
            `shippingAddress_country` = "' . $checkout->customer->countryCode . '"
            WHERE `checkout_id` = "' . $checkout->id . '"';
                
        Db::getInstance()->execute( $sql);
    }
    
    public function getSnippetUrl($snippet) {
        $str = "url='";
        $url = explode($str, $snippet);
        $newStr = "'>";
        return explode($newStr, $url[1]);
    }

    private function returnCall($code) {
        $this->responseCode($code);
        exit();
    }

    private function responseCode($code) {
        return var_dump(http_response_code($code));
    }
    
    public function languagePayson($language) {
        switch (strtoupper($language)) {
            case "SE":
            case "SV":
                return "SV";
            case "FI":
                return "FI";
            default:
                return "EN";
        }
    }
    
    /*
     * @return the object of PaysonApi
     * 
     */
    public function getPaysonApiInstance() {
        require_once(_PS_MODULE_DIR_ . 'paysoncheckout2/lib/paysonapi.php');
        if ((int) Configuration::get('PAYSONCHECKOUT2_MODE') == 1) {
            if (strlen(trim(Configuration::get('PAYSONCHECKOUT2_TESTAGENTID'))) > 0 && strlen(trim(Configuration::get('PAYSONCHECKOUT2_TESTAPIKEY'))) > 0) {
                // Use TestAgent
                return new PaysonEmbedded\PaysonApi(trim(Configuration::get('PAYSONCHECKOUT2_TESTAGENTID')), trim(Configuration::get('PAYSONCHECKOUT2_TESTAPIKEY')), TRUE);
            } else {
                // Sandbox
                return new PaysonEmbedded\PaysonApi('4', '2acab30d-fe50-426f-90d7-8c60a7eb31d4', TRUE);
            }
        } else {
            // Production mode
            return new PaysonEmbedded\PaysonApi(trim(Configuration::get('PAYSONCHECKOUT2_AGENTID')), trim(Configuration::get('PAYSONCHECKOUT2_APIKEY')), FALSE);
        }
    }
    
    public function addPaysonCustomerPS($cartId, $checkout) {
        $cart = new Cart(intval($cartId));

        $customer = new Customer();
        $password = Tools::passwdGen(8);
        $customer->is_guest = 0;
        $customer->passwd = Tools::encrypt($password);
        $customer->id_default_group = (int) (Configuration::get('PS_CUSTOMER_GROUP', null, $cart->id_shop));
        $customer->optin = 0;
        $customer->active = 1;
        $customer->email = $checkout->customer->email;
        $customer->firstname = str_replace(array(':',',', ';', '+', '"', "'"), array(' '), (strlen($checkout->customer->firstName) > 31 ? substr($checkout->customer->firstName, 0, $address::$definition['fields']['firstname']['size']) : $checkout->customer->firstName));
        $customer->lastname = $checkout->customer->lastName != NULL ? $checkout->customer->lastName : str_replace(array(':',',', ';', '+', '"', "'"), array(' '), (strlen($checkout->customer->firstName) > 31 ? substr($checkout->customer->firstName, 0, $address::$definition['fields']['firstname']['size']) : $checkout->customer->firstName));
        $customer->id_gender =0;
        $customer->add();
        if(_PCO_LOG_){Logger::addLog('Create PS Customer - Checkout: ' . print_r($checkout, true), 1, NULL, NULL, NULL, true);}
        if(_PCO_LOG_){Logger::addLog('Create PS Customer - Customer: ' . print_r($customer, true), 1, NULL, NULL, NULL, true);}
        return $customer;     
    }
    
    public function addPaysonAddressPS($countryId, $checkout, $customerId) {
        $address = new Address();
        $address->firstname = str_replace(array(':',',', ';', '+', '"', "'"), array(' '), (strlen($checkout->customer->firstName) > 31 ? substr($checkout->customer->firstName, 0, $address::$definition['fields']['firstname']['size']) : $checkout->customer->firstName));
        $address->lastname = $checkout->customer->lastName != NULL ? $checkout->customer->lastName : (str_replace(array(':',',', ';', '+', '"', "'"), array(' '), (strlen($checkout->customer->firstName) > 31 ? substr($checkout->customer->firstName, 0, $address::$definition['fields']['firstname']['size']) : $checkout->customer->firstName)));

        $address->address1 = $checkout->customer->street;
        $address->address2 = '';
        $address->city = $checkout->customer->city;
        $address->postcode = $checkout->customer->postalCode;
        $address->country = Country::getNameById(Configuration::get('PS_LANG_DEFAULT'), $countryId);
        $address->id_customer = $customerId;
        $address->id_country = $countryId;
        $address->phone = '000000';
        $address->phone_mobile = '000000';
        //$address->id_state   = (int)$customer->id_state;
        $address->alias = $this->l('Payson account address');
        $address->add();
        //if(_PCO_LOG_){Logger::addLog('Create PS Address - Checkout: ' . print_r($checkout, true), 1, NULL, NULL, NULL, true);}
        //if(_PCO_LOG_){Logger::addLog('Create PS Address - Address: ' . print_r($address, true), 1, NULL, NULL, NULL, true);}
        return $address;                   
    }
    
    public function updatePaysonAddressPS($countryId, $checkout, $customerId) {
        $address = new Address(Address::getFirstCustomerAddressId((int) $customerId)); 
        $address->firstname = str_replace(array(':',',', ';', '+', '"', "'"), array(' '), (strlen($checkout->customer->firstName) > 31 ? substr($checkout->customer->firstName, 0, $address::$definition['fields']['firstname']['size']) : $checkout->customer->firstName));
        $address->lastname = $checkout->customer->lastName != NULL ? $checkout->customer->lastName : (str_replace(array(':',',', ';', '+', '"', "'"), array(' '), (strlen($checkout->customer->firstName) > 31 ? substr($checkout->customer->firstName, 0, $address::$definition['fields']['firstname']['size']) : $checkout->customer->firstName)));
        $address->address1 = $checkout->customer->street;
        $address->address2 = '';
        $address->city = $checkout->customer->city;
        $address->postcode = $checkout->customer->postalCode;
        $address->country = Country::getNameById(Configuration::get('PS_LANG_DEFAULT'),$countryId);
        $address->id_country = Country::getByIso($checkout->customer->countryCode);
        $address->alias = $this->l('Payson account address');
        $address->update();
        //if(_PCO_LOG_){Logger::addLog('Update PS Address - Checkout: ' . print_r($checkout, true), 1, NULL, NULL, NULL, true);}
        //if(_PCO_LOG_){Logger::addLog('Update PS Address - Address: ' . print_r($address, true), 1, NULL, NULL, NULL, true);}
        return $address;                   
    }
    
    public function orderItemsList($cart, $payson, $currency = null) {
        require_once(_PS_MODULE_DIR_ . 'paysoncheckout2/lib/orderitem.php');
        $lastrate = "notset";
        $has_different_rates = false;
        
        $orderitemslist = array();
        $totalCartValue = 0;
        $cur = $currency->decimals;
        foreach ($cart->getProducts() as $cartProduct) {
            
            if ($lastrate == "notset") {
                $lastrate = $cartProduct['rate'];
            } elseif ($lastrate != $cartProduct['rate']) {
                $has_different_rates = true;
            }
            
            $price = Tools::ps_round($cartProduct['price_wt'], 2);
            $totalCartValue += ($price * (int) ($cartProduct['cart_quantity']));
            
            if (isset($cartProduct['quantity_discount_applies']) && $cartProduct['quantity_discount_applies'] == 1){
                $payson->discountApplies = 1;
            }

            $my_taxrate = $cartProduct['rate'] / 100;

            $product_price = Tools::ps_round($cartProduct['price_wt'], $cur * _PS_PRICE_DISPLAY_PRECISION_);
            $attributes_small = isset($cartProduct['attributes_small']) ? $cartProduct['attributes_small'] : '';
            $orderitemslist[] = new  PaysonEmbedded\OrderItem(
                $cartProduct['name'] . ' ' . $attributes_small, $product_price, $cartProduct['cart_quantity'], number_format($my_taxrate, 3, '.', ''), $cartProduct['id_product']
            );
        }
        
        $cartDiscounts = $cart->getDiscounts();

        $total_shipping_wt = Tools::ps_round($cart->getTotalShippingCost(), $cur * _PS_PRICE_DISPLAY_PRECISION_);
        $total_shipping_wot = 0;
        $carrier = new Carrier($cart->id_carrier, $cart->id_lang);

        if ($total_shipping_wt > 0) {
            $carriertax = Tax::getCarrierTaxRate((int) $carrier->id, $cart->id_address_invoice);
            $carriertax_rate = $carriertax / 100;
            $forward_vat = 1 + $carriertax_rate;
            $total_shipping_wot = $total_shipping_wt / $forward_vat;

            if (!empty($cartDiscounts) && (!empty($cartDiscounts[0]['obj'])) && $cartDiscounts[0]['obj']->free_shipping) {

            } else {
                $orderitemslist[] = new  PaysonEmbedded\OrderItem(
                        isset($carrier->name) ? $carrier->name : $this->l('Shipping'), $total_shipping_wt, 1, number_format($carriertax_rate, 2, '.', ''), $this->l('Shipping'), PaysonEmbedded\OrderItemType::SERVICE);
            }
        }

        $tax_rate_discount = 0;
        $taxDiscount = Cart::getTaxesAverageUsed((int) ($cart->id));

        if (isset($taxDiscount) && $taxDiscount != 1) {
            $tax_rate_discount = $taxDiscount * 0.01;
        }

        $total_discounts = 0;
        foreach ($cart->getCartRules(CartRule::FILTER_ACTION_ALL) as $cart_rule) {
            $value_real = $cart_rule["value_real"];
            $value_tax_exc = $cart_rule["value_tax_exc"];

            if ($has_different_rates == false) {
                $discount_tax_rate = Tools::ps_round($lastrate, 2);
            } else {
                $discount_tax_rate = (($value_real / $value_tax_exc) - 1) * 100;

                $discount_tax_rate = Tools::ps_round($discount_tax_rate, 2);
            }

            if ($totalCartValue<=$total_discounts) {
                $value_real = 0;
            }

            $orderitemslist[] = new  PaysonEmbedded\OrderItem(
                    $cart_rule["name"], -(Tools::ps_round($value_real, 2)), 1, number_format(($discount_tax_rate * 0.01), 4, '.', ''), $this->l('Discount'), PaysonEmbedded\OrderItemType::DISCOUNT);
            
            $total_discounts += $value_real;
        }
        
        if ($cart->gift) {
           $wrappingTemp = number_format(Tools::convertPrice((float) $cart->getGiftWrappingPrice(false), Currency::getCurrencyInstance((int) $cart->id_currency)), Configuration::get('PS_PRICE_DISPLAY_PRECISION'), '.', '') * number_format((((($cart->getOrderTotal(true, Cart::ONLY_WRAPPING) * 100) / $cart->getOrderTotal(false, Cart::ONLY_WRAPPING))) / 100), 2, '.', '');
           $orderitemslist[] = new  PaysonEmbedded\OrderItem( $this->l('Gift Wrapping'), $wrappingTemp, 1, number_format((((($cart->getOrderTotal(true, Cart::ONLY_WRAPPING) * 100) / $cart->getOrderTotal(false, Cart::ONLY_WRAPPING)) - 100) / 100), 2, '.', ''), 'wrapping', PaysonEmbedded\OrderItemType::SERVICE);
        }

        return $orderitemslist;
    }
    
    public function cutNum($num, $precision = 2) {
        return floor($num).Tools::substr($num-floor($num), 1, $precision+1);
    }
    
    /*
     * Update Payson order status to canceled or shipped
     */
    public function hookUpdateOrderStatus($params) {
        $newOrderStatus = $params['newOrderStatus'];
        $order = new Order((int) $params['id_order']);

        if ($order->module == 'paysoncheckout2') {
            if(_PCO_LOG_){Logger::addLog('Order status changed to ' . $newOrderStatus->name . ' for order: ' . $params['id_order'], 1, NULL, NULL, NULL, true);}
            if(_PCO_LOG_){Logger::addLog('Order status ID: ' . $newOrderStatus->id, 1, NULL, NULL, NULL, true);}
            
            if(_PCO_LOG_){Logger::addLog('Payson shipped status ID: ' . Configuration::get('PAYSON_ORDER_SHIPPED_STATE', null, null, $order->id_shop), 1, NULL, NULL, NULL, true);}
            if(_PCO_LOG_){Logger::addLog('Payson canceled status ID: ' . Configuration::get('PAYSON_ORDER_CANCEL_STATE', null, null, $order->id_shop), 1, NULL, NULL, NULL, true);}
            
            if ($newOrderStatus->id == Configuration::get('PAYSON_ORDER_SHIPPED_STATE', null, null, $order->id_shop) || $newOrderStatus->id == Configuration::get('PAYSON_ORDER_CANCEL_STATE', null, null, $order->id_shop)) {
                $checkout_id = $this->getPaysonOrderEventId($order->id_cart);
                
                if (isset($checkout_id) && $checkout_id !== NULL) {
                    $paysonApi = $this->getPaysonApiInstance();
                    $checkout = $paysonApi->GetCheckout($checkout_id);
                    if(_PCO_LOG_){Logger::addLog('Payson order current status is: ' . $checkout->status, 1, NULL, NULL, NULL, true);}
                    
                    if ($newOrderStatus->id == Configuration::get('PAYSON_ORDER_SHIPPED_STATE', null, null, $order->id_shop)) {
                        if ($checkout->status == 'readyToShip') {
                            try {
                                if(_PCO_LOG_){Logger::addLog('Updating Payson order shipped.', 1, NULL, NULL, NULL, true);}
                                //$payson = $this;
                                //$cart = new Cart((int) $order->id_cart);
                                //$customer = new Customer((int) $cart->id_customer);  
                                //$address = new Address((int) $cart->id_address_invoice);
                                //$cartCurrency = new Currency((int) $cart->id_currency);
                                $checkout->status = 'shipped';
                                //$checkout->payData->items = $this->orderItemsList($cart, $this, $cartCurrency);
                                // Update checkout object
                                //$updatedCheckout = $paysonApi->UpdateCheckout($this->updatePaysonCheckout($checkout, $customer,  $cart, $this, $address, $cartCurrency));
                                $updatedCheckout = $paysonApi->UpdateCheckout($checkout);

                                // Update data in Payson order table
                                $this->updatePaysonOrderEvent($updatedCheckout, $order->id_cart, $order->id_order);

                            } catch (Exception $e) {
                                Logger::addLog('Order update fail: ' . $e->getMessage(), 3, NULL, NULL, NULL, true);
                            }
                        } else {
                            // Error
                            Logger::addLog('Failed to update Payson order status to shipped. Payson order has wrong status.', 3, NULL, NULL, NULL, true);
                        }
                    }

                    if ($newOrderStatus->id == Configuration::get('PAYSON_ORDER_CANCEL_STATE', null, null, $order->id_shop)) {
                        if ($checkout->status == 'readyToShip') {
                            try {
                                if(_PCO_LOG_){Logger::addLog('Updating Payson order canceled.', 1, NULL, NULL, NULL, true);}

                                $checkout->status = 'canceled';
                                $updatedCheckout = $paysonApi->UpdateCheckout($checkout);

                                // Update data in Payson order table
                                $this->updatePaysonOrderEvent($updatedCheckout, $order->id_cart, $order->id_order);
                            } catch (Exception $e) {
                                Logger::addLog('Order update fail: ' . $e->getMessage(), 3, NULL, NULL, NULL, true);
                            }
                        } else {
                            // Error
                            Logger::addLog('Failed to update Payson order status to canceled. Payson order has wrong status for update.', 2, NULL, NULL, NULL, true);
                        }
                    }
 
                    if(_PCO_LOG_){Logger::addLog('Updated Payson order status is: ' . $updatedCheckout->status, 1, NULL, NULL, NULL, true);}

                } else {
                    // Error
                    Logger::addLog('Failed to send updated order stauts to Payson. Unable to get checkout ID from DB.', 3, NULL, NULL, NULL, true);
                }
            }
        }
    }
}
