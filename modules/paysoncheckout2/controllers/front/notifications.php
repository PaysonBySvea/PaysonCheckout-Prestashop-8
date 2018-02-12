<?php
class PaysonCheckout2NotificationsModuleFrontController extends ModuleFrontController
{
    public function init() {
        
        if(_PCO_LOG_){Logger::addLog('* ' . __FILE__ . ' -> ' . __METHOD__ . ' *', 1, NULL, NULL, NULL, true);}
        if(_PCO_LOG_){Logger::addLog('Query: ' . print_r($_REQUEST, true), 1, NULL, NULL, NULL, true);}
        
        $call = Tools::getValue('call');
        if(_PCO_LOG_){Logger::addLog('Call Type: ' . $call, 1, NULL, NULL, NULL, true);}
        
        if ($call == 'notification') {
            //parent::init();

            require_once(_PS_MODULE_DIR_ . 'paysoncheckout2/paysoncheckout2.php');
            $payson = new PaysonCheckout2();
            $paysonApi = $payson->getPaysonApiInstance();

            $cartId = Tools::getValue('id_cart');

            $checkoutId = Tools::getValue('checkout');
            if (!isset($checkoutId) || $checkoutId == NULL) {
               $checkoutId = $payson->getPaysonOrderEventId((int) $cartId);
            } 

            $checkout = $paysonApi->GetCheckout($checkoutId); 

            if(_PCO_LOG_){Logger::addLog('Notification Checkout Status: ' . $checkout->status, 1, NULL, NULL, NULL, true);}
            
            switch ($checkout->status) {
                case 'created':
                     $payson->returnCall(200);
                    break;
                case 'readyToShip':
                    var_dump(http_response_code(200));
                    exit();
                    break;
                case 'readyToPay':
                    var_dump(http_response_code(200));
                    exit();
                    break;
                case 'denied':
                    $payson->updatePaysonOrderEvent($checkout, $cartId);
                    var_dump(http_response_code(200));
                    exit();
                    break;
                case 'canceled':
                    $payson->updatePaysonOrderEvent($checkout, $cartId);
                    var_dump(http_response_code(200));
                    exit();
                    break;
                case 'paidToAccount':
                    $payson->updatePaysonOrderEvent($checkout, $cartId);
                    var_dump(http_response_code(200));
                    exit();
                    break;
                case 'expired':
                    $this->context->cookie->__set('paysonCheckoutId', NULL);
                    $payson->updatePaysonOrderEvent($checkout, $cartId);
                    var_dump(http_response_code(200));
                    exit();
                    break;
                case 'shipped':
                    $payson->updatePaysonOrderEvent($checkout, $cartId);
                    var_dump(http_response_code(200));
                    exit();
                    break;
                default:
                    Logger::addLog('Notification Unknown Checkout Status: ' . $checkout->status, 2, NULL, NULL, NULL, true);
                    var_dump(http_response_code(200));
                    exit();
            }  
        }
    }
}
