<?php

namespace Payson\Payments\Validation;

/**
 * @package Payson\Payments\Validation
 */
class ValidateCreateRecurringPaymentData extends ValidationService
{
    /**
     * @param array $data
     * @throws PaysonException if data is invalid
     */
    public function validate($data)
    {
        $this->validateGeneralData($data);
        $this->validateOrder($data);
        $this->validateOrderItems($data);
    }

    /**
     * @param array $data
     * @throws PaysonException
     */
    private function validateGeneralData($data)
    {
        $this->mustNotBeEmptyArray($data, 'Recurring Payment data');
        
        $requiredFields = array('order', 'notificationuri', 'subscriptionid');
        foreach ($requiredFields as $field) {
            $this->mustBeSet($data, $field, $field);
        }
    }

    /**
     * @param array $data
     * @throws PaysonException
     */
    private function validateOrder($data)
    {
        $this->mustBeSet($data['order'], 'currency', 'Currency');
        $this->mustBeString($data['order']['currency'], 'Currency');
        $this->mustBeInArray($data['order']['currency'], array('sek', 'SEK', 'Sek'), 'Currency');

        $this->mustBeSet($data['order'], 'items', 'Order Items');
        $this->mustNotBeEmptyArray($data['order']['items'], 'Order items');
    }
    
    /**
     * @param array $data
     * @throws PaysonException
     */
    private function validateOrderItems($data)
    {
        $requiredFields = array('name', 'quantity', 'unitprice', 'taxrate');

        foreach ($data['order']['items'] as $orderItem) {
            foreach ($requiredFields as $field) {
                $this->mustBeSet($orderItem, $field, 'Order item ' . $field);
            }
        }
    }
}
