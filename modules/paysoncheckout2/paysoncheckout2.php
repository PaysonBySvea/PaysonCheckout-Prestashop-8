<?php
/**
 * 2019 Payson AB
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 *  @author    Payson AB <integration@payson.se>
 *  @copyright 2019 Payson AB
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PaysonCheckout2 extends PaymentModule
{
    public $moduleVersion;
    public $illNameChars;

    public function __construct()
    {
        $this->name = 'paysoncheckout2';
        $this->tab = 'payments_gateways';
        $this->version = '3.0.20';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->author = 'Payson AB';
        $this->module_key = '4015ee54469de01eaa9150b76054547e';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Payson Checkout 2.0');
        $this->description = $this->l('Offer secure payments with Payson. Customers can pay by invoice, partial payments, card or internet bank');

        $this->moduleVersion = sprintf('CO2_PrestaShop_1.7|%s|%s', $this->version, _PS_VERSION_);

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }

        if (!defined('_PCO_LOG_')) {
            define('_PCO_LOG_', Configuration::get('PAYSONCHECKOUT2_LOG'));
        }
        
        $this->illNameChars = array('?', '#', '!', '=', '&', '{', '}', '[', ']', '{', '}', '(', ')', ':', ',', ';', '+', '"', "'", '¤', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
    }
    
    public function install()
    {
        if (parent::install() == false || 
                !$this->registerHook('paymentOptions') ||
                !$this->registerHook('paymentReturn') ||
                !$this->registerHook('actionOrderStatusUpdate') ||
                !$this->registerHook('displayBeforeCarrier') ||
                !$this->registerHook('displayAfterCarrier')
                ) {
            return false;
        }
        
        // Set some defaults
        Configuration::updateValue('PAYSONCHECKOUT2_MODULE_ENABLED', 1);
        Configuration::updateValue('PAYSONCHECKOUT2_PHONE', 'required');
        Configuration::updateValue('PAYSONCHECKOUT2_ONE_PAGE', 1);
        Configuration::updateValue('PAYSONCHECKOUT2_MODE', 1);
        Configuration::updateValue('PAYSONCHECKOUT2_TEMPLATE', 'one_page_2_col');
        Configuration::updateValue('PAYSONCHECKOUT2_SHOW_CONFIRMATION', 1);
        Configuration::updateValue('PAYSONCHECKOUT2_SHOW_TERMS', 0);
        Configuration::updateValue('PAYSONCHECKOUT2_NEWSLETTER', 0);
        Configuration::updateValue('PAYSONCHECKOUT2_COLOR_SCHEME', 'white');
        Configuration::updateValue('PAYSONCHECKOUT2_AGENTID', '4');
        Configuration::updateValue('PAYSONCHECKOUT2_APIKEY', '2acab30d-fe50-426f-90d7-8c60a7eb31d4');
        Configuration::updateValue('PAYSONCHECKOUT2_CUSTOM_CSS', '#module-paysoncheckout2-pconepage .cart-grid-body .card-block h1{color:red;}' . "\r\n" . '#module-paysoncheckout2-pconepage .cart-grid-body .card-block h1{font-size:12px;}');
        Configuration::updateValue('PAYSONCHECKOUT2_USE_CUSTOM_CSS', 0);
        
        $this->createPaysonOrderTable();

        $orderStates = OrderState::getOrderStates(Configuration::get('PS_LANG_DEFAULT'));
        $name = $this->l('Betald med Payson Checkout 2.0');
        $config_name = 'PAYSONCHECKOUT2_ORDER_STATE_PAID';
        $this->createPaysonOrderStates($name, $orderStates, $config_name, true);

        return true;
    }

    public function uninstall()
    {
        if (parent::uninstall() == false ||
                Configuration::deleteByName('PAYSONCHECKOUT2_AGENTID') == false ||
                Configuration::deleteByName('PAYSONCHECKOUT2_APIKEY') == false ||
                Configuration::deleteByName('PAYSONCHECKOUT2_MODE') == false ||
                Configuration::deleteByName('PAYSONCHECKOUT2_SHOW_CONFIRMATION') == false ||
                Configuration::deleteByName('PAYSONCHECKOUT2_LOG') == false ||
                Configuration::deleteByName('PAYSONCHECKOUT2_ONE_PAGE') == false ||
                Configuration::deleteByName('PAYSONCHECKOUT2_VERIFICATION') == false ||
                Configuration::deleteByName('PAYSONCHECKOUT2_COLOR_SCHEME') == false ||
                Configuration::deleteByName('PAYSONCHECKOUT2_PHONE') == false ||
                Configuration::deleteByName('PAYSONCHECKOUT2_MODULE_ENABLED') == false ||
                Configuration::deleteByName('PAYSON_ORDER_SHIPPED_STATE') == false ||
                Configuration::deleteByName('PAYSON_ORDER_CANCEL_STATE') == false ||
                Configuration::deleteByName('PAYSON_ORDER_CREDITED_STATE') == false ||
                Configuration::deleteByName('PAYSONCHECKOUT2_TEMPLATE') == false ||
                Configuration::deleteByName('PAYSONCHECKOUT2_MODULE_ENABLED') == false ||
                Configuration::deleteByName('PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS') == false ||
                Configuration::deleteByName('PAYSONCHECKOUT2_SHOW_TERMS') == false ||
                Configuration::deleteByName('PAYSONCHECKOUT2_NEWSLETTER') == false ||
                Configuration::deleteByName('PAYSONCHECKOUT2_CUSTOM_CSS') == false ||
                Configuration::deleteByName('PAYSONCHECKOUT2_USE_CUSTOM_CSS') == false
                
        ) {
            return false;
        }

        return true;
    }

    public function hookPaymentOptions($params)
    {
        //if (!$this->active || (int) Configuration::get('PAYSONCHECKOUT2_MODULE_ENABLED') == 0 || (int) Configuration::get('PAYSONCHECKOUT2_ONE_PAGE') == 1) {
        if (!$this->active || (int) Configuration::get('PAYSONCHECKOUT2_MODULE_ENABLED') == 0) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $payment_options = array($this->getIframePaymentOption());

        return $payment_options;
    }

    public function checkCurrency($cart)
    {
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

    public function checkCurrencyName($cartCurrency, $checkoutCurrency)
    {
        if (Tools::strtoupper($cartCurrency) == Tools::strtoupper($checkoutCurrency)) {
            return true;
        }
        
        return false;
    }

    public function getContent()
    {
        $saved = false;
        $errors = '';

        if (Tools::isSubmit('btnSettingsSubmit')) {
            Configuration::updateValue('PAYSONCHECKOUT2_AGENTID', Tools::getValue('PAYSONCHECKOUT2_AGENTID'));
            Configuration::updateValue('PAYSONCHECKOUT2_APIKEY', Tools::getValue('PAYSONCHECKOUT2_APIKEY'));
            Configuration::updateValue('PAYSONCHECKOUT2_MODE', (int) Tools::getValue('PAYSONCHECKOUT2_MODE'));
            Configuration::updateValue('PAYSONCHECKOUT2_SHOW_CONFIRMATION', 1);
            Configuration::updateValue('PAYSONCHECKOUT2_LOG', (int) Tools::getValue('PAYSONCHECKOUT2_LOG'));
            Configuration::updateValue('PAYSONCHECKOUT2_ONE_PAGE', (int) Tools::getValue('PAYSONCHECKOUT2_ONE_PAGE'));
            Configuration::updateValue('PAYSONCHECKOUT2_VERIFICATION', (int) Tools::getValue('PAYSONCHECKOUT2_VERIFICATION'));
            Configuration::updateValue('PAYSONCHECKOUT2_COLOR_SCHEME', Tools::getValue('PAYSONCHECKOUT2_COLOR_SCHEME'));
            Configuration::updateValue('PAYSONCHECKOUT2_MODULE_ENABLED', 1);
            Configuration::updateValue('PAYSON_ORDER_SHIPPED_STATE', (int) Tools::getValue('PAYSON_ORDER_SHIPPED_STATE'));
            Configuration::updateValue('PAYSON_ORDER_CANCEL_STATE', (int) Tools::getValue('PAYSON_ORDER_CANCEL_STATE'));
            Configuration::updateValue('PAYSON_ORDER_CREDITED_STATE', (int) Tools::getValue('PAYSON_ORDER_CREDITED_STATE'));
            Configuration::updateValue('PAYSONCHECKOUT2_TEMPLATE', Tools::getValue('PAYSONCHECKOUT2_TEMPLATE'));
            Configuration::updateValue('PAYSONCHECKOUT2_PHONE', Tools::getValue('PAYSONCHECKOUT2_PHONE'));
            Configuration::updateValue('PAYSONCHECKOUT2_NEWSLETTER', (int) Tools::getValue('PAYSONCHECKOUT2_NEWSLETTER'));
            Configuration::updateValue('PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS', (int) Tools::getValue('PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS'));
            Configuration::updateValue('PAYSONCHECKOUT2_SHOW_TERMS', (int) Tools::getValue('PAYSONCHECKOUT2_SHOW_TERMS'));
            $saved = true;
        }
        
        if (Tools::isSubmit('btnCssSubmit')) {
            Configuration::updateValue('PAYSONCHECKOUT2_CUSTOM_CSS', Tools::getValue('PAYSONCHECKOUT2_CUSTOM_CSS'));
            Configuration::updateValue('PAYSONCHECKOUT2_USE_CUSTOM_CSS', (int) Tools::getValue('PAYSONCHECKOUT2_USE_CUSTOM_CSS'));
            $saved = true;
        }

        $this->context->smarty->assign(array(
            'errorMSG' => $errors,
            'isSaved' => $saved,
            'commonform' => $this->createSettingsForm(),
            'cssform' => $this->createCSSForm(),
        ));

        return $this->display(__FILE__, 'views/templates/admin/payson_admin.tpl');
    }

    public function createCSSForm()
    {
        $css_form = array();
        $css_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Custom CSS is used to change the appearance of the Payson One Page Checkout'),
                'icon' => '',
            ),
            'input' => array(
                array(
                    'type' => 'switch',
                    'label' => $this->l('Use custom CSS'),
                    'name' => 'PAYSONCHECKOUT2_USE_CUSTOM_CSS',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'ccss_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'ccss_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ),
                    ),
                    'desc' => '',
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Custom CSS'),
                    'name' => 'PAYSONCHECKOUT2_CUSTOM_CSS',
                    'rows' => 30,
                    'desc' => $this->l('Make sure to use well formatted CSS or you may brake the checkout!'),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );
                
        $helper = $this->getFormHelper();
        $helper->submit_action = 'btnCssSubmit';
        return $helper->generateForm($css_form);
    }

    public function createSettingsForm()
    {
        $orderStates = OrderState::getOrderStates((int) $this->context->cookie->id_lang);
        array_unshift($orderStates, array('id_order_state' => '-1', 'name' => $this->l('Deactivated')));
        //$orderStates[] = array('id_order_state' => '-1', 'name' => $this->l('Deactivated'));
        $groupTitle = $this->l('Agent');
        if (Configuration::get('PS_DISABLE_OVERRIDES')) {
            $groupTitle .= ' - <a href="' . $this->context->link->getAdminLink('AdminPerformance', true) . '">' . $this->l('WARNING: Disable overrides must be set to "No". Click here to change.') . '</a>';
        }
        $fields_form = array();
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $groupTitle,
                'icon' => '',
            ),
            'input' => array(
                array(
                    'type' => 'switch',
                    'label' => $this->l('Test mode'),
                    'name' => 'PAYSONCHECKOUT2_MODE',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'testmode_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'testmode_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ),
                    ),
                    'desc' => $this->l('Verify your installation in test mode before going live'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Agent ID'),
                    'name' => 'PAYSONCHECKOUT2_AGENTID',
                    'class' => 'fixed-width-lg',
                    'required' => false,
                    'desc' => $this->l('Enter your Agent ID'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('API-key'),
                    'name' => 'PAYSONCHECKOUT2_APIKEY',
                    'class' => 'fixed-width-lg',
                    'required' => false,
                    'desc' => $this->l('Enter your API-key'),
                )
            )
        );
  
        $fields_form[1]['form'] = array(
            'legend' => array(
                'title' => $this->l('Order managment'),
                'icon' => '',
            ),
            'input' => array(
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
                    'label' => $this->l('Refunded order status'),
                    'name' => 'PAYSON_ORDER_CREDITED_STATE',
                    'desc' => $this->l('Payson order will be refunded when this order status is set.'),
                    'options' => array(
                        'query' => $orderStates,
                        'id' => 'id_order_state',
                        'name' => 'name',
                    ),
                ),
            )
        );

        $fields_form[2]['form'] = array(
            'legend' => array(
                'title' => $this->l('Payment window'),
                'icon' => '',
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Color scheme'),
                    'name' => 'PAYSONCHECKOUT2_COLOR_SCHEME',
                    'desc' => $this->l('Payment window color scheme'),
                    'options' => array(
                        'query' => array(
                            array(
                                'value' => 'white',
                                'label' => $this->l('White'),
                            ),
                            array(
                                'value' => 'gray',
                                'label' => $this->l('Gray'),
                            ),
                        ),
                        'id' => 'value',
                        'name' => 'label',
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('BankID'),
                    'name' => 'PAYSONCHECKOUT2_VERIFICATION',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'veri_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'veri_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ),
                    ),
                    'desc' => $this->l('Select Yes to force customer identification by BankID'),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Show phone'),
                    'name' => 'PAYSONCHECKOUT2_PHONE',
                    'desc' => $this->l('Settings for phone'),
                    'options' => array(
                        'query' => array(array('id_option' => 'required', 'name' => $this->l('Yes, required')), array('id_option' => 'optional', 'name' => $this->l('Yes, optional')), array('id_option' => 'no', 'name' => $this->l('No'))),
                        'id' => 'id_option',
                        'name' => 'name',
                    ),
                ),
            )
        );
        
        $fields_form[3]['form'] = array(
            'legend' => array(
                'title' => $this->l('One Page Checkout'),
                'icon' => '',
            ),
            'input' => array(
                array(
                    'type' => 'switch',
                    'label' => $this->l('One Page Checkout'),
                    'name' => 'PAYSONCHECKOUT2_ONE_PAGE',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'op_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'op_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ),
                    ),
                    'desc' => $this->l('Select Yes to show the payment window on the checkout page'),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Checkout template'),
                    'name' => 'PAYSONCHECKOUT2_TEMPLATE',
                    'desc' => $this->l('Checkout layout template.'),
                    'options' => array(
                        'query' => array(array('id_option' => 'one_page_2_col', 'name' => $this->l('2 columns')), array('id_option' => 'one_page_l', 'name' => $this->l('Left aligned')), array('id_option' => 'one_page_r', 'name' => $this->l('Right aligned'))),
                        'id' => 'id_option',
                        'name' => 'name',
                    ),
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
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'link_pay_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ),
                    ),
                    'desc' => $this->l('Select Yes to show a link to other payment methods'),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Show newsletter checkbox'),
                    'name' => 'PAYSONCHECKOUT2_NEWSLETTER',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'newsl_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'newsl_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ),
                    ),
                    'desc' => $this->l('Check to show newsletter checkbox'),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Show terms checkbox'),
                    'name' => 'PAYSONCHECKOUT2_SHOW_TERMS',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'terms_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'terms_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ),
                    ),
                    'desc' => $this->l('Select Yes to require customers to accept the terms and conditions'),
                )
            )
        );
        
        $fields_form[4]['form'] = array(
            'legend' => array(
                'title' => $this->l('Log'),
                'icon' => '',
            ),
            'input' => array(
                array(
                    'type' => 'switch',
                    'label' => $this->l('Log messages'),
                    'name' => 'PAYSONCHECKOUT2_LOG',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'log_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'log_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ),
                    ),
                    'desc' => $this->l('Check to log messages from Payson Checkout 2.0'),
                )
            )
        );
        
        $fields_form[5]['form'] = array(
            'legend' => array(
                'title' => $this->l('Save changes'),
                'icon' => '',
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            )
        );
        
        $helper = $this->getFormHelper();
        $helper->submit_action = 'btnSettingsSubmit';
        return $helper->generateForm($fields_form);
    }

    public function getFormHelper()
    {
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
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) .
                '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper;
    }
    
    // Get values for module configuration
    public function getConfigFieldsValues()
    {
        return array(
            'PAYSONCHECKOUT2_MODE' => Tools::getValue('PAYSONCHECKOUT2_MODE', Configuration::get('PAYSONCHECKOUT2_MODE')),
            'PAYSONCHECKOUT2_COLOR_SCHEME' => Tools::getValue('PAYSONCHECKOUT2_COLOR_SCHEME', Configuration::get('PAYSONCHECKOUT2_COLOR_SCHEME')),
            'PAYSONCHECKOUT2_AGENTID' => Tools::getValue('PAYSONCHECKOUT2_AGENTID', Configuration::get('PAYSONCHECKOUT2_AGENTID')),
            'PAYSONCHECKOUT2_APIKEY' => Tools::getValue('PAYSONCHECKOUT2_APIKEY', Configuration::get('PAYSONCHECKOUT2_APIKEY')),
            'PAYSONCHECKOUT2_SHOW_CONFIRMATION' => Tools::getValue('PAYSONCHECKOUT2_SHOW_CONFIRMATION', 1),
            'PAYSONCHECKOUT2_ONE_PAGE' => Tools::getValue('PAYSONCHECKOUT2_ONE_PAGE', Configuration::get('PAYSONCHECKOUT2_ONE_PAGE')),
            'PAYSONCHECKOUT2_VERIFICATION' => Tools::getValue('PAYSONCHECKOUT2_VERIFICATION', Configuration::get('PAYSONCHECKOUT2_VERIFICATION')),
            'PAYSONCHECKOUT2_LOG' => Tools::getValue('PAYSONCHECKOUT2_LOG', Configuration::get('PAYSONCHECKOUT2_LOG')),
            'PAYSONCHECKOUT2_PHONE' => Tools::getValue('PAYSONCHECKOUT2_PHONE', Configuration::get('PAYSONCHECKOUT2_PHONE')),
            'PAYSONCHECKOUT2_MODULE_ENABLED' => Tools::getValue('PAYSONCHECKOUT2_MODULE_ENABLED', Configuration::get('PAYSONCHECKOUT2_MODULE_ENABLED')),
            'PAYSON_ORDER_CANCEL_STATE' => Tools::getValue('PAYSON_ORDER_CANCEL_STATE', Configuration::get('PAYSON_ORDER_CANCEL_STATE')),
            'PAYSON_ORDER_SHIPPED_STATE' => Tools::getValue('PAYSON_ORDER_SHIPPED_STATE', Configuration::get('PAYSON_ORDER_SHIPPED_STATE')),
            'PAYSON_ORDER_CREDITED_STATE' => Tools::getValue('PAYSON_ORDER_CREDITED_STATE', Configuration::get('PAYSON_ORDER_CREDITED_STATE')),
            'PAYSONCHECKOUT2_TEMPLATE' => Tools::getValue('PAYSONCHECKOUT2_TEMPLATE', Configuration::get('PAYSONCHECKOUT2_TEMPLATE')),
            'PAYSONCHECKOUT2_SHOW_TERMS' => Tools::getValue('PAYSONCHECKOUT2_SHOW_TERMS', Configuration::get('PAYSONCHECKOUT2_SHOW_TERMS')),
            'PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS' => Tools::getValue('PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS', Configuration::get('PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS')),
            'PAYSONCHECKOUT2_NEWSLETTER' => Tools::getValue('PAYSONCHECKOUT2_NEWSLETTER', Configuration::get('PAYSONCHECKOUT2_NEWSLETTER')),
            'PAYSONCHECKOUT2_CUSTOM_CSS' => Tools::getValue('PAYSONCHECKOUT2_CUSTOM_CSS', Configuration::get('PAYSONCHECKOUT2_CUSTOM_CSS')),
            'PAYSONCHECKOUT2_USE_CUSTOM_CSS' => Tools::getValue('PAYSONCHECKOUT2_USE_CUSTOM_CSS', Configuration::get('PAYSONCHECKOUT2_USE_CUSTOM_CSS')),
        );
    }

    private function createPaysonOrderStates($name, $orderStates, $config_name, $paid)
    {
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

            if (!copy(dirname(__FILE__) . '/views/img/payson_os.gif', _PS_IMG_DIR_ . 'os/' . $orderstate->id . '.gif')) {
                return false;
            }
        }
    }

    private function createPaysonOrderTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payson_embedded_order` (
	        `payson_embedded_id` int(11) auto_increment,
                `cart_id` int(15) NOT null,
		`order_id` int(15) DEFAULT null,
                `checkout_id` varchar(40) DEFAULT null,
                `purchase_id` varchar(50) DEFAULT null,
		`payment_status` varchar(20) DEFAULT null,
		`added` datetime DEFAULT null,
		`updated` datetime DEFAULT null,
		`sender_email` varchar(50) DEFAULT null,
		`currency_code` varchar(5) DEFAULT null,
		`tracking_id`  varchar(100) DEFAULT null,
		`type` varchar(50) DEFAULT null,
		`shippingAddress_name` varchar(50) DEFAULT null,
		`shippingAddress_lastname` varchar(50) DEFAULT null,
		`shippingAddress_street_address` varchar(60) DEFAULT null,
		`shippingAddress_postal_code` varchar(20) DEFAULT null,
		`shippingAddress_city` varchar(60) DEFAULT null,
		`shippingAddress_country` varchar(60) DEFAULT null,
		PRIMARY KEY  (`payson_embedded_id`)
	        ) ENGINE=' . _MYSQL_ENGINE_;

        if (Db::getInstance()->execute($sql) == false) {
            return false;
        }
    }

    public function getIframePaymentOption()
    {
        if ((int) Configuration::get('PAYSONCHECKOUT2_MODULE_ENABLED') == 1) {
            $iframeOption = new PaymentOption();
            $iframeOption->setCallToActionText($this->l('Payson Checkout 2.0'))
                    ->setAction($this->context->link->getModuleLink($this->name, 'pconepage', array('ref' => 'opm'), true))
                    ->setAdditionalInformation($this->context->smarty->fetch('module:paysoncheckout2/views/templates/front/payment_infos.tpl'));
            //->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/logo.png'));

            return $iframeOption;
        }
    }

    public function canUpdate($checkoutStatus)
    {
        switch ($checkoutStatus) {
            case 'created':
                return true;
            case 'readyToPay':
                return true;
            case 'processingPayment':
                return true;
            case 'readyToShip':
                return false;
            case 'formsFiled':
                return false;
            default:
                return false;
        }
        return false;
    }

    public function createPaysonCheckout($customer, $cart, $payson, $currency, $id_lang, $address)
    {
        $trackingId = time();
        
        $checkoutUri = $this->context->link->getModuleLink('paysoncheckout2', 'pconepage', array('trackingId' => $trackingId, 'id_cart' => $cart->id, 'call' => 'paymentreturn'));
        $confirmationUri = $this->context->link->getModuleLink('paysoncheckout2', 'confirmation', array('trackingId' => $trackingId, 'id_cart' => $cart->id, 'call' => 'confirmation'));
        $notificationUri = $this->context->link->getModuleLink('paysoncheckout2', 'notifications', array('trackingId' => $trackingId, 'id_cart' => $cart->id, 'call' => 'notification'));
        $cms = new CMS((int) (Configuration::get('PS_CONDITIONS_CMS_ID')), (int) ($this->context->cookie->id_lang));
        $termsUri = $this->context->link->getCMSLink($cms, $cms->link_rewrite, Configuration::get('PS_SSL_ENABLED'));

        $paysonMerchant = array(
            'termsUri' => $termsUri,
            'checkoutUri' => $checkoutUri,
            'confirmationUri' => $confirmationUri,
            'notificationUri' => $notificationUri,
            'integrationInfo' => $payson->moduleVersion,
            'reference' => $cart->id,
            'validationUri' => null,
            'partnerId' => null,
        );
        PaysonCheckout2::paysonAddLog('PCO Merchant: ' . print_r($paysonMerchant, true));

        $paysonOrder = array(
            'currency' => $currency->iso_code,
            'items' => $this->orderItemsList($cart, $payson, $currency),
        );
        PaysonCheckout2::paysonAddLog('PCO Order: ' . print_r($paysonOrder, true));

        //$deliveryCountries = Carrier::getDeliveredCountries($this->context->language->id, true, true);
        $activeCountries = Country::getCountries($this->context->language->id, true, false, false);
        $moduleCountries = $this->getModuleAllowedCountries((int) $this->getPaysonModuleID(), (int) $this->context->shop->id);
        PaysonCheckout2::paysonAddLog('Language ID: ' . $this->context->language->id);
        PaysonCheckout2::paysonAddLog('Active countries: ' . print_r($activeCountries, true));
        PaysonCheckout2::paysonAddLog('Module countries: ' . print_r($moduleCountries, true));
        $allowedDeliveryCountries = array();
        foreach ($activeCountries as $country) {
            if (in_array($country['iso_code'], $moduleCountries)) {
                $allowedDeliveryCountries[] = $country['iso_code'];
            }
        }
        PaysonCheckout2::paysonAddLog('Valid countries: ' . print_r($allowedDeliveryCountries, true));

        if (!is_array($allowedDeliveryCountries) || count($allowedDeliveryCountries) < 1) {
            // null will show all countries
            $allowedDeliveryCountries = null;
        }

        $paysonGui = array(
            'colorScheme' => Configuration::get('PAYSONCHECKOUT2_COLOR_SCHEME'),
            'locale' => $this->languagePayson(Language::getIsoById($id_lang)),
            'verification' => Configuration::get('PAYSONCHECKOUT2_VERIFICATION'),
            'countries' => $allowedDeliveryCountries,
            'phoneOptional' => null,
        );
        switch (Configuration::get('PAYSONCHECKOUT2_PHONE')) {
            case 'required':
                $paysonGui['requestPhone'] = 1;
                break;
            case 'optional':
                $paysonGui['phoneOptional'] = 1;
                break;
            default:
                $paysonGui['requestPhone'] = 0;
                $paysonGui['phoneOptional'] = 0;
                break;
        }
        
        PaysonCheckout2::paysonAddLog('PCO GUI: ' . print_r($paysonGui, true));

        if (Configuration::get('PAYSONCHECKOUT2_MODE') == 1) {
            // Create test customer
            $paysonCustomer = array(
                'firstName' => 'Tess T',
                'lastName' => 'Persson',
                'email' => 'test@noreelemail.com',
                'phone' => 1111111,
                'identityNumber' => '4605092222',
                'city' => 'Stan',
                'countryCode' => 'SE',
                'postalCode' => '99999',
                'street' => '',
                'type' => 'person',
            );
        } else {
            // Prefill if customer is logged in
            $paysonCustomer = array(
                'firstName' => isset($customer->firstname) ? $customer->firstname : '',
                'lastName' => isset($customer->lastname) ? $customer->lastname : '',
                'email' => isset($customer->email) ? $customer->email : '',
                'phone' => isset($address->phone) ? $address->phone : '',
                'identityNumber' => '',
                'city' => isset($address->city) ? $address->city : '',
                'countryCode' => isset($address->id_country) ? Country::getIsoById($address->id_country) : '',
                'postalCode' => isset($address->postcode) ? $address->postcode : '',
                'street' => isset($address->address1) ? $address->address1 : '',
                'type' => 'person',
            );
        }

        return array('merchant' => $paysonMerchant, 'order' => $paysonOrder, 'gui' => $paysonGui, 'customer' => $paysonCustomer);
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
    public function getModuleAllowedCountries($module_id = null, $shop_id = null)
    {
        $sql = "SELECT id_country FROM `" . _DB_PREFIX_ . "module_country`" . "WHERE id_shop=$shop_id AND id_module=$module_id";
        $moduleCountries = Db::getInstance()->ExecuteS($sql);
        $isoCodes = array();
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
    public function getPaysonModuleID()
    {
        $sql = "SELECT id_module FROM `" . _DB_PREFIX_ . "module`" . "WHERE name='paysoncheckout2'";
        $moduleId = Db::getInstance()->getValue($sql);

        return $moduleId;
    }

    public function updatePaysonCheckout($checkout, $customer, $cart, $payson, $address, $currency)
    {
        if ($customer->email != null && $checkout['status'] != 'readyToPay') {
            $checkout['customer']['firstName'] = $customer->firstname;
            $checkout['customer']['lastName'] = $customer->lastname;
            $checkout['customer']['email'] = $customer->email;
            $checkout['customer']['phone'] = $address->phone;
            $checkout['customer']['city'] = $address->city;
            $checkout['customer']['countryCode'] = Country::getIsoById($address->id_country);
            $checkout['customer']['postalCode'] = $address->postcode;
            $checkout['customer']['street'] = $address->address1;
        }

        $checkout['order']['items'] = $this->orderItemsList($cart, $payson, $currency);

        return $checkout;
    }

    /**
     * Create PS order
     *
     * @return boolean
     *
     */
    public function createOrderPS($cart_id, $checkout)
    {
        if (!isset($this->context)) {
            $this->context = Context::getContext();
        }

        PaysonCheckout2::paysonAddLog('Start create order.');

        // Load cart
        $cart = new Cart((int) $cart_id);
        //$cart = $this->context->cart;

        PaysonCheckout2::paysonAddLog('Cart ID: ' . $cart_id);
        PaysonCheckout2::paysonAddLog('Checkout ID: ' . $checkout['id']);

        try {
            // Check if order exists
            if ($cart->OrderExists() == false) {
                $currency = new Currency($cart->id_currency);

                // Add or load customer
                if ((int) Customer::customerExists($checkout['customer']['email'], true, true) > 0) {
                    $customer = new Customer(Customer::customerExists($checkout['customer']['email'], true, true));
                } else {
                    $customer = $this->addPaysonCustomerPS($cart->id, $checkout);
                }
                // Update or create address
                $address = $this->updateCreatePsAddress(Country::getByIso($checkout['customer']['countryCode']), $checkout, $customer->id);

                $cart->secure_key = $customer->secure_key;
                $cart->id_customer = $customer->id;
                $cart->save();

                $cache_id = 'objectmodel_cart_' . $cart->id . '*';
                Cache::clean($cache_id);
                $cart = new Cart($cart->id);

                $comment = $this->l('Checkout ID:') . ' ' . $checkout['id'] . "\n";
                $comment .= $this->l('Checkout Status:') . ' ' . $checkout['status'] . "\n";
                $comment .= $this->l('Cart ID:') . ' ' . $customer->id . "\n";

                // Order total
                //$total = (float) $cart->getOrderTotal(true, Cart::BOTH) < $checkout->payData->totalPriceIncludingTax + 2 && (float) $cart->getOrderTotal(true, Cart::BOTH) > $checkout->payData->totalPriceIncludingTax - 2? (float) $cart->getOrderTotal(true, Cart::BOTH) : $checkout->payData->totalPriceIncludingTax;
                //$total = $cart->getOrderTotal(true, Cart::BOTH);
                $total = $checkout['order']['totalPriceIncludingTax'];
                
                PaysonCheckout2::paysonAddLog('Address ID: ' . $address->id);
                PaysonCheckout2::paysonAddLog('Carrier ID: ' . $cart->id_carrier);
                PaysonCheckout2::paysonAddLog('Cart total: ' . $total);

                // Create order
                $this->validateOrder((int) $cart->id, Configuration::get("PAYSONCHECKOUT2_ORDER_STATE_PAID"), $total, $this->displayName, $comment . '<br />', array(), (int) $currency->id, false, $customer->secure_key);

                // Get new order ID
                $orderId = Order::getOrderByCartId((int) ($cart->id));

                // Save order number in DB
                $this->updatePaysonOrderEvent($checkout, $cart->id, (int) $orderId);
                
                // Set transcation ID (purchase ID)
                $order = new Order((int) $orderId);
                $this->setTransactionId($order->reference, $checkout['purchaseId']);

                return $orderId;
            } else {
                PaysonCheckout2::paysonAddLog('PS order already exits.', 2);
            }
        } catch (Exception $ex) {
            PaysonCheckout2::paysonAddLog('PS failed to create order: ' . $ex->getMessage());
        }
        return false;
    }

    public function paysonOrderExists($purchaseid)
    {
        $result = (bool) Db::getInstance()->getValue('SELECT count(*) FROM `' . _DB_PREFIX_ . 'payson_embedded_order` WHERE `purchase_id` = ' . (int) $purchaseid);
        return $result;
    }

    public function getPaysonOrderEventId($cartId)
    {
        $result = Db::getInstance()->getValue('SELECT checkout_id FROM `' . _DB_PREFIX_ . 'payson_embedded_order` WHERE `cart_id` = ' . (int) $cartId . ' ORDER BY `payson_embedded_id` DESC');
        return $result;
    }
    /*
     * @return void
     * @param checkoutId
     * @param $currentCartId
     * @disc The function save the parameters in the database
     */

    public function createPaysonOrderEvent($checkoutId, $cartId = 0)
    {
        $alreadyCreated = $this->getPaysonOrderEventId($cartId);
        if (!isset($alreadyCreated) || (int) $alreadyCreated < 1) {
            Db::getInstance()->insert('payson_embedded_order', array(
                'cart_id' => (int) $cartId,
                'checkout_id' => pSQL($checkoutId),
                'purchase_id' => pSQL($checkoutId),
                'payment_status' => 'created',
                'added' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s')));
        } else {
            Db::getInstance()->update('payson_embedded_order', array(
                'checkout_id' => pSQL($checkoutId),
                'purchase_id' => pSQL($checkoutId),
                'payment_status' => 'created',
                'added' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s')
                    ), 'cart_id = ' . (int) $cartId);
        }
    }
    /*
     * @return void
     * @param $checkout
     * @param $ccartId
     * @param $psOrder
     * @disc The function update the parameters in the database
     */

    public function updatePaysonOrderEvent($checkout, $cartId = 0, $psOrder = 0)
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'payson_embedded_order` SET
            `cart_id` = "' . (int) $cartId . '",';
        if ($psOrder > 0) {
            $sql .= '`order_id` = "' . (int) $psOrder . '",';
        }
        $sql .= '`payment_status` = "' . pSQL($checkout['status']) . '",
            `updated` = NOW(),
            `sender_email` = "' . pSQL($checkout['customer']['email']) . '", 
            `currency_code` = "' . pSQL($checkout['order']['currency']) . '",
            `tracking_id` = "",
            `type` = "embedded",
            `shippingAddress_name` = "' . pSQL($checkout['customer']['firstName']) . '",
            `shippingAddress_lastname` = "' . pSQL($checkout['customer']['lastName']) . '",
            `shippingAddress_street_address` = "' . pSQL($checkout['customer']['street']) . '",
            `shippingAddress_postal_code` = "' . pSQL($checkout['customer']['postalCode']) . '",
            `shippingAddress_city` = "' . pSQL($checkout['customer']['city']) . '",
            `shippingAddress_country` = "' . pSQL($checkout['customer']['countryCode']) . '"
            WHERE `checkout_id` = "' . pSQL($checkout['id']) . '"';

        Db::getInstance()->execute($sql);
    }

    public function setTransactionId($ps_order_ref, $transaction_id)
    {
        Db::getInstance()->update('order_payment', array(
            'transaction_id' => pSQL($transaction_id),
        ), 'order_reference = "'.pSQL($ps_order_ref).'"');
    }
    
    public function getSnippetUrl($snippet)
    {
        $str = "url='";
        $url = explode($str, $snippet);
        $newStr = "'>";
        return explode($newStr, $url[1]);
    }

    private function returnCall($code)
    {
        $this->responseCode($code);
        exit();
    }

    private function responseCode($code)
    {
        return var_dump(http_response_code($code));
    }

    public function languagePayson($language)
    {
        switch (Tools::strtoupper($language)) {
            case 'SE':
            case 'SV':
                return 'SV';
            case 'FI':
                return 'FI';
            case 'DA':
            case 'DK':
                return 'DA';
            case 'NO':
            case 'NB':
                return 'NO';
            case 'CA':
            case 'GL':
            case 'EU':
            case 'ES':
                return 'ES';
            case 'DE':
                return 'DE';
            default:
                return 'EN';
        }
    }
    
    public function validPaysonCurrency($currency)
    {
        switch (Tools::strtoupper($currency)) {
            case 'SEK':
            case 'EUR':
                return true;
            default:
                return false;
        }
    }
    
    /*
     * @return the object of PaysonApi
     * 
     */
    public function getPaysonApiInstance()
    {
        require_once(_PS_MODULE_DIR_ . 'paysoncheckout2/paysonpayments/include.php');
        $apiUrl = \Payson\Payments\Transport\Connector::PROD_BASE_URL;
        $agentId = trim(Configuration::get('PAYSONCHECKOUT2_AGENTID'));
        $apiKey = trim(Configuration::get('PAYSONCHECKOUT2_APIKEY'));
        
        if ((int) Configuration::get('PAYSONCHECKOUT2_MODE') == 1) {
            $apiUrl = \Payson\Payments\Transport\Connector::TEST_BASE_URL;
            if (Tools::strlen($agentId) < 1 && Tools::strlen($apiKey) < 1) {
                $agentId = '4';
                $apiKey = '2acab30d-fe50-426f-90d7-8c60a7eb31d4';
            }
        }
        
        return \Payson\Payments\Transport\Connector::init($agentId, $apiKey, $apiUrl);
    }

    public function addPaysonCustomerPS($cartId, $checkout)
    {
        PaysonCheckout2::paysonAddLog('Create PS Customer - Checkout customer: ' . print_r($checkout['customer'], true));
        
        $cart = new Cart((int) ($cartId));

        $customer = new Customer();
        
        $firstName = str_replace($this->illNameChars, array(' '), (Tools::strlen($checkout['customer']['firstName']) > 31 ? Tools::substr($checkout['customer']['firstName'], 0, 31) : $checkout['customer']['firstName']));
        // $checkout->customer->lastName is null if customer is business
        $lastName = $checkout['customer']['lastName'] != null ? str_replace($this->illNameChars, array(' '), (Tools::strlen($checkout['customer']['lastName']) > 31 ? Tools::substr($checkout['customer']['lastName'], 0, 31) : $checkout['customer']['lastName'])) : '-';
        
        $customer->firstname = $firstName;
        $customer->lastname = $lastName;
        
        $password = Tools::passwdGen(8);
        $customer->is_guest = 0;
        $customer->passwd = Tools::encrypt($password);
        $customer->id_default_group = (int) (Configuration::get('PS_CUSTOMER_GROUP', null, $cart->id_shop));
        $customer->optin = 0;
        $customer->active = 1;
        $customer->email = $checkout['customer']['email'];
        $customer->id_gender = 0;
        $customer->add();
        
        PaysonCheckout2::paysonAddLog('Created PS Customer');
        
        return $customer;
    }

    public function updateCreatePsAddress($countryId, $checkout, $customerId)
    {
        PaysonCheckout2::paysonAddLog('Update or Create PS Address - Checkout customer: ' . print_r($checkout['customer'], true));
        PaysonCheckout2::paysonAddLog('Customer ID: ' . print_r($customerId, true));
        PaysonCheckout2::paysonAddLog('Address ID: ' . print_r(Address::getFirstCustomerAddressId((int) $customerId), true));
        
        $newAddress = false;
        if ((int) Address::getFirstCustomerAddressId((int) $customerId) < 1) {
            $address = new Address();
            $newAddress = true;
        } else {
            $address = new Address(Address::getFirstCustomerAddressId((int) $customerId));
        }

        $firstName = str_replace($this->illNameChars, array(' '), (Tools::strlen($checkout['customer']['firstName']) > 31 ? Tools::substr($checkout['customer']['firstName'], 0, 31) : $checkout['customer']['firstName']));
        // $checkout->customer->lastName is null if customer is business
        $lastName = $checkout['customer']['lastName'] != null ? str_replace($this->illNameChars, array(' '), (Tools::strlen($checkout['customer']['lastName']) > 31 ? Tools::substr($checkout['customer']['lastName'], 0, 31) : $checkout['customer']['lastName'])) : '-';
        
        $address->firstname = $firstName;
        $address->lastname = $lastName;
        
        if ($checkout['customer']['type'] == 'business') {
            $address->company = $firstName;
        }
        
        $address->address1 = $checkout['customer']['street'];
        $address->address2 = '';
        $address->city = $checkout['customer']['city'];
        $address->postcode = $checkout['customer']['postalCode'];
        $address->country = Country::getNameById(Configuration::get('PS_LANG_DEFAULT'), $countryId);
        $address->id_customer = $customerId;
        $address->id_country = $countryId;
        $address->phone = $checkout['customer']['phone'] != null ? $checkout['customer']['phone'] : '-';
        //$address->phone_mobile = $checkout->customer->phone != null ? $checkout->customer->phone : '-';
        //$address->id_state   = (int)$customer->id_state;
        $address->alias = $this->l('Payson account address');
        
        $validation = $address->validateController();

        // Checks address
        if (count($validation) > 0) {
            foreach ($validation as $item) {
                PaysonCheckout2::paysonAddLog($item, 3);
            }
        }

        if ($newAddress == false) {
            $address->update();
        } else {
            $address->add();
        }
        
        PaysonCheckout2::paysonAddLog('Updated/Created PS Address');
        
        return $address;
    }

    public function orderItemsList($cart, $payson, $currency = null)
    {
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

            if (isset($cartProduct['quantity_discount_applies']) && $cartProduct['quantity_discount_applies'] == 1) {
                $payson->discountApplies = 1;
            }

            $my_taxrate = $cartProduct['rate'] / 100;

            $product_price = Tools::ps_round($cartProduct['price_wt'], $cur * _PS_PRICE_DISPLAY_PRECISION_);
            $attributes_small = isset($cartProduct['attributes_small']) ? $cartProduct['attributes_small'] : '';
            $orderitemslist[] = array(
                'name' => $cartProduct['name'] . ' ' . $attributes_small,
                'unitPrice' => $product_price,
                'quantity' => $cartProduct['cart_quantity'],
                'taxrate' => number_format($my_taxrate, 3, '.', ''),
                'reference' => $cartProduct['id_product']
            );
        }

        $cartDiscounts = $cart->getDiscounts();

        $total_shipping_wt = Tools::ps_round($cart->getTotalShippingCost(), $cur * _PS_PRICE_DISPLAY_PRECISION_);
        $total_shipping_wot = 0;
        $carrier = new Carrier($cart->id_carrier, $cart->id_lang);

        $shippingToSubtractFromDiscount = 0;
        if ($total_shipping_wt > 0) {
            $carriertax = Tax::getCarrierTaxRate((int) $carrier->id, $cart->id_address_invoice);
            $carriertax_rate = $carriertax / 100;
            $forward_vat = 1 + $carriertax_rate;
            $total_shipping_wot = $total_shipping_wt / $forward_vat;

            if (!empty($cartDiscounts) && (!empty($cartDiscounts[0]['obj'])) && $cartDiscounts[0]['obj']->free_shipping) {
                $shippingToSubtractFromDiscount = $total_shipping_wt;
            } else {
                $orderitemslist[] = array(
                    'name' => isset($carrier->name) ? $carrier->name : $this->l('Shipping'),
                    'unitPrice' =>  $total_shipping_wt,
                    'quantity' => 1,
                    'taxrate' => number_format($carriertax_rate, 2, '.', ''),
                    'reference' => $this->l('Shipping'),
                    'type' => 'SERVICE',
                );
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
                $discount_tax_rate = Tools::ps_round($lastrate, $cur * _PS_PRICE_DISPLAY_PRECISION_);
            } else {
                $discount_tax_rate = (($value_real / $value_tax_exc) - 1) * 100;

                $discount_tax_rate = Tools::ps_round($discount_tax_rate, $cur * _PS_PRICE_DISPLAY_PRECISION_);
            }

            if ($totalCartValue <= $total_discounts) {
                $value_real = 0;
            }
            $orderitemslist[] = array(
                    'name' => $cart_rule["name"],
                    'unitPrice' => -(Tools::ps_round(($value_real - $shippingToSubtractFromDiscount), $cur * _PS_PRICE_DISPLAY_PRECISION_)),
                    'quantity' => 1,
                    'taxrate' => number_format(($discount_tax_rate * 0.01), 4, '.', ''),
                    'reference' => $this->l('Discount'),
                    'type' => 'DISCOUNT',
                );
            $total_discounts += $value_real;
        }

        if ($cart->gift) {
            $wrappingTemp = number_format(Tools::convertPrice((float) $cart->getGiftWrappingPrice(false), Currency::getCurrencyInstance((int) $cart->id_currency)), Configuration::get('PS_PRICE_DISPLAY_PRECISION'), '.', '') * number_format((((($cart->getOrderTotal(true, Cart::ONLY_WRAPPING) * 100) / $cart->getOrderTotal(false, Cart::ONLY_WRAPPING))) / 100), 2, '.', '');
            $orderitemslist[] = array(
                    'name' => $this->l('Gift Wrapping'),
                    'unitPrice' => $wrappingTemp,
                    'quantity' => 1,
                    'taxrate' => number_format((((($cart->getOrderTotal(true, Cart::ONLY_WRAPPING) * 100) / $cart->getOrderTotal(false, Cart::ONLY_WRAPPING)) - 100) / 100), 2, '.', ''),
                    'reference' => 'wrapping',
                    'type' => 'SERVICE',
                );
        }

        return $orderitemslist;
    }
    
    /*
     * Update Payson order status, ship, cancel or refund
     */

    public function hookActionOrderStatusUpdate($params)
    {
        $order = new Order((int) $params['id_order']);
        
        if ($order->module == 'paysoncheckout2') {
            $newOrderStatus = $params['newOrderStatus'];
            
            $paidName = '';
            $shippedName = '';
            $canceledName = '';
            $refundName = '';
            $orderStates = OrderState::getOrderStates(Configuration::get('PS_LANG_DEFAULT'));
            foreach ($orderStates as $state) {
                if ($state['module_name'] == 'paysoncheckout2' || $state['paid'] == 1) {
                    $paidName = $state['name'];
                }
                if ($state['id_order_state'] == Configuration::get('PAYSON_ORDER_SHIPPED_STATE', null, null, $order->id_shop)) {
                    $shippedName = $state['name'];
                }
                if ($state['id_order_state'] == Configuration::get('PAYSON_ORDER_CANCEL_STATE', null, null, $order->id_shop)) {
                    $canceledName = $state['name'];
                }
                if ($state['id_order_state'] == Configuration::get('PAYSON_ORDER_CREDITED_STATE', null, null, $order->id_shop)) {
                    $refundName = $state['name'];
                }
            }
            
            PaysonCheckout2::paysonAddLog('PS order status changed to ' . $newOrderStatus->name . ' for order: ' . $params['id_order']);
            PaysonCheckout2::paysonAddLog('PS order status to send shipped to Payson: ' . $shippedName);
            PaysonCheckout2::paysonAddLog('PS order status to send canceled to Payson: ' . $canceledName);
            PaysonCheckout2::paysonAddLog('PS order status to send refund to Payson: ' . $refundName);

            if ($newOrderStatus->id == Configuration::get('PAYSON_ORDER_SHIPPED_STATE', null, null, $order->id_shop) || $newOrderStatus->id == Configuration::get('PAYSON_ORDER_CANCEL_STATE', null, null, $order->id_shop) || $newOrderStatus->id == Configuration::get('PAYSON_ORDER_CREDITED_STATE', null, null, $order->id_shop)) {
                $checkout_id = $this->getPaysonOrderEventId($order->id_cart);
                PaysonCheckout2::paysonAddLog('Cart ID from order: ' . $order->id_cart);
                PaysonCheckout2::paysonAddLog('Checkout ID from DB: ' . $checkout_id);

                if (isset($checkout_id) && $checkout_id !== null) {
                    try {
                        $paysonApi = $this->getPaysonApiInstance();
                        $checkoutClient = new \Payson\Payments\CheckoutClient($paysonApi);
                        $checkout = $checkoutClient->get(array('id' => $checkout_id));
                        PaysonCheckout2::paysonAddLog('Payson order current status is: ' . $checkout['status']);
                    } catch (Exception $e) {
                        $this->adminDisplayWarning($this->l('Unable to get Payson order.'));
                        PrestaShopLogger::addLog('Unable to get Payson order when trying to update order status.', 3, null, null, null, true);
                        PrestaShopLogger::addLog('Message: ' . $e->getMessage(), 1, null, null, null, true);
                        return false;
                    }
                    if ($newOrderStatus->id == Configuration::get('PAYSON_ORDER_SHIPPED_STATE', null, null, $order->id_shop)) {
                        if ($checkout['status'] == 'readyToShip') {
                            try {
                                PaysonCheckout2::paysonAddLog('Updating Payson order status to shipped.', 1, null, null, null, true);
                                
                                $checkout['status'] = 'shipped';
                                $updatedCheckout = $checkoutClient->update($checkout);

                                $this->updatePaysonOrderEvent($updatedCheckout, $order->id_cart);
                                PaysonCheckout2::paysonAddLog('Updated Payson order status is: ' . $updatedCheckout['status']);
                            } catch (Exception $e) {
                                $this->adminDisplayWarning($this->l('Failed to send updated order stauts to Payson. Please log in to your PaysonAccount to manually edit order.'));
                                PrestaShopLogger::addLog('Order update fail: ' . $e->getMessage(), 3, null, null, null, true);
                            }
                        } else {
                            $this->adminDisplayWarning($this->l('Payson order must have status Waiting for send before it can be set to Shipped. Please log in to your PaysonAccount to manually edit order.'));
                            PrestaShopLogger::addLog('Failed to update Payson order status to Shipped. Payson order has wrong status: ' . $checkout['status'], 3, null, null, null, true);
                        }
                    }

                    if ($newOrderStatus->id == Configuration::get('PAYSON_ORDER_CANCEL_STATE', null, null, $order->id_shop)) {
                        if ($checkout['status'] == 'readyToShip') {
                            try {
                                PaysonCheckout2::paysonAddLog('Updating Payson order status to canceled.', 1, null, null, null, true);

                                $checkout['status'] = 'canceled';
                                $updatedCheckout = $checkoutClient->update($checkout);

                                $this->updatePaysonOrderEvent($updatedCheckout, $order->id_cart);
                                PaysonCheckout2::paysonAddLog('Updated Payson order status is: ' . $updatedCheckout['status']);
                            } catch (Exception $e) {
                                $this->adminDisplayWarning($this->l('Failed to send updated order stauts to Payson. Please log in to your PaysonAccount to manually edit order.'));
                                PrestaShopLogger::addLog('Order update fail: ' . $e->getMessage(), 3, null, null, null, true);
                            }
                        } else {
                            $this->adminDisplayWarning($this->l('Payson order must have status Waiting for send before it can be set to Canceled. Please log in to your PaysonAccount to manually edit order.'));
                            PrestaShopLogger::addLog('Failed to update Payson order status to Canceled. Payson order has wrong status: ' . $checkout['status'], 3, null, null, null, true);
                        }
                    }
                    
                    if ($newOrderStatus->id == Configuration::get('PAYSON_ORDER_CREDITED_STATE', null, null, $order->id_shop)) {
                        if ($checkout['status'] == 'readyToShip' || $checkout['status'] == 'shipped' || $checkout['status'] == 'paidToAccount') {
                            try {
                                PaysonCheckout2::paysonAddLog('Updating Payson order status to credited.');
                                
                                // Need to ship before refund
                                if ($checkout['status'] == 'readyToShip') {
                                    PaysonCheckout2::paysonAddLog('Updating Payson order status to shipped before refunding.');
                                    $checkout['status'] = 'shipped';
                                    $checkout = $checkoutClient->update($checkout);
                                }

                                foreach ($checkout['order']['items'] as &$item) {
                                    $item['creditedAmount'] = ($item['totalPriceIncludingTax']);
                                }
                                unset($item);
                                
                                $updatedCheckout = $checkoutClient->update($checkout);
                                
                                $this->updatePaysonOrderEvent($updatedCheckout, $order->id_cart);
                                PaysonCheckout2::paysonAddLog('Updated Payson order status is: ' . $updatedCheckout['status']);
                            } catch (Exception $e) {
                                $this->adminDisplayWarning($this->l('Failed to send updated order stauts to Payson. Please log in to your PaysonAccount to manually edit order.'));
                                PrestaShopLogger::addLog('Order update fail: ' . $e->getMessage(), 3, null, null, null, true);
                            }
                        } else {
                            $this->adminDisplayWarning($this->l('Payson order must have status Shipped before it can be set to Credited. Please log in to your PaysonAccount to manually edit order.'));
                            PrestaShopLogger::addLog('Failed to update Payson order status to Credited. Payson order has wrong status: ' . $checkout['status'], 3, null, null, null, true);
                        }
                    }
                } else {
                    $this->adminDisplayWarning($this->l('Failed to send updated order stauts to Payson. Please log in to your PaysonAccount to manually edit order.'));
                    PrestaShopLogger::addLog('Failed to send updated order stauts to Payson. Unable to get checkout ID.', 3, null, null, null, true);
                }
            }
        }
    }
    
    public static function paysonAddLog($message, $severity = 1, $errorCode = null, $objectType = null, $objectId = null, $allowDuplicate = true, $idEmployee = null)
    {
        if (_PCO_LOG_) {
            PrestaShopLogger::addLog($message, $severity, $errorCode, $objectType, $objectId, $allowDuplicate, $idEmployee);
        }
    }
}
