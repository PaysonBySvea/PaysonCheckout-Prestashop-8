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

class PaysonCheckout2ValidationModuleFrontController extends ModuleFrontController
{

    public $ssl = false;
    
    public function __construct()
    {
        parent::__construct();

        if (Configuration::get('PS_SSL_ENABLED')) {
            $this->ssl = true;
        }
    }
    
    public function init()
    {
        parent::init();
        PaysonCheckout2::paysonAddLog('* ' . __FILE__ . ' -> ' . __METHOD__ . ' *');

        require_once(_PS_MODULE_DIR_ . 'paysoncheckout2/paysoncheckout2.php');
        $payson = new PaysonCheckout2();
        
        $this->context->cookie->__set('validation_error', null);
        
        $cartId = (int) Tools::getValue('id_cart');
        if (!isset($cartId) || $cartId < 1 || $cartId == null) {
            PrestaShopLogger::addLog('No cart ID in query.', 3);
            $this->context->cookie->__set('validation_error', $this->module->l('Validation message', 'validation') . ': ' . $this->module->l('Missing cart ID.', 'validation'));
            die('reload');
        }
        
        if (isset($this->context->cookie->paysonCheckoutId) && $this->context->cookie->paysonCheckoutId != null) {
            // Get checkout ID from cookie
            $checkoutId = $this->context->cookie->paysonCheckoutId;
            PaysonCheckout2::paysonAddLog('Got checkout ID: ' . $checkoutId . ' from cookie.');
        } else {
            // Get checkout ID from query
            if (Tools::getIsset('checkout') && Tools::getValue('checkout') != null) {
                $checkoutId = Tools::getValue('checkout');
                PaysonCheckout2::paysonAddLog('Got checkout ID: ' . $checkoutId . ' from query.');
            } else {
                // Get checkout ID from DB
                $checkoutId = $payson->getPaysonOrderEventId($cartId);
                if (isset($checkoutId) && $checkoutId != null) {
                    PaysonCheckout2::paysonAddLog('Got checkout ID: ' . $checkoutId . ' from DB.');
                } else {
                    // Unable to get checkout ID
                    PrestaShopLogger::addLog('Unable to get checkout ID, reload page.', 3);
                    $this->context->cookie->__set('validation_error', $this->module->l('Validation message', 'validation') . ': ' . $this->module->l('Missing checkout ID.', 'validation'));
                    die('reload');
                }
            }
        }
        
        $paysonApi = $payson->getPaysonApiInstance();
        $checkoutClient = new \Payson\Payments\CheckoutClient($paysonApi);
        $checkout = $checkoutClient->get(array('id' => $checkoutId));

        PaysonCheckout2::paysonAddLog('Checkout ID: ' . $checkout['id']);
        PaysonCheckout2::paysonAddLog('Cart ID: ' . $cartId);
        PaysonCheckout2::paysonAddLog('Checkout Status: ' . $checkout['status']);

        $cart = new Cart($cartId);

        // Load, add or update customer
        if ($this->context->customer->isLogged() || $this->context->customer->is_guest) {
            PaysonCheckout2::paysonAddLog('Validation - customer is logged in.');
            $customer = new Customer((int) ($this->context->cart->id_customer));
            if ($customer->email != $checkout['customer']['email']) {
                $customer->email = $checkout['customer']['email'];
                $customer->update();
            }
        } else {
            if ((int) Customer::customerExists($checkout['customer']['email'], true, true) > 0) {
                PaysonCheckout2::paysonAddLog('Validation - load existing customer.');
                $customer = new Customer((int) Customer::customerExists($checkout['customer']['email'], true, true));
            } else {
                $customer = $payson->addPaysonCustomerPS($cart->id, $checkout);
            }
        }
        
        // Update or create address
        $address = $payson->updateCreatePsAddress(Country::getByIso($checkout['customer']['countryCode']), $checkout, $customer->id);

        if ((int) $customer->newsletter !== 1 && $this->context->cookie->newsletter_sub == true) {
            $customer->newsletter = 1;
            $customer->update();
            PaysonCheckout2::paysonAddLog('Updated customer newsletter settting.');
        }
        
        $new_delivery_options = array();
        $new_delivery_options[(int) ($address->id)] = $cart->id_carrier . ',';
        $new_delivery_options_serialized = serialize($new_delivery_options);

        PaysonCheckout2::paysonAddLog('Address ID: ' . $address->id);
        PaysonCheckout2::paysonAddLog('Carrier ID: ' . $cart->id_carrier);

        $update_sql = 'UPDATE ' . _DB_PREFIX_ . 'cart ' .
                'SET delivery_option=\'' .
                pSQL($new_delivery_options_serialized) .
                '\' WHERE id_cart=' .
                (int) $cart->id;

        Db::getInstance()->execute($update_sql);

        if ($cart->id_carrier > 0) {
            $cart->delivery_option = $new_delivery_options_serialized;
        } else {
            $cart->delivery_option = '';
        }
        

        $update_sql = 'UPDATE ' . _DB_PREFIX_ . 'cart_product ' .
                'SET id_address_delivery=' . (int) $address->id .
                ' WHERE id_cart=' . (int) $cart->id;

        Db::getInstance()->execute($update_sql);

        // To refresh/clear cart carrier cache
        $cart->getPackageList(true);
        $cart->getDeliveryOptionList(null, true);
        $cart->getDeliveryOption(null, false, false);

        // Set carrier
        $cart->setDeliveryOption($new_delivery_options);

        $cart->secure_key = $customer->secure_key;
        $cart->id_customer = $customer->id;
        if ($cart->id_address_delivery != $cart->id_address_invoice) {
            // Set Payson address as invoice address only, keep delivery address
            $cart->id_address_invoice = $address->id;
        } else {
            // Set Payson address as both invoice address and delivery address
            $cart->id_address_delivery = $address->id;
            $cart->id_address_invoice = $address->id;
        }
        $cart->save();

        $cache_id = 'objectmodel_cart_' . $cart->id . '*';
        Cache::clean($cache_id);
        $cart = new Cart($cart->id);

        //PaysonCheckout2::paysonAddLog('Cart: ' . print_r($cart, true), 1, null, null, null, true);
        PaysonCheckout2::paysonAddLog('Checkout country: ' . $checkout['customer']['countryCode']);

        $checkoutTotal = $checkout['order']['totalPriceIncludingTax'];
        $cartTotal = $cart->getOrderTotal(true, Cart::BOTH);

        PaysonCheckout2::paysonAddLog('Checkout total: ' . $checkoutTotal);
        PaysonCheckout2::paysonAddLog('Cart total: ' . $cartTotal);

        if ($checkoutTotal !== $cartTotal) {
            /*
             * Common reason for ending up with a mismatch between checkout and cart totals is that the customer has selected 
             * a different country in the checkout. Here the cart has been updated to reflect the VAT of the selected country. 
             * Here we update the checkout to match the cart
             */
            $cartCurrency = new Currency($cart->id_currency);

            // Update checkout object
            $checkoutData = $payson->updatePaysonCheckout($checkout, $customer, $cart, $payson, $address, $cartCurrency);
            $checkout = $checkoutClient->update($checkoutData);

            // Update data in Payson order table
            $payson->updatePaysonOrderEvent($checkout, $cart->id);

            PaysonCheckout2::paysonAddLog('Updated checkout to match cart.');
            PaysonCheckout2::paysonAddLog('Failed validation, reload.');
            if (Tools::getIsset('validate_order')) {
                // Validation from JS PaysonEmbeddedAddressChanged event, will reload
                $this->context->cookie->__set('validation_error', $this->module->l('Your order has been updated. Please review the order before proceeding.', 'validation'));
                die('reload');
            }
        }
        PaysonCheckout2::paysonAddLog('Passed validation.');
        if (Tools::getIsset('validate_order')) {
            // Validation from JS PaysonEmbeddedAddressChanged event
            die('passed_validation');
        }
    }
}
