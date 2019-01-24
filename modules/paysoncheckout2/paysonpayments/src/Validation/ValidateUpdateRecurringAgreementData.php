<?php

namespace Payson\Payments\Validation;

/**
 * @package Payson\Payments\Validation
 */
class ValidateUpdateRecurringPaymentData extends ValidationService
{
    /**
     * @param array $data
     */
    public function validate($data)
    {
        $this->validateGeneralData($data);
        $this->validateMerchant($data);
    }
    
    /**
     * @param array $data
     * @throws PaysonException
     */
    private function validateGeneralData($data)
    {
        $this->mustNotBeEmptyArray($data, 'Recurring Subscription data');

        $requiredFields = array('id', 'status');
        foreach ($requiredFields as $field) {
            $this->mustBeSet($data, $field, $field);
        }
        
        $requiredFields = array('merchant', 'agreement');
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
    }
}
