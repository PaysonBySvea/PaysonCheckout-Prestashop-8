<?php

namespace Payson\Payments\Validation;

/**
 * @package Payson\Payments\Validation
 */
class ValidateGetRecurringPaymentData extends ValidationService
{
    /**
     * @param mixed $data
     */
    public function validate($data)
    {
        $this->mustBeSet($data, 'id', 'Checkout Id');
        $this->mustBeString($data['id'], 'Checkout Id');
    }
}
