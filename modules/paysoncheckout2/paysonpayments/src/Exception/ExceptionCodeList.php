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

namespace Payson\Payments\Exception;

class ExceptionCodeList
{
    const COMMUNICATION_ERROR = 10000;
    const MISSING_AGENT_ID = 20001;
    const MISSING_API_KEY = 20002;
    const MISSING_API_BASE_URL = 20003;
    const INCORRECT_API_BASE_URL = 20004;
    const INPUT_VALIDATION_ERROR = 30000;
    const UNKNOWN_CODE_MESSAGE = 'Unknown error';

    /**
     * Return Message for exception code
     *
     * @param  $exceptionCode
     * @return string
     */
    public static function getErrorMessage($exceptionCode)
    {
        $exceptionCode = (int)$exceptionCode;

        $exceptionMessageList = array(
            self::COMMUNICATION_ERROR => 'API Client Error',
            self::MISSING_AGENT_ID => 'Missing Agent Id',
            self::MISSING_API_KEY => 'Missing API Key',
            self::MISSING_API_BASE_URL => 'Missing API Base URL',
            self::INCORRECT_API_BASE_URL => 'Incorrect API Base URL',
            self::INPUT_VALIDATION_ERROR => 'Input Validation Error'
        );

        if (isset($exceptionMessageList[$exceptionCode])) {
            return $exceptionMessageList[$exceptionCode];
        }

        return self::UNKNOWN_CODE_MESSAGE;
    }
}
