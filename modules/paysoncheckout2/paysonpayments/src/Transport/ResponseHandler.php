<?php

namespace Payson\Payments\Transport;

use Payson\Payments\Exception\PaysonException;

/**
 * Class ResponseHandler - HTTP response handler
 *
 * @package Payson\Payments\Transport
 */
class ResponseHandler
{
    /**
     * API response success codes
     * 200 - OK
     * 201 - Created
     * @var array
     */
    private $httpSuccessCodes = array(200, 201);

    /**
     * Payson API response
     *
     * @var mixed $content
     */
    private $content;

    /**
     * @var array $header
     */
    private $header;

    /**
     * Json string
     *
     * @var string $body
     */
    private $body;

    /**
     * @var int $httpCode
     */
    private $httpCode;


    /**
     * ResponseHandler constructor.
     *
     * @param $content
     * @param $httpCode
     */
    public function __construct($content, $httpCode, $responseHeader, $responseBody)
    {
        $this->content = $content;
        $this->httpCode = $httpCode;

        $this->header = $responseHeader;
        $this->body = $responseBody;
        
        //$this->setHeader();
        //$this->setBody();
    }

    /**
     * Handle response.
     * Prepare error message if response is not successful
     *
     * @throws PaysonException
     */
    public function handleClientResponse()
    {
        if (!in_array($this->httpCode, $this->httpSuccessCodes)) {
            $errorMessage = 'Undefined error occurred.';
            $errorCode = null;

            if (!empty($this->body) && ($this->body != "null")) {
                $errorContent = $this->getContent();
                if (isset($errorContent['code'])) {
                    $errorCode = $errorContent['code'];
                }
                if (isset($errorContent['message'])) {
                    $errorMessage = $errorContent['message'] . ' ';
                }
                if (isset($errorContent['errors']) && is_array($errorContent['errors'])) {
                    $error = $errorContent['errors'][0];
                    //foreach ($errorContent['errors'] as $error) {
                        if (isset($error['property'])) {
                            $errorMessage .= $error['property'] . ', ';
                        }
                        $errorMessage .= $error['message'];
                    //}
                }
            } else {
                if (isset($this->header['http_code'])) {
                    $errorMessage = $this->header['http_code'];
                }
                if (isset($this->header['errorMessage'])) {
                    $errorMessage = $this->header['errorMessage'];
                }
                $errorCode = $this->httpCode;
            }

            throw new PaysonException($errorMessage, $errorCode);
        }
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Prepare body
     */
    public function setBody()
    {
        /**
         * Split the string on "double" new line.
         * We use Windows "end of line" char
         */
        $arrRequests = explode("\r\n\r\n", $this->content, 2); // Split on first occurrence

        if (is_array($arrRequests) && count($arrRequests) > 1) {
            $this->body = $arrRequests[1];
        }
    }

    /**
     * @return mixed
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }

    /**
     * @return array
     */
    public function getResponse()
    {
        $returnData = array();

        $bodyContent = $this->getContent();
        if ($bodyContent !== null) {
            $returnData = $bodyContent;
        }

        return $returnData;
    }

    /**
     * @return array
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Create array of header information
     */
    public function setHeader()
    {
        $headers = array();

        /**
         * Split the string on "double" new line.
         * We use Windows "end of line" char
         */
        $arrRequests = explode("\r\n\r\n", $this->content); // Split on first occurrence
        $headerLines = explode("\r\n", $arrRequests[0]); // Split on first occurrence
        $headers['http_code'] = $headerLines[0];

        foreach ($headerLines as $i => $line) {
            if ($i > 0) {
                list ($key, $value) = explode(':', $line, 2); // Split on first occurrence
                $headers[trim($key)] = trim($value);
            }
        }

        $this->header = $headers;
    }

    /**
     * Return response content
     *
     * @return mixed
     * @throws PaysonException
     */
    public function getContent()
    {
        $result = json_decode($this->body, true);

        if ($result === null && $this->body !== '') {
            throw new PaysonException('Response format is not valid, JSON decode error', 1000);
        }

        return $result;
    }
}
