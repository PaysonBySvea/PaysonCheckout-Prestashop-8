<?php

namespace Payson\Payments;

use Payson\Payments\Implementation\ImplementationInterface;
use Payson\Payments\Transport\Connector;
use Payson\Payments\Implementation\ImplementationFactory;

/**
 * Class CheckoutClient
 *
 * @package Payson\Payments
 */
class CheckoutClient
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
     * Create new checkout
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        return $this->executeCommand(ImplementationFactory::returnCreateCheckoutClass($this->connector), $data);
    }

    /**
     * Update existing checkout
     *
     * @param array $data
     * @return mixed
     */
    public function update(array $data)
    {
        return $this->executeCommand(ImplementationFactory::returnUpdateCheckoutClass($this->connector), $data);
    }

    /**
     * Get existing checkout
     *
     * @param array $data
     * @return mixed
     */
    public function get($data)
    {
        return $this->executeCommand(ImplementationFactory::returnGetCheckoutClass($this->connector), $data);
    }
    
    /**
     * Get list of existing checkouts
     *
     * @param array $data
     * @return mixed
     */
    public function listCheckouts(array $data = array())
    {
        return $this->executeCommand(ImplementationFactory::returnListCheckoutsClass($this->connector), $data);
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
