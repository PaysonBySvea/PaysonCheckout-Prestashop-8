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

use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

class Ps_PaysonCheckout2PcOnePageModuleFrontController extends ModuleFrontController
{

    public $display_column_left = false;
    public $display_column_right = false;
    public $ssl = false;
    
    public function __construct()
    {
        parent::__construct();

        if (Configuration::get('PS_SSL_ENABLED')) {
            $this->ssl = true;
        }
    }
    
    public function setMedia()
    {
        parent::setMedia();
        $this->context->controller->addCSS(_MODULE_DIR_ . 'ps_paysoncheckout2/views/css/payson_checkout2.css', 'all');
        Media::addJsDef(array('acceptTermsMessage' => $this->module->l('You must agree to the terms of service before continuing.', 'pconepage')));
        $this->addJS(_MODULE_DIR_ . 'ps_paysoncheckout2/views/js/payson_checkout2.js');
    }

    public function postProcess()
    {
        // Gift wrapping
        if (Tools::getIsset('gift_message')) {
            Ps_PaysonCheckout2::paysonAddLog('Start to save gift wrapping.');
            Ps_PaysonCheckout2::paysonAddLog('Gift is: ' . (int) (Tools::getValue('gift')));
            $this->context->cart->gift = (int) (Tools::getValue('gift'));
            $gift_message = Tools::getValue('gift_message');
            $gift_error = '';
            if (!Validate::isMessage($gift_message)) {
                $gift_error = $this->module->l('Invalid gift message', 'pconepage');
            } else {
                $this->context->cart->gift_message = strip_tags($gift_message);
            }
            $this->context->cart->update();
            $this->context->smarty->assign('gift_error', $gift_error);
        }

        // Order message
        if (Tools::getIsset('message')) {
            Ps_PaysonCheckout2::paysonAddLog('Start to save message: ' . Tools::getValue('message'));
            $messageContent = Tools::getValue('message');
            $message_result = $this->updateMessage($messageContent, $this->context->cart);
            if (!$message_result) {
                $this->context->smarty->assign('gift_error', $this->module->l('Invalid message', 'pconepage'));
                Ps_PaysonCheckout2::paysonAddLog('Unable to save message.');
                //die('error');
            }
            //die('success');
        }

        // Discounts, coupons
        if (CartRule::isFeatureActive()) {
            $vouchererrors = '';
            if (Tools::isSubmit('submitAddDiscount')) {
                $code = trim(Tools::getValue('discount_name'));
                $code = Tools::purifyHTML($code);
                if (!($code)) {
                    $vouchererrors = $this->module->l('You must enter a voucher code', 'pconepage');
                } elseif (!Validate::isCleanHtml($code)) {
                    $vouchererrors = $this->module->l('Voucher code invalid', 'pconepage');
                } else {
                    if (($cartRule = new CartRule(CartRule::getIdByCode($code))) &&
                            Validate::isLoadedObject($cartRule)) {
                        if ($error = $cartRule->checkValidity($this->context, false, true)) {
                            $vouchererrors = $error;
                        } else {
                            $this->context->cart->addCartRule($cartRule->id);
                            $url = 'index.php?fc=module&module=ps_paysoncheckout2&controller=pconepage';
                            Tools::redirect($url);
                        }
                    } else {
                        $vouchererrors = $this->module->l('This voucher does not exists', 'pconepage');
                    }
                }
                $this->context->smarty->assign(array(
                    'vouchererrors' => html_entity_decode($vouchererrors),
                    'discount_name' => Tools::safeOutput($code),
                ));
            } elseif (($id_cart_rule = (int) Tools::getValue('deleteDiscount')) &&
                    Validate::isUnsignedId($id_cart_rule)) {
                $this->context->cart->removeCartRule($id_cart_rule);
                $url = 'index.php?fc=module&module=ps_paysoncheckout2&controller=pconepage';
                Tools::redirect($url);
            }
        }

        // Handle changed carrier
        if (Tools::getIsset('delivery_option')) {
            $newDeliveryOption = Tools::getValue('delivery_option');

            Ps_PaysonCheckout2::paysonAddLog('Updating delivery option: ' . print_r($newDeliveryOption, true));

            if ($this->validateDeliveryOption($newDeliveryOption)) {
                if ((int) $this->context->cart->id_address_delivery > 0) {
                    // Use customer address ID
                    $newDeliveryOptionId = $newDeliveryOption[0];
                    $newDeliveryOption = array();
                    $newDeliveryOption[(int) ($this->context->cart->id_address_delivery)] = $newDeliveryOptionId;
                }
                $this->context->cart->setDeliveryOption($newDeliveryOption);

                Ps_PaysonCheckout2::paysonAddLog('Carrier ID: ' . $this->context->cart->id_carrier);
                Ps_PaysonCheckout2::paysonAddLog('Addres ID: ' . $this->context->cart->id_address_delivery);
                Ps_PaysonCheckout2::paysonAddLog('Updated delivery option: ' . print_r($newDeliveryOption, true));
            }

            if (!$this->context->cart->update()) {
                $this->context->smarty->assign(array('vouchererrors' => $this->module->l('Could not save carrier selection', 'pconepage'),));
                Ps_PaysonCheckout2::paysonAddLog('Unable to update delivey option.');
            }

            // See if rules apply here
            CartRule::autoRemoveFromCart($this->context);
            CartRule::autoAddToCart($this->context);
        }
    
        // Refresh carriers
        if (Tools::getIsset('refresh_carriers')) {
            Ps_PaysonCheckout2::paysonAddLog('Get carrier list.');
           
            $checkoutSession = $this->getCheckoutSession();
            $carriers = $checkoutSession->getDeliveryOptions();

            Ps_PaysonCheckout2::paysonAddLog('Carrier list: ' . print_r($carriers, true));
            
            if (is_array($carriers) && count($carriers) > 0) {
                $carrier_prices = array();
                foreach ($carriers as $carrier) {
                    $carrier_prices[] = array('id' => $carrier['id'], 'price' => Tools::displayPrice($carrier['price_with_tax']));
                }
                Ps_PaysonCheckout2::paysonAddLog('Carrier prices: ' . print_r($carrier_prices, true));
                die(json_encode($carrier_prices));
            }
            die('no_update');
        }
         
        // Newsletter subscription
        if (Tools::getIsset('newsletter_sub')) {
            $val = Tools::getValue('newsletter_sub');
            $this->context->cookie->__set('newsletter_sub', $val);
            die('success');
        }
    }
    
    public function initContent()
    {   
        parent::initContent();
        Ps_PaysonCheckout2::paysonAddLog('* ' . __FILE__ . ' -> ' . __METHOD__ . ' *');

        $errMess = false;
        try {
            // Class PaysonCheckout2
            require_once(_PS_MODULE_DIR_ . 'ps_paysoncheckout2/ps_paysoncheckout2.php');
            $payson = new Ps_PaysonCheckout2();
            
            $this->context->smarty->assign('custom_css', Configuration::get('PAYSONCHECKOUT2_USE_CUSTOM_CSS') == 1 ? trim(Configuration::get('PAYSONCHECKOUT2_CUSTOM_CSS')) : '');

            if (isset($this->context->cart) && $this->context->cart->nbProducts() > 0) {
                // Set default delivery option on cart if needed
                if (!$this->context->cart->getDeliveryOption(null, true)) {
                    $this->context->cart->setDeliveryOption($this->context->cart->getDeliveryOption());
                    $this->context->cart->save();
                    Ps_PaysonCheckout2::paysonAddLog('Added default delivery: ' . print_r($this->context->cart->getDeliveryOption(), true));
                }

                // Check if rules apply
                CartRule::autoRemoveFromCart($this->context);
                CartRule::autoAddToCart($this->context);

                // Get cart currency
                $cartCurrency = new Currency($this->context->cart->id_currency);
                Ps_PaysonCheckout2::paysonAddLog('Cart Currency: ' . $cartCurrency->iso_code);

                // Check cart currency
                if (!$payson->validPaysonCurrency($cartCurrency->iso_code)) {
                    $errMess = $this->module->l('Unsupported currency. Please use SEK or EUR.', 'pconepage');
                }

                // Check cart products stock levels
                $cartQuantities = $this->context->cart->checkQuantities(true);
                if ($cartQuantities !== true) {
                    $errMess = $this->module->l('An item', 'pconepage') . ' (' . $cartQuantities['name'] . ') ' . $this->module->l('in your cart is no longer available in this quantity. You cannot proceed with your order until the quantity is adjusted.', 'pconepage');
                }

                // Check minimun order value
                $min_purchase = Tools::convertPrice((float) Configuration::get('PS_PURCHASE_MINIMUM'), $cartCurrency);
                if ($this->context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS) < $min_purchase) {
                    $errMess = $this->module->l('This order does not meet the requirement for minimum order value.', 'pconepage');
                }

                // Reset cookies
                $this->context->cookie->__set('alreadyLoggedIn', null);
                $this->context->cookie->__set('CreatedCustomer', null);
               
                // Check customer and address
                if ($this->context->customer->isLogged() || $this->context->customer->is_guest) {
                    Ps_PaysonCheckout2::paysonAddLog($this->context->customer->is_guest == 1 ? 'Customer is: Guest' : 'Customer is: Logged in');
                    // Customer is logged in or has entered guest address information, we'll use this information
                    $customer = new Customer((int) ($this->context->cart->id_customer));
                    $address = new Address((int) ($this->context->cart->id_address_invoice));

                    // Set cookie to indicate that this customer was logged in before checkout
                    $this->context->cookie->__set('alreadyLoggedIn', 1);
                    
                    if ($address->id_state) {
                        $state = new State((int) ($address->id_state));
                    }

                    if (!Validate::isLoadedObject($customer)) {
                        $errMess = $this->module->l('Unable to validate customer. Please try again.', 'pconepage');
                    }
                } else {
                    Ps_PaysonCheckout2::paysonAddLog('Customer is not Guest or Logged in');
                    // Create new customer and address
                    $address = new Address();
                    $customer = new Customer();
                }

                Ps_PaysonCheckout2::paysonAddLog('Refresh cart summmary');
                // Refresh cart summary
                $this->context->cart->getSummaryDetails();
                $this->assignSummaryInformations();

                Ps_PaysonCheckout2::paysonAddLog('Get delivery options');
                // Get delivery options
                $checkoutSession = $this->getCheckoutSession();
                $delivery_options = $checkoutSession->getDeliveryOptions();
                $delivery_options_finder_core = new DeliveryOptionsFinder($this->context, $this->getTranslator(), $this->objectPresenter, new PriceFormatter());
                $delivery_option = $delivery_options_finder_core->getSelectedDeliveryOption();

                Ps_PaysonCheckout2::paysonAddLog('Check free shipping cart rule');
                // Free shipping cart rule
                $free_shipping = false;
                foreach ($this->context->cart->getCartRules() as $rule) {
                    if ($rule['free_shipping']) {
                        $free_shipping = true;
                        break;
                    }
                }
                
                Ps_PaysonCheckout2::paysonAddLog('Check free shipping based on order total');
                // Free shipping based on order total
                $configuration = Configuration::getMultiple(array('PS_SHIPPING_FREE_PRICE', 'PS_SHIPPING_FREE_WEIGHT'));
                if (isset($configuration['PS_SHIPPING_FREE_PRICE']) && $configuration['PS_SHIPPING_FREE_PRICE'] > 0) {
                    $free_fees_price = Tools::convertPrice((float) $configuration['PS_SHIPPING_FREE_PRICE'], Currency::getCurrencyInstance((int) $this->context->cart->id_currency));
                    $orderTotalwithDiscounts = $this->context->cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING, null, null, false);
                    $left_to_get_free_shipping = ($free_fees_price - $orderTotalwithDiscounts);
                    $this->context->smarty->assign('left_to_get_free_shipping', $left_to_get_free_shipping);
                    $this->context->smarty->assign('free_shipping_price_amount', $free_fees_price);
                }

                Ps_PaysonCheckout2::paysonAddLog('Check free shipping based on weight');
                // Free shipping based on order weight
                if (isset($configuration['PS_SHIPPING_FREE_WEIGHT']) && $configuration['PS_SHIPPING_FREE_WEIGHT'] > 0) {
                    $free_fees_weight = $configuration['PS_SHIPPING_FREE_WEIGHT'];
                    $total_weight = $this->context->cart->getTotalWeight();
                    $left_to_get_free_shipping_weight = $free_fees_weight - $total_weight;
                    $this->context->smarty->assign('left_to_get_free_shipping_weight', $left_to_get_free_shipping_weight);
                }

                Ps_PaysonCheckout2::paysonAddLog('Reset error message');
                // Reset error messages
                $this->context->smarty->assign('payson_errors', null);
                // Check for validation/confirmation errors
                if (isset($this->context->cookie->validation_error) && $this->context->cookie->validation_error != null) {
                    Ps_PaysonCheckout2::paysonAddLog('Validation or confirmation error message: ' . $this->context->cookie->validation_error);
                    //$this->context->smarty->assign('payson_errors', $this->context->cookie->validation_error);
                    //$errMess = $this->context->cookie->validation_error;
                    // Delete old messages
                    $this->context->cookie->__set('validation_error', null);
                }

                Ps_PaysonCheckout2::paysonAddLog('Find terms to approve');
                // Terms to approve
                $conditionsToApproveFinder = new ConditionsToApproveFinder(
                    $this->context,
                    $this->getTranslator()
                );

                Ps_PaysonCheckout2::paysonAddLog('Assign smart vars');
                // Assign smarty tpl variables
                $this->context->smarty->assign(array(
                    'discounts' => $this->context->cart->getCartRules(),
                    'cart_is_empty' => false,
                    'gift' => $this->context->cart->gift,
                    'gift_message' => $this->context->cart->gift_message,
                    'giftAllowed' => (int) (Configuration::get('PS_GIFT_WRAPPING')),
                    'gift_wrapping_price' => Tools::convertPrice($this->context->cart->getGiftWrappingPrice(true), $cartCurrency),
                    'message' => Message::getMessageByCartId((int) ($this->context->cart->id)),
                    'id_cart' => $this->context->cart->id,
                    'controllername' => 'pconepage',
                    'free_shipping' => $free_shipping,
                    'id_lang' => $this->context->language->id,
                    'token_cart' => $this->context->cart->secure_key,
                    'id_address' => $this->context->cart->id_address_delivery,
                    'delivery_options' => $delivery_options,
                    'delivery_option' => $delivery_option,
                    'PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS' => (int) Configuration::get('PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS'),
                    'PAYSONCHECKOUT2_SHOW_TERMS' => (int) Configuration::get('PAYSONCHECKOUT2_SHOW_TERMS'),
                    'PAYSONCHECKOUT2_NEWSLETTER' => (int) Configuration::get('PAYSONCHECKOUT2_NEWSLETTER'),
                    'pcoUrl' => $this->context->link->getModuleLink('ps_paysoncheckout2', 'pconepage', array(), true),
                    'validateUrl' => $this->context->link->getModuleLink('ps_paysoncheckout2', 'validation', array(), true),
                    'conditions_to_approve' => $conditionsToApproveFinder->getConditionsToApproveForTemplate(),
                    'newsletter_optin_text' => $this->module->l('Sign up for our newsletter', 'pconepage'),
                    'hookDisplayBeforeCarrier' => Hook::exec('displayBeforeCarrier'),
                    'hookDisplayAfterCarrier' => Hook::exec('displayAfterCarrier'),
                    'pco_checkout_id' => '',
                ));

                Ps_PaysonCheckout2::paysonAddLog('Check for error');
                // Check for error and exit if any
                if ($errMess !== false) {
                    throw new Exception($errMess);
                }

                Ps_PaysonCheckout2::paysonAddLog('Start Payson');
                Ps_PaysonCheckout2::paysonAddLog('Get if pco_update is set');
                if (Tools::getIsset('pco_update')) {
                    Ps_PaysonCheckout2::paysonAddLog('Query pco_update from Tools is: ' . Tools::getValue('pco_update'));
                }
                $checkoutSnippet = '';
                $checkoutId = '';
                if (Tools::getIsset('pco_update')) {
                    Ps_PaysonCheckout2::paysonAddLog('Start Payson API');
                    // Initiate Payson API
                    $paysonApi = $payson->getPaysonApiInstance();
                    Ps_PaysonCheckout2::paysonAddLog('Payson API initiated. Agent ID: ' . $paysonApi->getAgentId());

                    $getCheckout = $this->getCheckout($payson, $paysonApi, $customer, $cartCurrency, $address);
                    $checkout = $getCheckout['checkout'];
                    $isNewCheckout = $getCheckout['newcheckout'];

                    if (!$isNewCheckout) {
                        // Check if we need to create a new checkout if language or currency differs between cart and checkout or if it expired
                        if (($checkout['status'] == 'expired') || !$payson->checkCurrencyName($cartCurrency->iso_code, $checkout['order']['currency']) || ($payson->languagePayson(Language::getIsoById($this->context->language->id)) !== $payson->languagePayson($checkout['gui']['locale']))) {
                            $this->context->cookie->__set('paysonCheckoutId', null);
                            $getCheckout = $this->getCheckout($payson, $paysonApi, $customer, $cartCurrency, $address);
                            $checkout = $getCheckout['checkout'];
                            $isNewCheckout = $getCheckout['newcheckout'];
                        } else {
                            if ($payson->canUpdate($checkout['status'])) {
                                // Update checkout
                                $checkoutData = $payson->updatePaysonCheckout($checkout, $customer, $this->context->cart, $payson, $address, $cartCurrency);
                                $checkoutClient = new \Payson\Payments\CheckoutClient($paysonApi);
                                $checkout = $checkoutClient->update($checkoutData);
                            } else {
                                $this->context->cookie->__set('paysonCheckoutId', null);
                            }
                        }
                    }
                    $checkoutSnippet = $checkout['snippet'];
                    $checkoutId = $checkout['id'];
                }

                // Assign some more smarty tpl variables
                $this->context->smarty->assign(array(
                    'pco_checkout_id' => $checkoutId,
                    'payson_checkout' => $checkoutSnippet,
                ));
                
                if (Tools::getIsset('pco_update')) {
                    die($checkoutSnippet);
                }
            
                // Show checkout
                $this->displayCheckout();
            } else {
                // No cart or empty cart
                $errMess = $this->module->l('Your cart is empty.', 'pconepage');
                throw new Exception($errMess);
            }
        } catch (Exception $ex) {
            // Log error message
            Ps_PaysonCheckout2::paysonAddLog('Checkout error: ' . $ex->getMessage(), 2);

            // Replace checkout snippet with error message
            $this->context->smarty->assign('payson_checkout', $ex->getMessage());
            
            // Delete checkout id cookie, force a new checkout
            $this->context->cookie->__set('paysonCheckoutId', null);

            // If AJAX return error message
            if (Tools::getIsset('pco_update')) {
                die($ex->getMessage());
            }

            // Show checkout
            $this->displayCheckout();
        }
    }

    protected function getCheckout($payson, $paysonApi, $customer, $cartCurrency, $address)
    {
        $checkoutClient = new \Payson\Payments\CheckoutClient($paysonApi);
        // Get or create checkout
        $newCheckout = false;
        $checkoutId = $this->context->cookie->paysonCheckoutId;
        if ($checkoutId && $checkoutId != null) {
            // Get existing checkout
            $checkout = $checkoutClient->get(array('id' => $checkoutId));
            Ps_PaysonCheckout2::paysonAddLog('Got existing checkout with ID: ' . $checkout['id']);
        } else {
            // Create a new checkout
            $checkoutData = $payson->createPaysonCheckout($customer, $this->context->cart, $payson, $cartCurrency, $this->context->language->id, $address);
            $checkout = $checkoutClient->create($checkoutData);
            // Save checkout ID in cookie
            $this->context->cookie->__set('paysonCheckoutId', $checkout['id']);
            // Save data in Payson order table
            $payson->createPaysonOrderEvent($checkout['id'], $this->context->cart->id);
            Ps_PaysonCheckout2::paysonAddLog('Created new checkout with ID: ' . $checkout['id']);
            $newCheckout = true;
        }
        
        return array('checkout' => $checkout, 'newcheckout' => $newCheckout);
    }
    
    protected function displayCheckout()
    {
        if ((int) Configuration::get('PAYSONCHECKOUT2_ONE_PAGE') == 1 && Tools::getValue('ref') != 'opm' && Tools::getValue('call') != 'paymentreturn') {
            Ps_PaysonCheckout2::paysonAddLog('Selected template: ' . Configuration::get('PAYSONCHECKOUT2_TEMPLATE'));
            $this->setTemplate('module:ps_paysoncheckout2/views/templates/front/' . Configuration::get('PAYSONCHECKOUT2_TEMPLATE') . '.tpl');
        } else {
            $this->setTemplate('module:ps_paysoncheckout2/views/templates/front/payment.tpl');
        }
    }
    
    protected function getCheckoutSession()
    {
        $deliveryOptionsFinder = new DeliveryOptionsFinder($this->context, $this->getTranslator(), $this->objectPresenter, new PriceFormatter());
        $session = new CheckoutSession($this->context, $deliveryOptionsFinder);

        return $session;
    }

    protected function validateDeliveryOption($delivery_option)
    {
        if (!is_array($delivery_option)) {
            return false;
        }

        foreach ($delivery_option as $option) {
            if (!preg_match('/(\d+,)?\d+/', $option)) {
                return false;
            }
        }

        return true;
    }

    protected function updateMessage($messageContent, $cart)
    {
        if ($messageContent) {
            if (!Validate::isMessage($messageContent)) {
                return false;
            } elseif ($oldMessage = Message::getMessageByCartId((int) ($cart->id))) {
                $message = new Message((int) ($oldMessage['id_message']));
                $message->message = $messageContent;
                $message->update();
            } else {
                $message = new Message();
                $message->message = $messageContent;
                $message->id_cart = (int) ($cart->id);
                $message->id_customer = (int) ($cart->id_customer);
                $message->add();
            }
        } else {
            if ($oldMessage = Message::getMessageByCartId((int) ($cart->id))) {
                $message = new Message((int) ($oldMessage['id_message']));
                $message->delete();
            }
        }

        return true;
    }

    protected function assignSummaryInformations()
    {
        $summary = $this->context->cart->getSummaryDetails();
        $customizedDatas = Product::getAllCustomizedDatas($this->context->cart->id);

        // override customization tax rate with real tax (tax rules)
        if ($customizedDatas) {
            foreach ($summary['products'] as &$productUpdate) {
                if (isset($productUpdate['id_product'])) {
                    $productId = (int) $productUpdate['id_product'];
                } else {
                    $productId = (int) $productUpdate['product_id'];
                }

                if (isset($productUpdate['id_product_attribute'])) {
                    $productAttributeId = (int) $productUpdate['id_product_attribute'];
                } else {
                    $productAttributeId = (int) $productUpdate['product_attribute_id'];
                }

                if (isset($customizedDatas[$productId][$productAttributeId])) {
                    $productUpdate['tax_rate'] = Tax::getProductTaxRate($productId, $this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
                }
            }
            Product::addCustomizationPrice($summary['products'], $customizedDatas);
        }

        $cart_product_context = Context::getContext()->cloneContext();
        foreach ($summary['products'] as $key => &$product) {
            // For older themes
            $product['quantity'] = $product['cart_quantity'];

            if ($cart_product_context->shop->id != $product['id_shop']) {
                $cart_product_context->shop = new Shop((int) $product['id_shop']);
            }
            $specific_price_output = null;
            $product['price_without_specific_price'] = Product::getPriceStatic($product['id_product'], !Product::getTaxCalculationMethod(), $product['id_product_attribute'], 2, null, false, false, 1, false, null, null, null, $specific_price_output, true, true, $cart_product_context);

            if (Product::getTaxCalculationMethod()) {
                $product['is_discounted'] = $product['price_without_specific_price'] != $product['price'];
            } else {
                $product['is_discounted'] = $product['price_without_specific_price'] != $product['price_wt'];
            }
        }

        // Get available cart rules and unset the cart rules already in the cart
        $available_cart_rules = CartRule::getCustomerCartRules($this->context->language->id, (isset($this->context->customer->id) ? $this->context->customer->id : 0), true, true, true, $this->context->cart);

        $cart_cart_rules = $this->context->cart->getCartRules();
        foreach ($available_cart_rules as $key => $available_cart_rule) {
            if (!$available_cart_rule['highlight'] || strpos($available_cart_rule['code'], 'BO_ORDER_') === 0) {
                unset($available_cart_rules[$key]);
                continue;
            }
            foreach ($cart_cart_rules as $cart_cart_rule) {
                if ($available_cart_rule['id_cart_rule'] == $cart_cart_rule['id_cart_rule']) {
                    unset($available_cart_rules[$key]);
                    continue 2;
                }
            }
        }

        $show_option_allow_separate_package = (!$this->context->cart->isAllProductsInStock(true) &&
                Configuration::get('PS_SHIP_WHEN_AVAILABLE'));

        $this->context->smarty->assign($summary);
        $this->context->smarty->assign(array(
            'token_cart' => Tools::getToken(false),
            'isVirtualCart' => $this->context->cart->isVirtualCart(),
            'productNumber' => $this->context->cart->nbProducts(),
            'voucherAllowed' => CartRule::isFeatureActive(),
            'shippingCost' => $this->context->cart->getOrderTotal(true, Cart::ONLY_SHIPPING),
            'shippingCostTaxExc' => $this->context->cart->getOrderTotal(false, Cart::ONLY_SHIPPING),
            'customizedDatas' => $customizedDatas,
            'CUSTOMIZE_FILE' => Product::CUSTOMIZE_FILE,
            'CUSTOMIZE_TEXTFIELD' => Product::CUSTOMIZE_TEXTFIELD,
            'lastProductAdded' => $this->context->cart->getLastProduct(),
            'displayVouchers' => $available_cart_rules,
            'advanced_payment_api' => true,
            'currencySign' => $this->context->currency->sign,
            'currencyRate' => $this->context->currency->conversion_rate,
            'currencyFormat' => $this->context->currency->format,
            'currencyBlank' => $this->context->currency->blank,
            'show_option_allow_separate_package' => $show_option_allow_separate_package,
            'smallSize' => Image::getSize(ImageType::getFormattedName('small')),
        ));

        $this->context->smarty->assign(array(
            'HOOK_SHOPPING_CART' => Hook::exec('displayShoppingCartFooter', $summary),
            'HOOK_SHOPPING_CART_EXTRA' => Hook::exec('displayShoppingCart', $summary),
        ));
    }
}
