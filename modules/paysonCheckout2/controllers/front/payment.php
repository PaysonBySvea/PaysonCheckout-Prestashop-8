<?php
class PaysonCheckout2PaymentModuleFrontController extends ModuleFrontController {

    public $ssl = true;
    public $display_column_left = false;
    public $display_column_right = false;

    /**
     * @see FrontController::initContent()
     */
    public function initContent() {
        parent::initContent();

        $cart = $this->context->cart;

//print_r(Tools::getValue('snippet'));exit;
        $this->context->smarty->assign(
                array(
                    'checkoutId' => Tools::getValue('checkoutId'),
                    'snippet'  => Tools::getValue('snippet'),
                    'cust_currency' => $cart->id_currency,
                    'currencies' => $this->module->getCurrency((int) $cart->id_currency),
                    'total' => $cart->getOrderTotal(true, Cart::BOTH),
                    //'checkoutId' => $this->module->getPaysonResponsR(),
                    'isoCode' => $this->context->language->iso_code,
                    'this_path' => $this->module->getPathUri(),
                    'this_path_paysonCheckout2' => $this->module->getPathUri(),
                    'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/'
                )
        );

        $this->setTemplate('paysonCheckout2_execution.tpl');
    }

}
