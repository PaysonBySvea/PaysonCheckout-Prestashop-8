<?php
global $cookie;
include_once(dirname(__FILE__) . '/../../config/config.inc.php');
include_once(dirname(__FILE__) . '/../../init.php');
include_once(dirname(__FILE__) . '/paysonCheckout2.php');
include_once(_PS_MODULE_DIR_ . 'paysonCheckout2/paysonEmbedded/paysonapi.php');
$context = Context::getContext();
$payson = new PaysonCheckout2();
$cart = new Cart(intval($cookie->id_cart));
$address = new Address();
$customer  = new Customer();

if($context->customer->isLogged() || $context->customer->is_guest)
{
    $customer = new Customer(intval($cart->id_customer));  
    $address = new Address(intval($cart->id_address_invoice));
      
    $state = NULL;
    if ($address->id_state)
        $state = new State(intval($address->id_state));

    if (!Validate::isLoadedObject($address))
    {
        Logger::addLog($payson->getL('Payson error: (invalid address)'), 1, NULL, NULL, NULL, true);
        Tools::redirect('index.php?controller=order&step=1');
    }
    
    if (!Validate::isLoadedObject($customer))
    {
        Logger::addLog($payson->getL('Payson error: (invalid customer)'), 1, NULL, NULL, NULL, true);
        Tools::redirect('index.php?controller=order&step=1');
    }
}
else
{
    if(isset($_GET['Email']) && $_GET['Email'] != NULL)
    {
        $paysonCustomerInfoToUpdate = $_GET;
        $tempCustomer = Customer::getCustomersByEmail(getMailPaysonCheckout($payson, $context->cookie->paysonCheckoutId));
        $customer = NULL;
        $address = NULL;

        if(count($tempCustomer) > 0){
            $customerId = null;
            foreach ($tempCustomer as $result){
                 $customerId  = $result['id_customer'];
            }
            
            $customer = new Customer($customerId); 
            //Update customer address in PS
            $address = updateCustomerAddressPS($cart->id, Country::getByIso($paysonCustomerInfoToUpdate['CountryCode']), $paysonCustomerInfoToUpdate, $customer->id);
            
        }
        else{
            //This row create a new customer in PS.
            $customer = addPaysonCustomerPS($payson, $cart->id, $context->cookie->paysonCheckoutId, $paysonCustomerInfoToUpdate);
            //This row create a new customer address in PS. 
            $address = addPaysonAddressPS($cart->id, Country::getByIso($paysonCustomerInfoToUpdate['CountryCode']), $paysonCustomerInfoToUpdate, $customer->id);
        }

        $cart->secure_key = $customer->secure_key;
        $cart->id_customer = $customer->id;
        $cart->id_address_delivery = $address->id;
        $cart->id_address_invoice = $address->id;
        $cart->update();
        exit();  
    }    
}


// check$currency->iso_code of payment
$currency_order = new Currency(intval($cart->id_currency));

use PaysonEmbedded\CurrencyCode as CurrencyCode;

$callPaysonApi = $payson->getAPIInstanceMultiShop();

$checkoutTempObj = NULL;

try 
{
    $cartQuantities = $cart->checkQuantities(true);
    if ($cartQuantities === TRUE) {
        // Only get/create PCO2 if all producta are available
        if ($context->cookie->paysonCheckoutId != Null && canUpdate($callPaysonApi, $context->cookie->paysonCheckoutId) && checkCurrency($currency_order->iso_code, $callPaysonApi, $context->cookie->paysonCheckoutId)) {
            //Get the checkout object
            $checkoutTempObj = $callPaysonApi->GetCheckout($context->cookie->paysonCheckoutId);

            $checkoutTempObj = $callPaysonApi->UpdateCheckout(updatePaysonCheckout($checkoutTempObj, $customer, $cart, $payson, $address, $currency_order));
            $payson->updatePaysonOrderEvents($checkoutTempObj);
        }
        else 
        {
            //Create a new checkout object
            $checkoutId = $callPaysonApi->CreateCheckout(addPaysonCheckout($customer, $cart, $payson, $currency_order, $context->language->id, $address));
            $context->cookie->__set('paysonCheckoutId',$checkoutId);
            //Get the new checkout object
            $checkoutTempObj = $callPaysonApi->GetCheckout($checkoutId);

            if ($checkoutTempObj->id != null) 
            {
                $payson->createPaysonOrderEvents($checkoutTempObj->id, $cart->id);
            }
        }
    }
    
    // One Page Checkout
    if((isset($_GET["type"]) && $_GET["type"] === 'checkPayson') || (isset($_GET['Email']) && $_GET['Email'] != NULL && $checkoutTempObj->status != "readyToShip")) {   
        // Make sure all products are still available
        if (is_array($cartQuantities)) {
            $returnOutput = '<p class="warning">' . sprintf(Tools::displayError('An item (%1s) in your cart is no longer available in this quantity. You cannot proceed with your order until the quantity is adjusted.'), $cartQuantities['name']) . '</p>';
        } else {
            $returnOutput = $checkoutTempObj->snippet; 
        }
        // Return PCO2 or error to JS, reset vars and exit
        print $returnOutput; unset($_GET); exit;
    }
    
    // Multi Page Checkout
    if (is_array($cartQuantities)) 
    {
        // Redirect to checkout
        Tools::redirect('index.php?controller=order&step=1');
    }
    
    $embeddedUrl = $payson->getSnippetUrl($checkoutTempObj->snippet);

    Tools::redirect(Context::getContext()->link->getModuleLink('paysonCheckout2', 'payment', array('checkoutId' => $checkoutTempObj->id, 'snippetUrl' => $embeddedUrl[0])));
} catch (Exception $e) {

    if (Configuration::get('PAYSONCHECKOUT2_LOGS') == 'yes') {
        $message = '<Payson PrestaShop Checkout 2.0> ' . $e->getMessage();
        PrestaShopLogger::addLog($message, 1, NULL, NULL, NULL, true);
    }
    $payson->paysonApiError('Please try using a different payment method (redirect).');
}

function canUpdate($callPaysonApi, $paysonCheckoutId){
    $checkout = $callPaysonApi->GetCheckout($paysonCheckoutId);
    switch ($checkout->status){
        case 'created':
            return true;
            break;
        case 'readyToPay':
            return true;
            break;
        case 'processingPayment':
            return true;
            break;
            
        case 'readyToShip':
            return false;
            break;
        case 'formsFiled':
            return false;
            break;
        default: 
            return false;
    }
    return false;  
}

function checkCurrency($cartCurrency, $callPaysonApi, $paysonCheckoutId){
    $checkout = $callPaysonApi->GetCheckout($paysonCheckoutId);
    
    if(strtoupper($cartCurrency) == strtoupper($checkout->payData->currency)){
        return true;
    }else{
        return false;
    }
}

function addPaysonCheckout($customer, $cart, $payson, $currency, $id_lang, $address) {
    $url = Tools::getHttpHost(false, true) . __PS_BASE_URI__;
    $trackingId = time();

    
    if (Configuration::get('PS_SSL_ENABLED') || Configuration::get('PS_SSL_ENABLED_EVERYWHERE')) {
        $protocol = 'https://';
    } else {
        $protocol = 'http://';
    }
    
    $confirmationUri = $protocol . $url . "modules/paysonCheckout2/validation.php?trackingId=" . $trackingId . "&id_cart=" . $cart->id;
    $notificationUri = $protocol . $url . 'modules/paysonCheckout2/ipn_payson.php?id_cart=' . $cart->id;
    $termsUri =        $protocol . $url . "index.php?id_cms=3&controller=cms&content_only=1";
    $checkoutUri =     $protocol . $url . "modules/paysonCheckout2/validation.php?trackingId=" . $trackingId . "&id_cart=" . $cart->id;
    
    $paysonMerchant = new PaysonEmbedded\Merchant($checkoutUri, $confirmationUri, $notificationUri, $termsUri, NULL, $payson->MODULE_VERSION);
    $paysonMerchant->reference = $cart->id;
    $payData = new PaysonEmbedded\PayData($currency->iso_code);
    $payData->items = orderItemsList($cart, $payson, $currency);
    $gui = new PaysonEmbedded\Gui($payson->languagePayson(Language::getIsoById($id_lang)), Configuration::get('PAYSONCHECKOUT2_COLOR_SCHEME'), Configuration::get('PAYSONCHECKOUT2_VERIFICATION'), (int) Configuration::get('PAYSONCHECKOUT2_REQUEST_PHONE'));

    if($payson->testMode){ 
        $customerCheckout  = new PaysonEmbedded\Customer('Tess T', 'Persson', 'test@payson.se', 1111111, "4605092222", 'Stan', 'SE', '99999', '');
    }else{
        $customerCheckout  = $customer->email == Null ? Null :new PaysonEmbedded\Customer($customer->firstname, $customer->lastname, $customer->email, $address->phone, "", $address->city, Country::getIsoById($address->id_country), $address->postcode, $address->address1);
    }

    $checkout = new PaysonEmbedded\Checkout($paysonMerchant, $payData, $gui, $customerCheckout);
    
    return $checkout;
}

function updatePaysonCheckout($checkout, $customer, $cart, $payson, $address, $currency) 
{   

    if($customer->email != Null && $checkout->status !=  'readyToPay'){
        $checkout->customer->firstName = $customer->firstname ;
        $checkout->customer->lastName = $customer->lastname;
        $checkout->customer->email = $customer->email;
        $checkout->customer->phone = $address->phone;
        $checkout->customer->city = $address->city; 
        $checkout->customer->countryCode = Country::getIsoById($address->id_country);
        $checkout->customer->postalCode = $address->postcode;
        $checkout->customer->street = $address->address1;
    }
    
    
    $checkout->payData->items = orderItemsList($cart, $payson, $currency);
    
    return $checkout;
}

function getMailPaysonCheckout($payson, $trackingId) 
{
    $callPaysonApi = $payson->getAPIInstanceMultiShop();
    $checkoutObj = $callPaysonApi->GetCheckout($trackingId);
    return $checkoutObj->customer->email;
}

function addPaysonCustomerPS($payson, $cartId, $checkoutId, $paysonCustomerInfoToUpdate){
        $cart = new Cart(intval($cartId));

        $customer = new Customer();
        $password = Tools::passwdGen(8);
        $customer->is_guest = 1;
        $customer->passwd = Tools::encrypt($password);
        $customer->id_default_group = (int) (Configuration::get('PS_CUSTOMER_GROUP', null, $cart->id_shop));
        $customer->optin = 0;
        $customer->active = 1;
        $customer->id_gender = 9;
        $customer->email = getMailPaysonCheckout($payson, $checkoutId);
        $customer->firstname = str_replace(array(':',',', ';', '+', '"', "'"), array(' '), (strlen($paysonCustomerInfoToUpdate['FirstName']) > 31 ? substr($paysonCustomerInfoToUpdate['FirstName'], 0, $address::$definition['fields']['firstname']['size']) : $paysonCustomerInfoToUpdate['FirstName']));
        $customer->lastname = $paysonCustomerInfoToUpdate['LastName'] != NULL ? $paysonCustomerInfoToUpdate['LastName'] : str_replace(array(':',',', ';', '+', '"', "'"), array(' '), (strlen($paysonCustomerInfoToUpdate['FirstName']) > 31 ? substr($paysonCustomerInfoToUpdate['FirstName'], 0, $address::$definition['fields']['firstname']['size']) : $paysonCustomerInfoToUpdate['FirstName']));
            
        $customer->add();

        return $customer;     
}

function addPaysonAddressPS($cartId, $countryId, $paysonCustomerInfoToUpdate, $customerId){
    $cart = new Cart(intval($cartId));

    $address = new Address();
    $address->firstname = str_replace(array(':',',', ';', '+', '"', "'"), array(' '), (strlen($paysonCustomerInfoToUpdate['FirstName']) > 31 ? substr($paysonCustomerInfoToUpdate['FirstName'], 0, $address::$definition['fields']['firstname']['size']) : $paysonCustomerInfoToUpdate['FirstName']));
    $address->lastname = $paysonCustomerInfoToUpdate['LastName'] != NULL ? $paysonCustomerInfoToUpdate['LastName'] : (str_replace(array(':',',', ';', '+', '"', "'"), array(' '), (strlen($paysonCustomerInfoToUpdate['FirstName']) > 31 ? substr($paysonCustomerInfoToUpdate['FirstName'], 0, $address::$definition['fields']['firstname']['size']) : $paysonCustomerInfoToUpdate['FirstName'])));
     
    $address->address1 = $paysonCustomerInfoToUpdate['Street'];
    $address->address2 = '';
    $address->city = $paysonCustomerInfoToUpdate['City'];
    $address->postcode = $paysonCustomerInfoToUpdate['PostalCode'];
    $address->country = Country::getNameById(Configuration::get('PS_LANG_DEFAULT'),$countryId);
    $address->id_customer = $customerId;
    $address->id_country = $countryId;
    $address->phone = '000000';
    $address->phone_mobile = '000000';
    //$address->id_state   = (int)$customer->id_state;
    $address->alias = "Payson account address";
    $address->add();

    return $address;                   
}

function updateCustomerAddressPS($cartId, $countryId, $paysonCustomerInfoToUpdate, $customerId){
    $cart = new Cart(intval($cartId));
    
    
    $address = new Address(Address::getFirstCustomerAddressId((int)$customerId)); 
    $address->firstname = str_replace(array(':',',', ';', '+', '"', "'"), array(' '), (strlen($paysonCustomerInfoToUpdate['FirstName']) > 31 ? substr($paysonCustomerInfoToUpdate['FirstName'], 0, $address::$definition['fields']['firstname']['size']) : $paysonCustomerInfoToUpdate['FirstName']));
    $address->lastname = $paysonCustomerInfoToUpdate['LastName'] != NULL ? $paysonCustomerInfoToUpdate['LastName'] : (str_replace(array(':',',', ';', '+', '"', "'"), array(' '), (strlen($paysonCustomerInfoToUpdate['FirstName']) > 31 ? substr($paysonCustomerInfoToUpdate['FirstName'], 0, $address::$definition['fields']['firstname']['size']) : $paysonCustomerInfoToUpdate['FirstName'])));
    $address->address1 = $paysonCustomerInfoToUpdate['Street'];
    $address->address2 = '';
    $address->city = $paysonCustomerInfoToUpdate['City'];
    $address->postcode = $paysonCustomerInfoToUpdate['PostalCode'];
    $address->country = Country::getNameById(Configuration::get('PS_LANG_DEFAULT'),$countryId);
    $address->id_country = Country::getByIso($paysonCustomerInfoToUpdate['CountryCode']);
    $address->alias = "Payson account address";
    $address->update();

    return $address;                   
}

/*
 * @return void
 * @param array $paysonUrl, $productInfo, $shopInfo, $moduleVersionToTracking
 * @disc the function request and redirect Payson API Sandbox
 */
/*
 * @return product list
 * @param int $id_cart
 * @disc 
 */
function orderItemsList($cart, $payson, $currency = null) 
{
    include_once(_PS_MODULE_DIR_ . 'paysonCheckout2/PaysonEmbedded/orderitem.php');
    $orderitemslist = array();
    
    $cur = $currency->decimals;
    foreach ($cart->getProducts() AS $cartProduct) 
    {
        if (isset($cartProduct['quantity_discount_applies']) && $cartProduct['quantity_discount_applies'] == 1)
            $payson->discount_applies = 1;
        
        $my_taxrate = $cartProduct['rate'] / 100;
        //$product_price = $cartProduct['price_wt'];

        $product_price = Tools::ps_round($cartProduct['price_wt'], $cur * _PS_PRICE_DISPLAY_PRECISION_);
        $attributes_small = isset($cartProduct['attributes_small']) ? $cartProduct['attributes_small'] : '';
        $orderitemslist[] = new  PaysonEmbedded\OrderItem(
            $cartProduct['name'] . ' ' . $attributes_small, $product_price, $cartProduct['cart_quantity'], number_format($my_taxrate, 3, '.', ''), $cartProduct['id_product']
        );
    }
    // check four discounts 
    $cartDiscounts = $cart->getDiscounts();

    //$total_shipping_wt = floatval($cart->getTotalShippingCost());
    $total_shipping_wt = Tools::ps_round($cart->getTotalShippingCost(), $cur * _PS_PRICE_DISPLAY_PRECISION_);
    $total_shipping_wot = 0;
    $carrier = new Carrier($cart->id_carrier, $cart->id_lang);
    
    if ($total_shipping_wt > 0) 
    {
        $carriertax = Tax::getCarrierTaxRate((int) $carrier->id, $cart->id_address_invoice);
        $carriertax_rate = $carriertax / 100;
        $forward_vat = 1 + $carriertax_rate;
        $total_shipping_wot = $total_shipping_wt / $forward_vat;
        
        if (!empty($cartDiscounts) and $cartDiscounts[0]['obj']->free_shipping) 
        {

        } 
        else 
        {
            $orderitemslist[] = new  PaysonEmbedded\OrderItem(
                    isset($carrier->name) ? $carrier->name : 'shipping', $total_shipping_wt, 1, number_format($carriertax_rate, 2, '.', ''), 'shipping', PaysonEmbedded\OrderItemType::SERVICE
            );
        }
    }
    
    $tax_rate_discount = 0;
    $taxDiscount = Cart::getTaxesAverageUsed((int) ($cart->id));
    
    if (isset($taxDiscount) AND $taxDiscount != 1) 
    {
        $tax_rate_discount = $taxDiscount * 0.01;
    }
    
    $discountTemp = 0;
    $i = 0;
    
    foreach ($cartDiscounts AS $cartDiscount) 
    {
        $discountTemp -= ($cartDiscount['value_real'] - (empty($cartDiscounts) ? 0 : $cartDiscounts[$i]['obj']->free_shipping ? $total_shipping_wt : 0));
        $i++;
    }
    
    if (!empty($cartDiscounts)) 
    {
        $orderitemslist[] = new  PaysonEmbedded\OrderItem($cartDiscount['name'], number_format($discountTemp, Configuration::get('PS_PRICE_DISPLAY_PRECISION'), '.', ''), 1, number_format($tax_rate_discount, 4, '.', ''), "discount", PaysonEmbedded\OrderItemType::DISCOUNT);
    }
    
    if ($cart->gift) 
    {
       $wrappingTemp = number_format(Tools::convertPrice((float) $cart->getGiftWrappingPrice(false), Currency::getCurrencyInstance((int) $cart->id_currency)), Configuration::get('PS_PRICE_DISPLAY_PRECISION'), '.', '') * number_format((((($cart->getOrderTotal(true, Cart::ONLY_WRAPPING) * 100) / $cart->getOrderTotal(false, Cart::ONLY_WRAPPING))) / 100), 2, '.', '');
        $orderitemslist[] = new  PaysonEmbedded\OrderItem('gift wrapping', $wrappingTemp, 1, number_format((((($cart->getOrderTotal(true, Cart::ONLY_WRAPPING) * 100) / $cart->getOrderTotal(false, Cart::ONLY_WRAPPING)) - 100) / 100), 2, '.', ''), 'wrapping', PaysonEmbedded\OrderItemType::SERVICE);
    }

    return $orderitemslist;
}
//ready, -----------------------------------------------------------------------
?>
