<?php

namespace Payson\Payments\Model;

/**
 * Class Request
 * Request model - Data for request to the API
 *
 * @package Payson\Payments\Model
 */
class Request
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';

    /**
     * Authorization string
     *
     * @var string $authorizationString
     */
    private $authorizationString;

    /**
     * Request body
     *
     * @var string $body
     */
    private $body;

    /**
     * Request method
     *
     * @var string $method
     */
    private $method;

    /**
     * API Url
     *
     * @var string $apiUrl
     */
    private $apiUrl;

    private $uriParameters;

    private $timestamp;

    /**
     * Return authorization string
     *
     * @return string
     */
    public function getAuthorizationString()
    {
        return $this->authorizationString;
    }

    /**
     * Set authorization string
     *
     * @param string $authorizationString
     */
    public function setAuthorizationString($authorizationString)
    {
        $this->authorizationString = $authorizationString;
    }

    /**
     * Return request body data as json
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set request body data as json
     *
     * @param string|mixed $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set POST method
     */
    public function setPostMethod()
    {
        $this->method = self::METHOD_POST;
    }

    /**
     * Set GET method
     */
    public function setGetMethod()
    {
        $this->method = self::METHOD_GET;
    }

    /**
     * Set PUT method
     */
    public function setPutMethod()
    {
        $this->method = self::METHOD_PUT;
    }

    /**
     * Return the API uri parameters
     *
     * @return string
     */
    public function getUriParameters()
    {
        return $this->uriParameters;
    }

    /**
     * Set the API uri parameters
     *
     * @param array $uriParameters
     */
    public function setUriParameters($uriParameters)
    {
        $uriParametersString = "?";
        foreach($uriParameters as $key => $value)
        {
            if(is_bool($value))
            {
                $value = $value ? 'true' : 'false';
            }
            $uriParametersString = $uriParametersString . $key . "=" . $value . "&";
        }
        $uriParametersString = substr_replace($uriParametersString, "", -1);
        $this->uriParameters = $uriParametersString;
    }

    /**
     * Return full request API url
     *
     * @return string
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * Set API url
     *
     * @param string $apiUrl
     */
    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = $apiUrl;
    }

    /**
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param mixed $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }
}
