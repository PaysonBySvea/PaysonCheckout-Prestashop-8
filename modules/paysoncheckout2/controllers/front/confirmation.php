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

    public function setMedia()
    {
        parent::setMedia();
        $this->context->controller->addCSS(_MODULE_DIR_ . 'paysoncheckout2/views/css/payson_checkout2.css', 'all');
    }

    public function init()
    {
        parent::init();

        if (_PCO_LOG_) {
            Logger::addLog('* ' . __FILE__ . ' -> ' . __METHOD__ . ' *', 1, null, null, null, true);
            Logger::addLog('Call Type: ' . Tools::getValue('call'), 1, null, null, null, true);
            Logger::addLog('Query: ' . print_r($_REQUEST, true), 1, null, null, null, true);
        }
        
        $cartId = (int) Tools::getValue('id_cart');
        if (!isset($cartId)) {
            $this->context->cookie->__set('validation_error', $this->l('Something went wrong with your cart. Please try again.'));
            if (_PCO_LOG_) {
                Logger::addLog('No cart ID.', 2, null, null, null, true);
            }
            Tools::redirect('index.php?fc=module&module=paysoncheckout2&controller=pconepage');
        }

        require_once(_PS_MODULE_DIR_ . 'paysoncheckout2/paysoncheckout2.php');
        $payson = new PaysonCheckout2();
        
        if (isset($this->context->cookie->paysonCheckoutId) && $this->context->cookie->paysonCheckoutId != null) {
            // Get checkout ID from cookie
            $checkoutId = $this->context->cookie->paysonCheckoutId;
            if (_PCO_LOG_) {
                Logger::addLog('Got checkout ID: ' . $checkoutId . ' from cookie.', 1, null, null, null, true);
            }
        } else {
            // Get checkout ID from query
            if (Tools::getIsset('checkout') && Tools::getValue('checkout') != null) {
                $checkoutId = Tools::getValue('checkout');
                if (_PCO_LOG_) {
                    Logger::addLog('Got checkout ID: ' . $checkoutId . ' from query.', 1, null, null, null, true);
                }
            } else {
                // Get checkout ID from DB
                $checkoutId = $payson->getPaysonOrderEventId($cartId);
                if (isset($checkoutId) && $checkoutId != null) {
                    if (_PCO_LOG_) {
                        Logger::addLog('Got checkout ID: ' . $checkoutId . ' from DB.', 1, null, null, null, true);
                    }
                } else {
                    // Unable to get checkout ID
                    if (_PCO_LOG_) {
                        Logger::addLog('No checkout ID, redirect.', 2, null, null, null, true);
                    }
                    Tools::redirect('index.php?fc=module&module=paysoncheckout2&controller=pconepage');
                }
            }
        }
        
        $paysonApi = $payson->getPaysonApiInstance();
        $checkout = $paysonApi->GetCheckout($checkoutId);
        
        $cart = new Cart($cartId);

        if (!$cart->checkQuantities()) {
            Tools::redirect('index.php?fc=module&module=paysoncheckout2&controller=pconepage');
        }

        if (_PCO_LOG_) {
            Logger::addLog('Cart ID: ' . $cart->id, 1, null, null, null, true);
            Logger::addLog('Cart delivery cost: ' . $cart->getOrderTotal(true, Cart::ONLY_SHIPPING), 1, null, null, null, true);
            Logger::addLog('Cart total: ' . $cart->getOrderTotal(true, Cart::BOTH), 1, null, null, null, true);
            Logger::addLog('Checkout ID: ' . $checkout->id, 1, null, null, null, true);
            Logger::addLog('Checkout total: ' . $checkout->payData->totalPriceIncludingTax, 1, null, null, null, true);
            Logger::addLog('Checkout Status: ' . $checkout->status, 1, null, null, null, true);
        }

        $orderCreated = false;

        // For testing
        //$checkout->status = 'expired';

        switch ($checkout->status) {
            case 'created':
                Tools::redirect('index.php?fc=module&module=paysoncheckout2&controller=pconepage');
                break;
            case 'readyToShip':
                if ($cart->OrderExists() == false) {
                    // Create PS order
                    $orderCreated = $payson->createOrderPS($cart->id, $checkout);
                    if (_PCO_LOG_) {
                        Logger::addLog('New order ID: ' . $orderCreated, 1, null, null, null, true);
                    }
                } else {
                    if (_PCO_LOG_) {
                        Logger::addLog('Order already created.', 1, null, null, null, true);
                    }
                    Tools::redirect('index.php?fc=module&module=paysoncheckout2&controller=pconepage');
                }
                break;
            case 'readyToPay':
                Tools::redirect('index.php?fc=module&module=paysoncheckout2&controller=pconepage');
                break;
            case 'denied':
                $payson->updatePaysonOrderEvent($checkout, $cartId);
                $this->context->cookie->__set('validation_error', $this->l('The payment was denied. Please try using a different payment method.'));
                Tools::redirect('index.php?fc=module&module=paysoncheckout2&controller=pconepage');
                break;
            case 'canceled':
                $payson->updatePaysonOrderEvent($checkout, $cartId);
                $this->context->cookie->__set('validation_error', $this->l('This order has been canceled. Please try again.'));
                Tools::redirect('index.php?fc=module&module=paysoncheckout2&controller=pconepage');
                break;
            case 'expired':
                $this->context->cookie->__set('paysonCheckoutId', null);
                $payson->updatePaysonOrderEvent($checkout, $cartId);
                $this->context->cookie->__set('validation_error', $this->l('This order has expired. Please try again.'));
                Tools::redirect('index.php?fc=module&module=paysoncheckout2&controller=pconepage');
                break;
            case 'shipped':
                if (_PCO_LOG_) {
                    Logger::addLog('Got Checkout Status: shipped.', 1, null, null, null, true);
                }
                $payson->updatePaysonOrderEvent($checkout, $cartId);
                Tools::redirect('index.php?fc=module&module=paysoncheckout2&controller=pconepage');
                break;
            default:
                Logger::addLog('Unknown Checkout Status: ' . $checkout->status, 2, null, null, null, true);
                $this->context->cookie->__set('validation_error', $this->l('Unable to complete order.'));
                Tools::redirect('index.php?fc=module&module=paysoncheckout2&controller=pconepage');
        }
        
        // Delete checkout id cookie
        $this->context->cookie->__set('paysonCheckoutId', null);
        
        $this->context->smarty->assign(array('payson_checkout' => $checkout->snippet));
        
        $this->setTemplate('module:paysoncheckout2/views/templates/front/payment_return.tpl');
    }
}
