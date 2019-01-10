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
