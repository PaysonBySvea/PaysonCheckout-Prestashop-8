<?php

namespace Payson\Payments\Implementation;

use Payson\Payments\Transport\ResponseHandler;

/**
 * Interface ImplementationInterface
 * @package Payson\Payments\Implementation
 */
interface ImplementationInterface
{
    /**
     * Template pattern for all implementations
     * @param array $data
     */
    public function execute($data);

    /**
     * @return ResponseHandler
     */
    public function getResponseHandler();
}
