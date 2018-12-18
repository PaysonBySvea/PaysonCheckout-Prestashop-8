<?php

namespace Payson\Payments\Validation;

/**
 * @package Payson\Payments\Validation
 */
class ValidateListRecurringSubscriptionsData extends ValidationService
{
    /**
     * @param mixed $data
     */
    public function validate($data)
    {
        if (isset($data['status'])) {
            $this->mustBeString($data['status'], 'Recurring Subscription status');
            $this->mustBeInArray(strtolower($data['status']), array('none', 'awaitingsubscription', 'customersubscribed', 'customerunsubscribed', 'canceled', 'expired'), 'Recurring agreement status');
        }
        
        if (isset($data['page'])) {
            $this->mustBeInteger($data['page'], 'Page number');
        }
    }
}
