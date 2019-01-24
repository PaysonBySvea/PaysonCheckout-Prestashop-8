<?php

namespace Payson\Payments\Validation;

/**
 * Class ValidateGetCheckoutData
 * @package Payson\Payments\Validation
 */
class ValidateGetRecurringSubscriptionData extends ValidationService
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
