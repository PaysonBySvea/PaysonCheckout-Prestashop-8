<?php

namespace Payson\Payments;

use Payson\Payments\Implementation\ImplementationInterface;
use Payson\Payments\Transport\Connector;
use Payson\Payments\Implementation\ImplementationFactory;

/**
 * Class RecurringPaymentClient
 *
 * @package Payson\Payments
 */
class RecurringPaymentClient
{
    /**
     * Connector
     *
     * @var Connector
     */
    private $connector;

    /**
     * CheckoutClient constructor.
     *
     * @param Connector $connector
     */
    public function __construct(Connector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * Create new recurring subscription
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        return $this->executeCommand(ImplementationFactory::returnCreateRecurringPaymentClass($this->connector), $data);
    }

    /**
     * Update existing recurring subscription
     *
     * @param array $data
     * @return mixed
     */
    public function update(array $data)
    {
        return $this->executeCommand(ImplementationFactory::returnUpdateRecurringPaymentClass($this->connector), $data);
    }

    /**
     * Get existing recurring subscription
     *
     * @param array $data
     * @return mixed
     */
    public function get($data)
    {
        return $this->executeCommand(ImplementationFactory::returnGetRecurringPaymentClass($this->connector), $data);
    }
    
    /**
     * Get list of existing recurring subscriptions
     *
     * @param array $data
     * @return mixed
     */
    public function listRecurringPayments(array $data = array())
    {
        return $this->executeCommand(ImplementationFactory::returnListRecurringPaymentsClass($this->connector), $data);
    }
    
    /**
     * Get PaysonAccount info
     * 
     * @param array $data
     * @return mixed
     */
    public function getAccountInfo()
    {
        return $this->executeCommand(ImplementationFactory::returnGetAccountInfoClass($this->connector));
    }

    /**
     * @param ImplementationInterface $actionObject
     * @param array $inputData
     * @return array
     */
    private function executeCommand($actionObject, array $inputData = array())
    {
        $actionObject->execute($inputData);

        $responseHandler = $actionObject->getResponseHandler();

        return $responseHandler->getResponse();
    }
}
