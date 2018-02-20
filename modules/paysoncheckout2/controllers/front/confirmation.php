<?php
class PaysonCheckout2ConfirmationModuleFrontController extends ModuleFrontController
{
    public function setMedia()
    {
        parent::setMedia();
        $this->context->controller->addCSS(_MODULE_DIR_.'paysoncheckout2/views/css/payson_checkout2.css', 'all');
    }
    
    public function init() {
        parent::init();
        
        if(_PCO_LOG_){Logger::addLog('* ' . __FILE__ . ' -> ' . __METHOD__ . ' *', 1, NULL, NULL, NULL, true);}
        if(_PCO_LOG_){Logger::addLog('Call Type: ' . Tools::getValue('call'), 1, NULL, NULL, NULL, true);}
        require_once(_PS_MODULE_DIR_ . 'paysoncheckout2/paysoncheckout2.php');
        $payson = new PaysonCheckout2();
        $paysonApi = $payson->getPaysonApiInstance();
        
        $cartId = (int) Tools::getValue('id_cart');
        if (!isset($cartId)) {
           $this->context->cookie->__set('validation_error', $this->l('Something went wrong with your cart. Please try again.'));
           Tools::redirect('index.php?fc=module&module=paysoncheckout2&controller=pconepage');
        }
        
        $checkoutId = Tools::getValue('checkout');
        if (!isset($checkoutId) || $checkoutId == NULL) {
           // Get checkout ID from cookie
           $checkoutId = $this->context->cookie->paysonCheckoutId;
           if(_PCO_LOG_){Logger::addLog('No checkout in query, loaded: ' . $checkoutId . ' from cookie.', 1, NULL, NULL, NULL, true);}
        }
        
        $cart = new Cart($cartId);
        
        if (!$cart->checkQuantities()) {
            Tools::redirect('index.php?fc=module&module=paysoncheckout2&controller=pconepage');
        }
        
        $checkout = $paysonApi->GetCheckout($checkoutId);
        
        if(_PCO_LOG_){Logger::addLog('Cart ID: ' .  $cart->id, 1, NULL, NULL, NULL, true);}
        if(_PCO_LOG_){Logger::addLog('Cart delivery cost: ' . $cart->getOrderTotal(true, Cart::ONLY_SHIPPING), 1, NULL, NULL, NULL, true);}
        if(_PCO_LOG_){Logger::addLog('Cart total: ' . $cart->getOrderTotal(true, Cart::BOTH), 1, NULL, NULL, NULL, true);}
        if(_PCO_LOG_){Logger::addLog('Checkout ID: ' . $checkout->id, 1, NULL, NULL, NULL, true);}
        if(_PCO_LOG_){Logger::addLog('Checkout total: ' . $checkout->payData->totalPriceIncludingTax, 1, NULL, NULL, NULL, true);}
        if(_PCO_LOG_){Logger::addLog('Checkout Status: ' . $checkout->status, 1, NULL, NULL, NULL, true);}
        
        $orderCreated = FALSE;
        
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
                    if(_PCO_LOG_){Logger::addLog('New order ID: ' . $orderCreated, 1, NULL, NULL, NULL, true);}
                } else {
                    $orderAlreadyCreated = TRUE;
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
                $this->context->cookie->__set('paysonCheckoutId', NULL);
                $payson->updatePaysonOrderEvent($checkout, $cartId);
                $this->context->cookie->__set('validation_error', $this->l('This order has expired. Please try again.'));
                Tools::redirect('index.php?fc=module&module=paysoncheckout2&controller=pconepage');
                break;
            case 'shipped':
                if(_PCO_LOG_){Logger::addLog('Got order status shipped 1.', 1, NULL, NULL, NULL, true);}
                $payson->updatePaysonOrderEvent($checkout, $cartId);
                Tools::redirect('index.php?fc=module&module=paysoncheckout2&controller=pconepage');
                break;
            default:
                Logger::addLog('Unknown Checkout Status: ' . $checkout->status, 2, NULL, NULL, NULL, true);
                $this->context->cookie->__set('validation_error', $this->l('Unable to finish order.'));
                Tools::redirect('index.php?fc=module&module=paysoncheckout2&controller=pconepage');
        }     
        
        if ($orderCreated !== FALSE || $orderAlreadyCreated == TRUE) {
//            $customer = new Customer((int) $cart->id_customer);
//            if (Configuration::get('PAYSONCHECKOUT2_SHOW_CONFIRMATION') == 0 && $customer->is_guest == 0) {
//                // Show thank you page for logged in customer
//                Tools::redirect(
//                       'order-confirmation.php?key='.
//                       $customer->secure_key.
//                       '&id_cart='.
//                       $cart->id.
//                       '&id_module='.
//                       $this->module->id
//                   );
//
//            } else {
                // Show PCO2 iframe order confirmation
                $this->context->smarty->assign([
                    'pco2Snippet' => $checkout->snippet,
                ]);
                
                if(_PCO_LOG_){Logger::addLog('Order completed successfully.', 1, NULL, NULL, NULL, true);}
                
                 // Delete checkout id cookie
                $this->context->cookie->__set('paysonCheckoutId', NULL);

                $this->setTemplate('module:paysoncheckout2/views/templates/front/payment_return.tpl');
            //}
        } else {
            // Show order confirmation with errors
            if(_PCO_LOG_){Logger::addLog('Order creation was unsuccessfull.', 1, NULL, NULL, NULL, true);}
            $this->context->smarty->assign('payson_error', $this->l('Unable to create order.'));
            $this->setTemplate('module:paysoncheckout2/views/templates/front/payment_return.tpl');
        }
    } 
}
