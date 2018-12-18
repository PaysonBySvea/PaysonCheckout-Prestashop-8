<?php

namespace Payson\Payments\Transport;

use Payson\Payments\PaysonException;
use Payson\Payments\Exception\ExceptionCodeList;
use Payson\Payments\Model\Request;

/**
 * Class ApiClient
 *
 * @package Payson\Payments\Transport
 */
class ApiClient
{
    /**
     *
     * @var $curlClient
     */
    private $curlClient;

    /**
     * Client
     *
     * @param $curlClient
     */
    public function __construct($curlClient)
    {
        $this->curlClient = $curlClient;
    }

    /**
     * Send request
     *
     * @param Request $request Request model
     * @throws PaysonException when an error is encountered
     * @return ResponseHandler
     */
    public function sendRequest(Request $request)
    {
        //echo $request->getApiUrl();
        //echo $request->getMethod();
        //echo $request->getAuthorizationString();
        $header = array();
        $header[] = 'Content-Type: application/json';
        $header[] = 'Authorization: Basic ' . $request->getAuthorizationString();

        $this->curlClient->init();
        $this->curlClient->setOption(CURLOPT_URL, $request->getApiUrl());
        $this->curlClient->setOption(CURLOPT_HTTPHEADER, $header);
        $this->curlClient->setOption(CURLOPT_RETURNTRANSFER, 1);
        $this->curlClient->setOption(CURLOPT_HEADER, 1);
        $this->curlClient->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curlClient->setOption(CURLOPT_CUSTOMREQUEST, $request->getMethod());
        $this->curlClient->setOption(CURLOPT_POSTFIELDS, $request->getBody());

        $httpResponse = $this->curlClient->execute();
        $httpCode = $this->curlClient->getInfo(CURLINFO_HTTP_CODE);

        $httpError = $this->curlClient->getError();
        $errorNumber = $this->curlClient->getErrorNumber();

        $headerSize = $this->curlClient->getInfo(CURLINFO_HEADER_SIZE);
        $responseHeader = substr($httpResponse, 0, $headerSize);
        $responseBody = substr($httpResponse, $headerSize);
        
        $this->curlClient->close();

        if ($errorNumber > 0) {
            throw new PaysonException($httpError, ExceptionCodeList::COMMUNICATION_ERROR);
        }

        $responseHandler = new ResponseHandler($httpResponse, $httpCode, $responseHeader, $responseBody);
        $responseHandler->handleClientResponse();
        
        return $responseHandler;
    }
}
