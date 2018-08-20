<?php
/**
 * 2018 Payson AB
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 *  @author    Payson AB <integration@payson.se>
 *  @copyright 2018 Payson AB
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class PaysonCheckout2ConfirmationModuleFrontController extends ModuleFrontController
{

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
        $this->context->controller->addCSS(_MODULE_DIR_ . 'paysoncheckout2/views/css/payson_checkout2.css', 'all');
        $this->addJS(_MODULE_DIR_ . 'paysoncheckout2/views/js/payson_checkout2_confirmation.js');
    }

    public function init()
    {
        parent::init();

        PaysonCheckout2::paysonAddLog('* ' . __FILE__ . ' -> ' . __METHOD__ . ' *');
        PaysonCheckout2::paysonAddLog('Query: ' . print_r($_REQUEST, true));
        
        try {
            require_once(_PS_MODULE_DIR_ . 'paysoncheckout2/paysoncheckout2.php');
            $payson = new PaysonCheckout2();
            
            $cartId = (int) Tools::getValue('id_cart');
            if (!isset($cartId)) {
                throw new Exception($this->module->l('Unable to show confirmation.', 'confirmation') . ' ' . $this->module->l('Missing cart ID.', 'confirmation'));
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
                        throw new Exception($this->module->l('Unable to show confirmation.', 'confirmation') . ' ' . $this->module->l('Missing checkout ID.', 'confirmation'));
                    }
                }
            }

            $paysonApi = $payson->getPaysonApiInstance();
            $checkout = $paysonApi->GetCheckout($checkoutId);

            $cart = new Cart($cartId);

            PaysonCheckout2::paysonAddLog('Cart ID: ' . $cart->id);
            PaysonCheckout2::paysonAddLog('Checkout ID: ' . $checkout->id);
            PaysonCheckout2::paysonAddLog('Checkout Status: ' . $checkout->status);

            if ($checkout->status == 'readyToShip' && !$cart->checkQuantities()) {
                PaysonCheckout2::paysonAddLog('A product has run out of stock between checkout and confirmation.');
                // Delete checkout id cookie, force a new chckout
                $this->context->cookie->__set('paysonCheckoutId', null);
                // Set status canceled on Payson order
                $paysonApi->CancelCheckout($checkout);
                // Redirect to checkout
                Tools::redirect('index.php?fc=module&module=paysoncheckout2&controller=pconepage');
            }
            
            $newOrderId = false;
            $redirect = false;

            // For testing
            //$checkout->status = 'denied';

            switch ($checkout->status) {
                case 'readyToShip':
                    if ($cart->OrderExists() == false) {
                        // Create PS order
                        $newOrderId = $payson->createOrderPS($cart->id, $checkout);
                        PaysonCheckout2::paysonAddLog('New order ID: ' . $newOrderId);
                    } else {
                        PaysonCheckout2::paysonAddLog('Order already created.');
                        $redirect = 'index.php';
                    }
                    break;
                case 'created':
                case 'readyToPay':
                case 'denied':
                    $redirect = 'index.php?fc=module&module=paysoncheckout2&controller=pconepage';
                    $this->context->cookie->__set('validation_error', $this->module->l('Payment status was', 'confirmation') . ' "' . $checkout->status . '".');
                    break;
                case 'canceled':
                case 'expired':
                case 'shipped':
                    throw new Exception($this->module->l('Unable to show confirmation.', 'confirmation') . ' ' . $this->module->l('Payment status was', 'confirmation') . ' "' . $checkout->status . '".');
                default:
                    $redirect = 'index.php?fc=module&module=paysoncheckout2&controller=pconepage';
                    $this->context->cookie->__set('validation_error', $this->module->l('Payment status was', 'confirmation') . ' "' . $checkout->status . '".');
            }

            // Delete checkout id cookie
            $this->context->cookie->__set('paysonCheckoutId', null);

            if ($redirect !== false) {
                $payson->updatePaysonOrderEvent($checkout, $cartId);
                PaysonCheckout2::paysonAddLog('Checkout Status: ' . $checkout->status);
                PaysonCheckout2::paysonAddLog('Unable to display confirmation, redirecting to: ' . $redirect);
                Tools::redirect($redirect);
            }

            $order = new Order((int) $newOrderId);
            $this->context->cookie->__set('id_customer', $order->id_customer);

            $this->context->smarty->assign('payson_checkout', $checkout->snippet);
            $this->context->smarty->assign('HOOK_DISPLAY_ORDER_CONFIRMATION', Hook::exec('displayOrderConfirmation', array('order' => $order)));

            $this->displayConfirmation();
        } catch (Exception $ex) {
            // Log error message
            PaysonCheckout2::paysonAddLog('Checkout error: ' . $ex->getMessage(), 2);

            // Delete checkout id cookie
            $this->context->cookie->__set('paysonCheckoutId', null);
            
            // Replace checkout snippet with error message
            $this->context->smarty->assign('payson_checkout', $ex->getMessage());

            // Show confirmation
            $this->displayConfirmation();
        }
    }
    
    protected function displayConfirmation()
    {
        $this->setTemplate('module:paysoncheckout2/views/templates/front/payment_return.tpl');
    }
}
