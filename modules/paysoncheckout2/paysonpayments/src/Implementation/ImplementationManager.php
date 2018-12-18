<?php

namespace Payson\Payments\Implementation;

use Payson\Payments\Transport\Connector;
use Payson\Payments\Transport\ResponseHandler;
use Payson\Payments\Validation\ValidationService;

/**
 * Class ImplementationManager
 * @package Payson\Payments\Implementation
 */
abstract class ImplementationManager implements ImplementationInterface
{
    /**
     * Transport connector
     *
     * @var Connector $connector
     */
    protected $connector;

    /**
     * API response content - Json
     *
     * @var ResponseHandler $responseHandler
     */
    protected $responseHandler;

    /**
     * @var ValidationService
     */
    protected $validator;

    /**
     * @param Connector $connector
     * @param ValidationService $validationService
     */
    public function __construct(Connector $connector, ValidationService $validationService)
    {
        $this->connector = $connector;
        $this->validator = $validationService;
    }

    /**
     *
     * @param array $data
     */
    public function execute($data)
    {
        $data = FormatInputData::formatArrayKeysToLower($data);
        $this->validateData($data);
        $this->prepareData($data);
        $this->invoke();
    }

    /**
     * Return API response
     *
     * @return mixed
     */
    public function getResponseHandler()
    {
        return $this->responseHandler;
    }

    /**
     * Input data validation
     * @param array $data
     */
    abstract public function validateData($data);

    /**
     * Prepare body data
     * @param array $data
     */
    abstract public function prepareData($data);

    /**
     * Invoke API
     */
    abstract public function invoke();
}
