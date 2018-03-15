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

class PaysonCheckout2NotificationsModuleFrontController extends ModuleFrontController
{

    public function init()
    {

        // Give order confirmation a chance to finish
        sleep(2);
        
        if (_PCO_LOG_) {
            Logger::addLog('* ' . __FILE__ . ' -> ' . __METHOD__ . ' *', 1, null, null, null, true);
            Logger::addLog('Notification Query: ' . print_r($_REQUEST, true), 1, null, null, null, true);
        }

        $call = Tools::getValue('call');

        if ($call == 'notification') {
            $cartId = (int) Tools::getValue('id_cart');
            if (!isset($cartId) || $cartId == null) {
                Logger::addLog('Notification No cart ID.', 2, null, null, null, true);
                var_dump(http_response_code(500));
                exit();
            }
            
            require_once(_PS_MODULE_DIR_ . 'paysoncheckout2/paysoncheckout2.php');
            $payson = new PaysonCheckout2();
            $paysonApi = $payson->getPaysonApiInstance();
            
            if (Tools::getIsset('checkout') && Tools::getValue('checkout') != null) {
                // Get checkout ID from query
                $checkoutId = Tools::getValue('checkout');
                if (_PCO_LOG_) {
                    Logger::addLog('Notification Got checkout ID: ' . $checkoutId . ' from query.', 1, null, null, null, true);
                }
            } else {
                // Get checkout ID from DB
                $checkoutId = $payson->getPaysonOrderEventId($cartId);
                if (isset($checkoutId) && $checkoutId != null) {
                    if (_PCO_LOG_) {
                        Logger::addLog('Notification Got checkout ID: ' . $checkoutId . ' from DB.', 1, null, null, null, true);
                    }
                } else {
                    // Unable to get checkout ID
                    Logger::addLog('Notification No checkout ID.', 2, null, null, null, true);
                    var_dump(http_response_code(500));
                    exit();
                }
            }
            
            $checkout = $paysonApi->GetCheckout($checkoutId);

            if (_PCO_LOG_) {
                Logger::addLog('Notification Checkout Status: ' . $checkout->status, 1, null, null, null, true);
            }

            switch ($checkout->status) {
                case 'created':
                    var_dump(http_response_code(200));
                    exit();
                case 'readyToShip':
                    $cart = new Cart($cartId);
                    if (_PCO_LOG_) {
                        Logger::addLog('Notification Cart ID: ' . $cart->id, 1, null, null, null, true);
                    }
                    $orderCreated = false;
                    if ($cart->OrderExists() == false) {
                        // Create PS order
                        $orderCreated = $payson->createOrderPS($cart->id, $checkout);
                        
                        if ($orderCreated == false) {
                            Logger::addLog('Notification Unable to create order.', 3, null, null, null, true);
                            
                            var_dump(http_response_code(500));
                            exit();
                        } else {
                            if (_PCO_LOG_) {
                                Logger::addLog('Notification New order ID: ' . $orderCreated, 1, null, null, null, true);
                            }
                        }
                    } else {
                        if (_PCO_LOG_) {
                            Logger::addLog('Notification Order already created.', 1, null, null, null, true);
                        }
                    }
                    var_dump(http_response_code(200));
                    exit();
                case 'readyToPay':
                    var_dump(http_response_code(200));
                    exit();
                case 'denied':
                    $payson->updatePaysonOrderEvent($checkout, $cartId);
                    var_dump(http_response_code(200));
                    exit();
                case 'canceled':
                    $payson->updatePaysonOrderEvent($checkout, $cartId);
                    var_dump(http_response_code(200));
                    exit();
                case 'paidToAccount':
                    $payson->updatePaysonOrderEvent($checkout, $cartId);
                    var_dump(http_response_code(200));
                    exit();
                case 'expired':
                    $this->context->cookie->__set('paysonCheckoutId', null);
                    $payson->updatePaysonOrderEvent($checkout, $cartId);
                    var_dump(http_response_code(200));
                    exit();
                case 'shipped':
                    $payson->updatePaysonOrderEvent($checkout, $cartId);
                    var_dump(http_response_code(200));
                    exit();
                default:
                    Logger::addLog('Notification Unknown Checkout Status: ' . $checkout->status, 2, null, null, null, true);
                    var_dump(http_response_code(200));
                    exit();
            }
        }
    }
}
