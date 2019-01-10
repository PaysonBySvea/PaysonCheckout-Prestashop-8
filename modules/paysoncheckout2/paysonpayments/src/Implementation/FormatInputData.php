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

use Payson\Payments\Exception\ExceptionCodeList;
use Payson\Payments\Exception\PaysonException;

final class FormatInputData
{
    public static function formatArrayKeysToLower($data)
    {
        if (!is_array($data)) {
            throw new PaysonException(
                "Input data must be array!",
                ExceptionCodeList::INPUT_VALIDATION_ERROR
            );
        }

        return self::lowerArrayKeys($data);
    }

    private static function lowerArrayKeys(array $input)
    {
        $prestaTools = new \ToolsCore();

        $return = array();

        foreach ($input as $key => $value) {
            $key = $prestaTools::strtolower($key);

            if (is_array($value)) {
                $value = self::lowerArrayKeys($value);
            }

            $return[$key] = $value;
        }

        return $return;
    }
}
