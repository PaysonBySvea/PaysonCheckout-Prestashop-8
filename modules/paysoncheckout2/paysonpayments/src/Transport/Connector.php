<?php

namespace Payson\Payments\Transport;

use Payson\Payments\Transport\Curl;
use Payson\Payments\Exception\PaysonException;
use Payson\Payments\Exception\ExceptionCodeList;
use Payson\Payments\Model\Request;

/**
 * Connector
 *
 * @package Payson\Payments\Transport
 */
class Connector
{
    /**
     * Base URL for live server
     */
    const PROD_BASE_URL = 'https://api.payson.se/2.0';

    /**
     * Base URL for test server
     */
    const TEST_BASE_URL = 'https://test-api.payson.se/2.0';

    /**
     * Agent ID
     *
     * @var string $agentId
     */
    private $agentId;

    /**
     * API Key
     *
     * @var string $apiKey
     */
    private $apiKey;

    /**
     * Base Checkout API URL
     *
     * @var string $baseApiUrl
     */
    private $baseApiUrl;

    /**
     * HTTP client
     *
     * @var $apiClient
     */
    private $apiClient;

    /**
     * Connector
     *
     * @param string $agentId Agent Id
     * @param string $apiKey API Key
     * @param string $baseApiUrl Base URL
     */
    public function __construct($apiClient, $agentId, $apiKey, $baseApiUrl)
    {
        $this->agentId = $agentId;
        $this->apiKey = $apiKey;
        $this->baseApiUrl = $baseApiUrl;
        $this->apiClient = $apiClient;

        $this->validateData();
    }

    public function sendRequest(Request $request)
    {
        $this->createAuthorizationString($request);

        try {
            $response = $this->apiClient->sendRequest($request);

            return $response;
        } catch (PaysonException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new PaysonException('API communication error', 1010, $e);
        }
    }
    
    /**
     * Initializes connector instance
     * Defaults to test agent with Agent ID 4 and test environment
     * 
     * @param string $agentId Agent ID
     * @param string $apiKey API Key
     * @param string $apiUrl base URL
     * @return Connector
     */
    public static function init($agentId = '4', $apiKey = '2acab30d-fe50-426f-90d7-8c60a7eb31d4', $apiUrl = self::TEST_BASE_URL)
    {
        $httpClient = new ApiClient(new Curl());

        return new static($httpClient, $agentId, $apiKey, $apiUrl);
    }
    
    /**
     * Validate merchant credentials
     */
    private function validateData()
    {
        $this->validateAgentId();
        $this->validateApiKey();
        $this->validateBaseApiUrl();
    }

    /**
     * Validate merchant Agent ID
     *
     * @throws PaysonException if Agent ID is empty or not passed as string
     */
    private function validateAgentId()
    {
        if (empty(trim($this->agentId))) {
            throw new PaysonException(
                ExceptionCodeList::getErrorMessage(ExceptionCodeList::MISSING_AGENT_ID),
                ExceptionCodeList::MISSING_AGENT_ID
            );
        }
        
        if (!is_string($this->agentId)) {
            throw new PaysonException(
                "Agent ID must be passed as string!",
                ExceptionCodeList::MISSING_AGENT_ID
            );
        }
    }

    /**
     * Validate merchant API Key
     *
     * @throws PaysonException if API Key is empty or not passed as string
     */
    private function validateApiKey()
    {
        if (empty(trim($this->apiKey))) {
            throw new PaysonException(
                ExceptionCodeList::getErrorMessage(ExceptionCodeList::MISSING_API_KEY),
                ExceptionCodeList::MISSING_API_KEY
            );
        }
        
        if (!is_string($this->apiKey)) {
            throw new PaysonException(
                "API Key must be passed as string!",
                ExceptionCodeList::MISSING_API_KEY
            );
        }
    }

    /**
     * Validate API base URL
     *
     * @throws PaysonException if base API URL is empty or invalid
     */
    private function validateBaseApiUrl()
    {
        $availableUrls = array(
            self::TEST_BASE_URL,
            self::PROD_BASE_URL
        );

        if (empty($this->baseApiUrl)) {
            throw new PaysonException(
                ExceptionCodeList::getErrorMessage(ExceptionCodeList::MISSING_API_BASE_URL),
                ExceptionCodeList::MISSING_API_BASE_URL
            );
        } elseif (in_array($this->baseApiUrl, $availableUrls) !== true) {
            throw new PaysonException(
                ExceptionCodeList::getErrorMessage(ExceptionCodeList::INCORRECT_API_BASE_URL),
                ExceptionCodeList::INCORRECT_API_BASE_URL
            );
        }
    }

    /**
     * cUrl request authorization string
     * 
     * base64_encode({agentId}:{apiKey})
     */
    public function createAuthorizationString(Request $request)
    {
        $request->setAuthorizationString(base64_encode($this->agentId . ':' . $this->apiKey));
    }

    /**
     * @return string
     */
    public function getAgentId()
    {
        return $this->agentId;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @return string
     */
    public function getBaseApiUrl()
    {
        return $this->baseApiUrl;
    }
}
