<?php
include_once(dirname(__FILE__) . '/../../config/config.inc.php');
include_once(dirname(__FILE__) . '/paysonCheckout2.php');

if (version_compare(_PS_VERSION_, '1.6.1.0 ', '<=')) {
    include_once(dirname(__FILE__) . '/../../header.php');
}
if (version_compare(_PS_VERSION_, '1.5.0.0 ', '>=')) {
    $context = Context::getContext();
    $cart = $context->cart;
}

$cart_id = intval($_GET["id_cart"]);

$payson = new PaysonCheckout2();

// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
/*
$authorized = false;
foreach (Module::getPaymentModules() as $module) {
    if (($module['name']) == 'paysonCheckout2') {
        $authorized = true;
        break;
    }
}

if (!$authorized) {
    foreach (Module::getPaymentModules() as $module) {
        PrestaShopLogger::addLog($module['name'], 1, NULL, NULL, NULL, true);
    } 
    die(Tools::displayError('This payment method Payson Checkout 2.0 is not available.'));
}
*/
$payson->CreateOrder($cart_id, NULL, 'returnCall');
?>