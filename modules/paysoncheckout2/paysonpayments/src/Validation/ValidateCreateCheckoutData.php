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

namespace Payson\Payments\Validation;

/**
 * Class ValidateCreateCheckoutData
 * @package Payson\Payments\Validation
 */
class ValidateCreateCheckoutData extends ValidationService
{
    /**
     * @param array $data
     * @throws PaysonException if data is invalid
     */
    public function validate($data)
    {
        $this->validateGeneralData($data);
        $this->validateMerchant($data);
        $this->validateOrder($data);
        $this->validateOrderItems($data);
    }

    /**
     * @param array $data
     * @throws PaysonException
     */
    private function validateGeneralData($data)
    {
        $this->mustNotBeEmptyArray($data, 'Checkout data');

        $requiredFields = array('merchant', 'order');
        foreach ($requiredFields as $field) {
            $this->mustBeSet($data, $field, $field);
            $this->mustNotBeEmptyArray($data[$field], $field);
        }
    }

    /**
     * @param array $data
     * @throws PaysonException
     */
    private function validateMerchant($data)
    {
        $merchantData = $data['merchant'];
        $requiredFields = array('termsuri', 'checkouturi', 'confirmationuri', 'notificationuri');

        foreach ($requiredFields as $field) {
            $this->mustBeSet($merchantData, $field, 'Merchant ' . $field);
            $this->mustBeString($merchantData[$field], 'Merchant ' . $field);
            $this->lengthMustBeBetween(trim($merchantData[$field]), 9, 600, 'Merchant ' . $field . ' should contain http:// or https:// and');
        }
        
        $this->lengthMustBeBetween(trim($merchantData['integrationinfo']), 0, 100, 'Merchant integrationInfo');
    }

    /**
     * @param array $data
     * @throws PaysonException
     */
    private function validateOrder($data)
    {
        $this->mustBeSet($data['order'], 'currency', 'Currency');
        $this->mustBeString($data['order']['currency'], 'Currency');
        $this->mustBeInArray($data['order']['currency'], array('sek', 'eur', 'SEK', 'EUR', 'Sek', 'Eur'), 'Currency');

        $this->mustBeSet($data['order'], 'items', 'Order Items');
        $this->mustNotBeEmptyArray($data['order']['items'], 'Order Items');
    }
    
    /**
     * @param array $data
     * @throws PaysonException
     */
    private function validateOrderItems($data)
    {
        $requiredFields = array('name', 'quantity', 'unitprice');

        foreach ($data['order']['items'] as $orderItem) {
            foreach ($requiredFields as $field) {
                $this->mustBeSet($orderItem, $field, 'Order item ' . $field);
            }
            $this->mustBeString($orderItem['name'], 'Order item name ');
            $this->lengthMustBeBetween(trim($orderItem['name']), 1, 200, 'Order item name');
        }
    }
}
