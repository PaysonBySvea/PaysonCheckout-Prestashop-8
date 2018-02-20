<?php
class PaysonCheckout2ValidationModuleFrontController extends ModuleFrontController
{
    public function init() {
        parent::init();
        
        if(_PCO_LOG_){Logger::addLog('* ' . __FILE__ . ' -> ' . __METHOD__ . ' *', 1, NULL, NULL, NULL, true);}
        
        require_once(_PS_MODULE_DIR_ . 'paysoncheckout2/paysoncheckout2.php');
        $payson = new PaysonCheckout2();
        $paysonApi = $payson->getPaysonApiInstance();

        $cartId = (int) Tools::getValue('id_cart');
        if (!isset($cartId) || $cartId < 1 || $cartId == NULL) {
            Logger::addLog('No cart ID in query.', 3, NULL, NULL, NULL, true);
            header('HTTP/1.1 500 Error');
            exit();
        }

        $checkoutId = Tools::getValue('checkout');
        if (!isset($checkoutId) || $checkoutId == NULL) {
            if(_PCO_LOG_){Logger::addLog('No checkout in query, loading from cookie.', 1, NULL, NULL, NULL, true);}
           // Get checkout ID from cookie
           $checkoutId = $this->context->cookie->paysonCheckoutId;
        }

        $checkout = $paysonApi->GetCheckout($checkoutId); 

        if(_PCO_LOG_){Logger::addLog('Checkout ID: ' . $checkout->id, 1, NULL, NULL, NULL, true);}
        if(_PCO_LOG_){Logger::addLog('Cart ID: ' .  $cartId, 1, NULL, NULL, NULL, true);}
        if(_PCO_LOG_){Logger::addLog('Query: ' . print_r($_REQUEST, true), 1, NULL, NULL, NULL, true);}
        if(_PCO_LOG_){Logger::addLog('Checkout Status: ' . $checkout->status, 1, NULL, NULL, NULL, true);}

        $cart = new Cart($cartId);
        
        // Delete "cookie" file
        $storageFilePath = rtrim(dirname(dirname(dirname(__FILE__))), '/\\') . DIRECTORY_SEPARATOR . 'store/' . $cart->id . "_or.txt";
        unlink($storageFilePath);
        
        // Create or update customer
        $id_customer = (int) (Customer::customerExists($checkout->customer->email, true, true));

        if ($id_customer > 0) {
            $customer = new Customer($id_customer);
            $address = $payson->updatePaysonAddressPS(Country::getByIso($checkout->customer->countryCode), $checkout, $customer->id);
            if (!Validate::isLoadedObject($address)) {
                // Registred customer has no addres in PS, create new
                $address = $payson->addPaysonAddressPS(Country::getByIso($checkout->customer->countryCode), $checkout, $customer->id);
            }
        } else {
            // Create a new customer in PS
            $customer = $payson->addPaysonCustomerPS($cart->id, $checkout);
            // Create a new customer address in PS
            $address = $payson->addPaysonAddressPS(Country::getByIso($checkout->customer->countryCode), $checkout, $customer->id);
        }

        $new_delivery_options = array();
        $new_delivery_options[(int) ($address->id)] = $cart->id_carrier.',';
        $new_delivery_options_serialized = serialize($new_delivery_options);

        if(_PCO_LOG_){Logger::addLog('Address ID: ' . $address->id, 1, NULL, NULL, NULL, true);}
        if(_PCO_LOG_){Logger::addLog('Carrier ID: ' . $cart->id_carrier, 1, NULL, NULL, NULL, true);}

        $update_sql = 'UPDATE '._DB_PREFIX_.'cart '.
                'SET delivery_option=\''.
                pSQL($new_delivery_options_serialized).
                '\' WHERE id_cart='.
                (int) $cart->id;

        Db::getInstance()->execute($update_sql);

        if ($cart->id_carrier > 0) {
            $cart->delivery_option = $new_delivery_options_serialized;
        } else {
            $cart->delivery_option = '';
        }

        $update_sql = 'UPDATE '._DB_PREFIX_.'cart_product '.
            'SET id_address_delivery='.(int) $address->id.
            ' WHERE id_cart='.(int) $cart->id;

        Db::getInstance()->execute($update_sql);

        // To refresh/clear cart carrier cache
        $cart->getPackageList(true);
        $cart->getDeliveryOptionList(null, true);
        $cart->getDeliveryOption(null, false, false);

        // Set carrier
        $cart->setDeliveryOption($new_delivery_options);

        $cart->secure_key = $customer->secure_key;
        $cart->id_customer = $customer->id;
        $cart->save();

        $cache_id = 'objectmodel_cart_'.$cart->id.'*';
        Cache::clean($cache_id);
        $cart = new Cart($cart->id);

        //if(_PCO_LOG_){Logger::addLog('Cart: ' . print_r($cart, true), 1, NULL, NULL, NULL, true);}
        if(_PCO_LOG_){Logger::addLog('Checkout country: ' . $checkout->customer->countryCode, 1, NULL, NULL, NULL, true);}

        $checkoutTotal = $checkout->payData->totalPriceIncludingTax;
        $cartTotal = $cart->getOrderTotal(true, Cart::BOTH);

        if(_PCO_LOG_){Logger::addLog('Checkout total: ' . $checkoutTotal, 1, NULL, NULL, NULL, true);}
        if(_PCO_LOG_){Logger::addLog('Cart total: ' . $cartTotal, 1, NULL, NULL, NULL, true);}

        if ($checkoutTotal !== $cartTotal) {
            /*
             * Common reason for ending up with a mismatch between checkout and cart totals is that the customer has selected 
             * a different country in the checkout. Here the cart has been updated to reflect the VAT of the selected country. 
             * Here we update the checkout to match the cart, return 500 to stop the purchase and save a file for JS to look for.
             * If JS finds this file it will reload the checkout page to reflect changes.
            */
            $cartCurrency = new Currency($cart->id_currency);

            // Update checkout object
            $checkout = $paysonApi->UpdateCheckout($payson->updatePaysonCheckout($checkout, $customer, $cart, $payson, $address, $cartCurrency));

            // Update data in Payson order table
            $payson->updatePaysonOrderEvent($checkout, $cart->id);
            
            if(_PCO_LOG_){Logger::addLog('Updated checkout to match cart.', 1, NULL, NULL, NULL, true);}
            
            // Create "cookie" file
            $fileCookie = fopen($storageFilePath, "w");
            fclose($fileCookie);
            
            if(_PCO_LOG_){Logger::addLog('Sending status 500.', 1, NULL, NULL, NULL, true);}
            header('HTTP/1.1 500 Error');

            exit();
        } 
        if(_PCO_LOG_){Logger::addLog('Sending status 200.', 1, NULL, NULL, NULL, true);}
        
        header('HTTP/1.1 200 OK');
        exit();
    }   
}
