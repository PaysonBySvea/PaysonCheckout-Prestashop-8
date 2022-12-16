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

namespace Payson\Payments\Implementation;

use Payson\Payments\Model\Request;

class CreateRecurringPayment extends ImplementationManager
{
    protected $apiUrl = '/RecurringPayments';

    /**
     * Request body - JSON
     *
     * @var Request $requestModel
     */
    private $requestModel;

    /**
     * Validate passed data
     *
     * @param array $data
     */
    public function validateData($data)
    {
        $validator = $this->validator;
        $validator->validate($data);
    }

    /**
     * Prepare date for request
     *
     * @param array $data
     */
    public function prepareData($data)
    {
        $this->requestModel = new Request();
        $this->requestModel->setPostMethod();
        $this->requestModel->setBody(json_encode($data));
        $this->requestModel->setApiUrl($this->connector->getBaseApiUrl() . $this->apiUrl);
    }

    /**
     * Modify data for request
     *
     * @param array $data
     */
    public function modifyData($data)
    {
        return $data;
    }

    /**
     * Invoke request call
     *
     * @throws \Payson\Payments\Exception\PaysonApiException
     */
    public function invoke()
    {
        $this->responseHandler = $this->connector->sendRequest($this->requestModel);
    }

    /**
     * @return Request
     */
    public function getRequestModel()
    {
        return $this->requestModel;
    }

    /**
     * @param Request $requestModel
     */
    public function setRequestModel($requestModel)
    {
        $this->requestModel = $requestModel;
    }
}
