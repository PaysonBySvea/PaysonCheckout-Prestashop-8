<?php

namespace Payson\Payments\Validation;

/**
 * @package Payson\Payments\Validation
 */
class ValidateUpdateRecurringSubscriptionData extends ValidationService
{
    /**
     * @param array $data
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

        $requiredFields = array('id', 'status', 'subscriptionid', 'order');
        foreach ($requiredFields as $field) {
            $this->mustBeSet($data, $field, $field);
        }
        
        $this->mustNotBeEmptyArray($data['order'], 'Order');
    }
    
    /**
     * @param array $data
     * @throws PaysonException
     */
    private function validateOrder($data)
    {
        $this->mustBeSet($data['order'], 'currency', 'Currency');
        $this->mustBeString($data['order']['currency'], 'Currency');
        $this->mustBeInArray(strtolower($data['order']['currency']), array('sek', 'eur'), 'Currency');

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
            
            $this->mustBeFloat($orderItem['unitprice'], 'Order item unitPrice');
            $this->mustBeFloat($orderItem['quantity'], 'Order item quantity');
        }
    }
}
