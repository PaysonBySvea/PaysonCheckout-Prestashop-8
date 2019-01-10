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

namespace Payson\Payments\Transport;

use Payson\Payments\Exception\PaysonException;
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
        $prestaTools = new \ToolsCore();
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
        $responseBody = $prestaTools::substr($httpResponse, $headerSize);
        
        $this->curlClient->close();

        if ($errorNumber > 0) {
            throw new PaysonException($httpError, ExceptionCodeList::COMMUNICATION_ERROR);
        }

        $responseHandler = new ResponseHandler($httpResponse, $httpCode, $responseBody);
        $responseHandler->handleClientResponse();
        
        return $responseHandler;
    }
}
