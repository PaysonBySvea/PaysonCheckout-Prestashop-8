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
    public function __construct($content, $httpCode, $responseBody)
    {
        $this->content = $content;
        $this->httpCode = $httpCode;

        $this->body = $responseBody;
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
