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

function spl__autoload_payson_payments_classes($className)
{
    if (!preg_match('#^(Payson\\\\Payments)#', $className)) {
        return;
    }

    $filename = str_replace('Payson\\Payments\\', '', $className);
    $fullPath = str_replace('\\', '/', dirname(__FILE__) . '\\src\\' . $filename . ".php");

    if (file_exists($fullPath)) {
        include_once $fullPath;
    }
}

spl_autoload_register('spl__autoload_payson_payments_classes');
