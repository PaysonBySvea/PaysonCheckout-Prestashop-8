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

/**
 * Class Curl
 *
 * @package Payson\Payments\Transport
 */
class Curl
{
    /**
     * @var null|resource
     */
    private $handle = null;

    /**
     * @param $name
     * @param $value
     */
    public function setOption($name, $value)
    {
        curl_setopt($this->handle, $name, $value);
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        return curl_exec($this->handle);
    }
    
    /**
     * @param $name
     * @return mixed
     */
    public function getInfo($name)
    {
        return curl_getinfo($this->handle, $name);
    }

    /**
     * @return mixed
     */
    public function getFullInfo()
    {
        return curl_getinfo($this->handle);
    }

    /**
     * @return string
     */
    public function getError()
    {
        return curl_error($this->handle);
    }

    /**
     * Return error number (If error exist error number will be grater than 0)
     *
     * @return int
     */
    public function getErrorNumber()
    {
        return curl_errno($this->handle);
    }

    /**
     * Close a cURL session
     */
    public function close()
    {
        curl_close($this->handle);
    }

    /**
     * Init cURL session
     */
    public function init()
    {
        $this->handle = curl_init();
    }
}
