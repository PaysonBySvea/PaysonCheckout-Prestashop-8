<?php
class PaysonCheckout2RedirectModuleFrontController extends ModuleFrontController {

    public $ssl = true;
    public $display_column_left = false;
    public $display_column_right = false;

    /**
     * @see FrontController::initContent()
     */
    public function initContent() {
        parent::initContent();

        $cart = $this->context->cart;

        Tools::redirect(_PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/paysonCheckout2/redirect.php');
    }

}
