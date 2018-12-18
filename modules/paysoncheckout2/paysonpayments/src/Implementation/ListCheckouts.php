<?php

namespace Payson\Payments\Implementation;

use Payson\Payments\Model\Request;

class ListCheckouts extends ImplementationManager
{
    protected $apiUrl = '/Checkouts/';

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
        $queryString = '?status=' . (isset($data['status'])?$data['status']:'') . '&page=' . (isset($data['page'])?$data['page']:1);
        $this->requestModel = new Request();
        $this->requestModel->setGetMethod();
        $this->requestModel->setApiUrl($this->connector->getBaseApiUrl() . $this->apiUrl . $queryString);
        //$this->requestModel->setApiUrl($this->connector->getBaseApiUrl() . $this->apiUrl);
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
