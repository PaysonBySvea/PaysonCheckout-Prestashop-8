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

        if (_PCO_LOG_) {
            Logger::addLog('* ' . __FILE__ . ' -> ' . __METHOD__ . ' *', 1, null, null, null, true);
        }
        if (_PCO_LOG_) {
            Logger::addLog('Query: ' . print_r($_REQUEST, true), 1, null, null, null, true);
        }

        $call = Tools::getValue('call');
        if (_PCO_LOG_) {
            Logger::addLog('Call Type: ' . $call, 1, null, null, null, true);
        }

        if ($call == 'notification') {
            //parent::init();

            require_once(_PS_MODULE_DIR_ . 'paysoncheckout2/paysoncheckout2.php');
            $payson = new PaysonCheckout2();
            $paysonApi = $payson->getPaysonApiInstance();

            $cartId = Tools::getValue('id_cart');

            $checkoutId = Tools::getValue('checkout');
            if (!isset($checkoutId) || $checkoutId == null) {
                $checkoutId = $payson->getPaysonOrderEventId((int) $cartId);
            }

            $checkout = $paysonApi->GetCheckout($checkoutId);

            if (_PCO_LOG_) {
                Logger::addLog('Noti Checkout Status: ' . $checkout->status, 1, null, null, null, true);
            }

            switch ($checkout->status) {
                case 'created':
                    $payson->returnCall(200);
                    break;
                case 'readyToShip':
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
                    Logger::addLog('Noti Unknown Checkout Status: ' . $checkout->status, 2, null, null, null, true);
                    var_dump(http_response_code(200));
                    exit();
            }
        }
    }
}
