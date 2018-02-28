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

class PaysonCheckout2PaymentModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        parent::initContent();

        if (_PCO_LOG_) {
            Logger::addLog('* ' . __FILE__ . ' -> ' . __METHOD__ . ' *', 1, null, null, null, true);
        }

        if (!isset($this->context->cart->id)) {
            Tools::redirect('index.php');
        }

        $cartCurrency = new Currency($this->context->cart->id_currency);
        if (_PCO_LOG_) {
            Logger::addLog('Cart Currency: ' . $cartCurrency->iso_code, 1, null, null, null, true);
        }

        if (isset($this->context->cart) && $this->context->cart->nbProducts() > 0) {
            if (!$this->context->cart->checkQuantities()) {
                Tools::redirect('index.php?controller=order&step=1');
            } else {
                require_once(_PS_MODULE_DIR_ . 'paysoncheckout2/paysoncheckout2.php');
                $payson = new PaysonCheckout2();

                try {
                    $paysonApi = $payson->getPaysonApiInstance();
                    if (_PCO_LOG_) {
                        Logger::addLog('API Merchant ID: ' . $paysonApi->getMerchantId(), 1, null, null, null, true);
                    }
                } catch (Exception $e) {
                    Logger::addLog('API Failure: ' . $e->getMessage(), 3, null, null, null, true);
                    Tools::redirect('index.php?controller=order&step=1');
                }

                $address = new Address();
                $customer = new Customer();

                if ($this->context->customer->isLogged() || $this->context->customer->is_guest) {
                    if (_PCO_LOG_) {
                        Logger::addLog($this->context->customer->is_guest == 1 ? 'Customer is: Guest' : 'Customer is: Logged in', 1, null, null, null, true);
                    }
                    // Customer is logged in or has entered guest address information, we'll use this information
                    $customer = new Customer((int) ($this->context->cart->id_customer));
                    $address = new Address((int) ($this->context->cart->id_address_invoice));

//                    $state = null;
                    if ($address->id_state) {
                        $state = new State((int) ($address->id_state));
                    }

                    if (!Validate::isLoadedObject($address)) {
                        Logger::addLog('Unable to validate address.', 3, null, null, null, true);
                        Tools::redirect('index.php?controller=order&step=1');
                    }

                    if (!Validate::isLoadedObject($customer)) {
                        Logger::addLog('Unable to validate customer.', 3, null, null, null, true);
                        Tools::redirect('index.php?controller=order&step=1');
                    }
                    //if (_PCO_LOG_) {
                    //Logger::addLog('Customer: ' . print_r($customer, true), 1, null, null, null, true);
                    //}
                    //if (_PCO_LOG_) {
                    //Logger::addLog('Address: ' . print_r($address, true), 1, null, null, null, true);
                    //}
                } else {
                    if (_PCO_LOG_) {
                        Logger::addLog('Customer is not Guest or Logged in', 1, null, null, null, true);
                    }
                }

                if ((int) Configuration::get('PAYSONCHECKOUT2_ONE_PAGE') == 1) {
                    // This is a redirect via "other payment methods", clear cookie
                    $this->context->cookie->__set('paysonCheckoutId', null);
                }

                try {
                    if ($this->context->cookie->paysonCheckoutId != null && $payson->canUpdate($paysonApi, $this->context->cookie->paysonCheckoutId) && $payson->checkCurrencyName($cartCurrency->iso_code, $paysonApi, $this->context->cookie->paysonCheckoutId)) {
                        // Get checkout object
                        $chTempObj = $paysonApi->GetCheckout($this->context->cookie->paysonCheckoutId);

                        // Update checkout object
                        $checkoutObj = $paysonApi->UpdateCheckout($payson->updatePaysonCheckout($chTempObj, $customer, $this->context->cart, $payson, $address, $cartCurrency));

                        // Update data in Payson order table
                        $payson->updatePaysonOrderEvent($checkoutObj, $this->context->cart->id);
                        if (_PCO_LOG_) {
                            Logger::addLog('Loaded updated PCO.', 1, null, null, null, true);
                        }
                    } else {
                        // Create a new checkout object
                        $checkoutId = $paysonApi->CreateCheckout($payson->createPaysonCheckout($customer, $this->context->cart, $payson, $cartCurrency, $this->context->language->id, $address));

                        // Save PCO ID in cookie
                        $this->context->cookie->__set('paysonCheckoutId', $checkoutId);

                        //Get the new checkout object
                        $checkoutObj = $paysonApi->GetCheckout($checkoutId);

                        // Create data in Payson order table
                        $payson->createPaysonOrderEvent($checkoutObj->id, $this->context->cart->id);
                        if (_PCO_LOG_) {
                            Logger::addLog('Loaded new PCO.', 1, null, null, null, true);
                        }
                    }

                    if ($checkoutObj->id != null) {
                        // Get snippet for template
                        $snippet = $checkoutObj->snippet;
                        if (_PCO_LOG_) {
                            Logger::addLog('PCO ID: ' . $checkoutObj->id, 1, null, null, null, true);
                        }
                    } else {
                        Logger::addLog('Unable to retrive checkout.', 3, null, null, null, true);
                        Tools::redirect('index.php?controller=order&step=1');
                    }
                } catch (Exception $e) {
                    Logger::addLog('Unable to get checkout. Message: ' . $e->getMessage(), 3, null, null, null, true);
                    $this->context->cookie->__set('paysonCheckoutId', null);
                    Tools::redirect('index.php?controller=order&step=1');
                }
            }


            $this->context->smarty->assign('payson_errors', null);

            if (isset($this->context->cookie->validation_error) && $this->context->cookie->validation_error != null) {
                if (_PCO_LOG_) {
                    Logger::addLog('Redirection error message: ' . $this->context->cookie->validation_error, 1, null, null, null, true);
                }

                $this->context->smarty->assign('payson_errors', $this->context->cookie->validation_error);

                // Delete old messages
                $this->context->cookie->__set('validation_error', null);
            }

            // All is well, assign some smarty
            $this->context->smarty->assign(array('snippet' => $snippet));
        } else {
            $this->context->smarty->assign('payson_errors', $this->l('Your cart is empty.'));
        }

        // All done, lets checkout!
        $this->setTemplate('module:paysoncheckout2/views/templates/front/payment.tpl');
    }
}
