<?php

class PaysonCheckout2PaymentModuleFrontController extends ModuleFrontController
{
    public function initContent() {
        parent::initContent();
        
        if(_PCO_LOG_){Logger::addLog('* ' . __FILE__ . ' -> ' . __METHOD__ . ' *', 1, NULL, NULL, NULL, true);}
        
        if (!isset($this->context->cart->id)) {
            Tools::redirect('index.php');
        }

        $cartCurrency = new Currency($this->context->cart->id_currency);
        if(_PCO_LOG_){Logger::addLog('Cart Currency: ' . $cartCurrency->iso_code, 1, NULL, NULL, NULL, true);}

        if (isset($this->context->cart) && $this->context->cart->nbProducts() > 0) {
            if (!$this->context->cart->checkQuantities()) {
                Tools::redirect('index.php?controller=order&step=1');
            } else {
                require_once(_PS_MODULE_DIR_ . 'paysoncheckout2/paysoncheckout2.php');
                $payson = new PaysonCheckout2();
                
                try {
                    $paysonApi = $payson->getPaysonApiInstance();
                    if(_PCO_LOG_){Logger::addLog('Payson API Merchant ID: ' . $paysonApi->getMerchantId(), 1, NULL, NULL, NULL, true);}
                } catch (Exception $e) {
                    Logger::addLog('Payson API Failure: ' . $e->getMessage(), 3, NULL, NULL, NULL, true);
                    Tools::redirect('index.php?controller=order&step=1');
                }
                
                $address = new Address();
                $customer  = new Customer();

                if ($this->context->customer->isLogged() || $this->context->customer->is_guest) {
                    if(_PCO_LOG_){Logger::addLog($this->context->customer->is_guest == 1 ? 'Customer is: Guest' : 'Customer is: Logged in', 1, NULL, NULL, NULL, true);}
                    // Customer is logged in or has entered guest address information, we'll use this information
                    $customer = new Customer(intval($this->context->cart->id_customer));  
                    $address = new Address(intval($this->context->cart->id_address_invoice));

                    $state = NULL;
                    if ($address->id_state){
                        $state = new State(intval($address->id_state));
                    }

                    if (!Validate::isLoadedObject($address)) {
                        Logger::addLog('Unable to validate address.', 3, NULL, NULL, NULL, true);
                        Tools::redirect('index.php?controller=order&step=1');
                    }

                    if (!Validate::isLoadedObject($customer)) {
                        Logger::addLog('Unable to validate customer.', 3, NULL, NULL, NULL, true);
                        Tools::redirect('index.php?controller=order&step=1');
                    }
                    //if(_PCO_LOG_){Logger::addLog('Customer: ' . print_r($customer, true), 1, NULL, NULL, NULL, true);}
                    //if(_PCO_LOG_){Logger::addLog('Address: ' . print_r($address, true), 1, NULL, NULL, NULL, true);}
                    
                } else {
                    if(_PCO_LOG_){Logger::addLog('Customer is not Guest or Logged in', 1, NULL, NULL, NULL, true);}
                }

                if ((int)Configuration::get('PAYSONCHECKOUT2_ONE_PAGE') == 1) {
                    // This is a redirect via "other payment methods", clear cookie
                    $this->context->cookie->__set('paysonCheckoutId', NULL);
                }
                
                try {
                    if ($this->context->cookie->paysonCheckoutId != Null && $payson->canUpdate($paysonApi, $this->context->cookie->paysonCheckoutId) && $payson->checkCurrencyName($cartCurrency->iso_code, $paysonApi, $this->context->cookie->paysonCheckoutId)) {
                        // Get checkout object
                        $chTempObj = $paysonApi->GetCheckout($this->context->cookie->paysonCheckoutId);

                        // Update checkout object
                        $checkoutObj = $paysonApi->UpdateCheckout($payson->updatePaysonCheckout($chTempObj, $customer, $this->context->cart, $payson, $address, $cartCurrency));
                        
                        // Update data in Payson order table
                        $payson->updatePaysonOrderEvent($checkoutObj, $this->context->cart->id);
                        if(_PCO_LOG_){Logger::addLog('Loaded updated PCO.', 1, NULL, NULL, NULL, true);}
                    } else {
                        // Create a new checkout object
                        $checkoutId = $paysonApi->CreateCheckout($payson->createPaysonCheckout($customer, $this->context->cart, $payson, $cartCurrency, $this->context->language->id, $address));
                        
                        // Save PCO ID in cookie
                        $this->context->cookie->__set('paysonCheckoutId', $checkoutId);
                        
                        //Get the new checkout object
                        $checkoutObj = $paysonApi->GetCheckout($checkoutId);
                        
                        // Create data in Payson order table
                        $payson->createPaysonOrderEvent($checkoutObj->id, $this->context->cart->id);
                        if(_PCO_LOG_){Logger::addLog('Loaded new PCO.', 1, NULL, NULL, NULL, true);}
                    }

                    if ($checkoutObj->id != null) {
                        // Get snippet for template
                        $snippet = $checkoutObj->snippet;
                        if(_PCO_LOG_){Logger::addLog('PCO ID: ' . $checkoutObj->id, 1, NULL, NULL, NULL, true);}
                    } else {
                        Logger::addLog('Unable to retrive checkout.', 3, NULL, NULL, NULL, true);
                        Tools::redirect('index.php?controller=order&step=1');
                    }
                } catch (Exception $e) {
                    Logger::addLog('Unable to get checkout. Message: ' . $e->getMessage(), 3, NULL, NULL, NULL, true);
                    $this->context->cookie->__set('paysonCheckoutId', NULL);
                    Tools::redirect('index.php?controller=order&step=1');
                }
            }


            $this->context->smarty->assign('payson_errors', NULL);
            
            if (isset($this->context->cookie->validation_error) && $this->context->cookie->validation_error != NULL) {
                if(_PCO_LOG_){Logger::addLog('Redirection error message: ' . $this->context->cookie->validation_error, 1, NULL, NULL, NULL, true);}

                $this->context->smarty->assign('payson_errors', $this->context->cookie->validation_error);
                
                // Delete old messages
                $this->context->cookie->__set('validation_error', NULL);
            }
            
            // All is well, assign some smarty
            $this->context->smarty->assign(['snippet' => $snippet,]);

        } else {
            $this->context->smarty->assign('payson_errors', $this->l('Your cart is empty.'));
        }
        
        // All done, lets checkout!
        $this->setTemplate('module:paysoncheckout2/views/templates/front/payment.tpl');
    }
}
