<?php
/**
* 2019 Payson AB
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
*
*  @author    Payson AB <integration@payson.se>
*  @copyright 2019 Payson AB
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

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
