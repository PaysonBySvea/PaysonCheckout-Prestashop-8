<?php
/**
 * ipn_payson.php callback handler for Payson IPN notifications prestashop
 *
 * @package paymentMethod
 * @copyright Copyright 2015 Payson
 */
include_once(dirname(__FILE__) . '/../../config/config.inc.php');

if (version_compare(_PS_VERSION_, '1.6.1.0 ', '<=')) {
    include_once(dirname(__FILE__) . '/../../header.php');
}

/*
 * @return void
 * @param int $id_cart
 * @disc 
 */
paysonIpn();

function paysonIpn() {
    include_once(_PS_MODULE_DIR_ . 'paysonCheckout2/paysonCheckout2.php');

    $cart_id = '';
    $checkoutId = '';
    if (isset($_GET["checkout"]) && $_GET["checkout"] != NULL){
        $cart_id = intval($_GET["id_cart"]);
        $checkoutId = $_GET["checkout"];
    
        $payson = new PaysonCheckout2();
        $payson->CreateOrder($cart_id, $checkoutId, 'ipnCall');
    }

//    if (Configuration::get('PAYSON_LOGS') == 'yes'){
//        PrestaShopLogger::addLog('<Payson Direct api>The response could not validate.', 1, NULL, NULL, NULL, true);
//    }
}

?>