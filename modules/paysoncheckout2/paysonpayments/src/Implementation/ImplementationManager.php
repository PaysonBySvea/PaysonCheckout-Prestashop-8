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
