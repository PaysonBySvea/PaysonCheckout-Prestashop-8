<?php

class CartController extends CartControllerCore
{
    public function init()
    {
        if ((int)Configuration::get('PAYSONCHECKOUT2_ONE_PAGE') == 1 &&
            (int)Configuration::get('PAYSONCHECKOUT2_MODULE_ENABLED') == 1 && 
            Tools::getValue('action') === 'show' &&
            (int)Tools::getValue('ajax') !== 1 &&
            (int)Tools::getValue('update') !== 1 &&
            (int)Tools::getValue('forceview') !== 1
            ) {
                Tools::redirect($this->context->link->getModuleLink('paysoncheckout2', 'pconepage', array(), Tools::usingSecureMode()));
                die;
        }
        parent::init();
    }
}
