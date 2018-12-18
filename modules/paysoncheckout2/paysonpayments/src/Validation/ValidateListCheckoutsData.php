<?php

namespace Payson\Payments\Validation;

/**
 * Class ValidateListCheckoutData
 * @package Payson\Payments\Validation
 */
class ValidateListCheckoutsData extends ValidationService
{
    /**
     * @param mixed $data
     */
    public function validate($data)
    {
        if (isset($data['status'])) {
            $this->mustBeString($data['status'], 'Checkout status');
            $this->mustBeInArray(strtolower($data['status']), array('created', 'readytopay', 'readytoship', 'shipped', 'paidtoaccount', 'canceled', 'expired', 'denied'), 'Checkout status');
        }
        
        if (isset($data['page'])) {
            $this->mustBeInteger($data['page'], 'Page number');
        }
    }
}
