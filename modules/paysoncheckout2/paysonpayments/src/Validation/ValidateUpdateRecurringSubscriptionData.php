<?php

namespace Payson\Payments\Validation;

/**
 * @package Payson\Payments\Validation
 */
class ValidateUpdateRecurringSubscriptionData extends ValidationService
{
    /**
     * @param array $data
     * @throws PaysonException if data is invalid
     */
    public function validate($data)
    {
        $this->validateGeneralData($data);
        $this->validateMerchant($data);
        $this->validateAgreement($data);
    }

    /**
     * @param array $data
     * @throws PaysonException
     */
    private function validateGeneralData($data)
    {
        $this->mustNotBeEmptyArray($data, 'Recurring Subscription data');

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

    /**
     * @param array $data
     * @throws PaysonException
     */
    private function validateAgreement($data)
    {
        $this->mustBeSet($data['agreement'], 'currency', 'Currency');
        $this->mustBeString($data['agreement']['currency'], 'Currency');
        $this->mustBeInArray($data['agreement']['currency'], array('sek', 'SEK', 'Sek'), 'Currency');
    }
}
