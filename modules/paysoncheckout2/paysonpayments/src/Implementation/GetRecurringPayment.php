<?php

namespace Payson\Payments\Implementation;

use Payson\Payments\Model\Request;

class GetRecurringPayment extends ImplementationManager
{
    protected $apiUrl = '/RecurringPayments/';

    /**
     * Request body - JSON
     *
     * @var Request $requestModel
     */
    private $requestModel;

    /**
     * @param array $data
     * @throws PaysonException
     */
    public function validateData($data)
    {
        $validator = $this->validator;
        $validator->validate($data);
    }

    /**
     * Prepare body data for API call
     *
     * @param array $data
     */
    public function prepareData($data)
    {
        $checkoutId = $data['id'];
        $this->requestModel = new Request();
        $this->requestModel->setGetMethod();
        $this->requestModel->setApiUrl($this->connector->getBaseApiUrl() . $this->apiUrl . $checkoutId);
    }

    /**
     * Invoke request call
     *
     * @throws PaysonException
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
