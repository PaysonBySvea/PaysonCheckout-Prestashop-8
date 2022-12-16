<?php
/**
 * 2019 Payson AB
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 *  @author    Payson AB <integration@payson.se>
 *  @copyright 2019 Payson AB
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Payson\Payments;

use Payson\Payments\Implementation\ImplementationInterface;
use Payson\Payments\Transport\Connector;
use Payson\Payments\Implementation\ImplementationFactory;

/**
 * Class RecurringSubscriptionClient
 *
 * @package Payson\Payments
 */
class RecurringSubscriptionClient
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
        return $this->executeCommand(ImplementationFactory::returnCreateRecurringSubscriptionClass($this->connector), $data);
    }

    /**
     * Update existing recurring subscription
     *
     * @param array $data
     * @return mixed
     */
    public function update(array $data)
    {
        return $this->executeCommand(ImplementationFactory::returnUpdateRecurringSubscriptionClass($this->connector), $data);
    }

    /**
     * Get existing recurring subscription
     *
     * @param array $data
     * @return mixed
     */
    public function get($data)
    {
        return $this->executeCommand(ImplementationFactory::returnGetRecurringSubscriptionClass($this->connector), $data);
    }
    
    /**
     * Get list of existing recurring subscriptions
     *
     * @param array $data
     * @return mixed
     */
    public function listRecurringSubscriptions(array $data = array())
    {
        return $this->executeCommand(ImplementationFactory::returnListRecurringSubscriptionsClass($this->connector), $data);
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
