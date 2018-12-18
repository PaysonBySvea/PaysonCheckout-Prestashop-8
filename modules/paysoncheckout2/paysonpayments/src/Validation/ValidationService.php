<?php

namespace Payson\Payments\Validation;

use Payson\Payments\Exception\ExceptionCodeList;
use Payson\Payments\Exception\PaysonException;

abstract class ValidationService
{
    /**
     * @param mixed $data
     */
    abstract public function validate($data);
    
    /**
     * @param mixed $data
     * @param string $dataTitle
     * @throws PaysonException
     */
    protected function mustNotBeEmpty($data, $dataTitle)
    {
        if (empty($data)) {
            throw new PaysonException(
                "$dataTitle must not be empty!",
                ExceptionCodeList::INPUT_VALIDATION_ERROR
            );
        }
    }

    /**
     * @param array $data
     * @param string $paramKey
     * @param string $paramTitle
     * @throws PaysonException
     */
    protected function mustBeSet($data, $paramKey, $paramTitle)
    {
        if (!isset($data[$paramKey])) {
            throw new PaysonException(
                "$paramTitle must be set!",
                ExceptionCodeList::INPUT_VALIDATION_ERROR
            );
        }
    }

    /**
     * @param array  $data
     * @param string $paramTitle
     * @throws PaysonException
     */
    protected function mustBeString($data, $paramTitle)
    {
        if (!is_string($data) || $data == "") {
            throw new PaysonException(
                "$paramTitle must be passed as string and can't be empty!",
                ExceptionCodeList::INPUT_VALIDATION_ERROR
            );
        }
    }

    /**
     * @param array  $data
     * @param string $paramTitle
     * @throws PaysonException
     */
    protected function mustBeBoolean($data, $paramTitle)
    {
        if (!is_bool($data)) {
            throw new PaysonException(
                "$paramTitle must be passed as a boolean and can't be empty!",
                ExceptionCodeList::INPUT_VALIDATION_ERROR
            );
        }
    }

    /**
     * @param mixed $data
     * @param string $dataTitle
     * @throws PaysonException
     */
    protected function mustBeFloat($data, $dataTitle)
    {
        $this->mustNotBeEmpty($data, $dataTitle);
        if (!is_float($data)) {
            throw new PaysonException(
                "$dataTitle must be passed as decimal!",
                ExceptionCodeList::INPUT_VALIDATION_ERROR
            );
        }
    }
    
    /**
     * @param mixed $data
     * @param string $dataTitle
     * @throws PaysonException
     */
    protected function mustBeInteger($data, $dataTitle)
    {
        $this->mustNotBeEmpty($data, $dataTitle);
        if (!is_int($data)) {
            throw new PaysonException(
                "$dataTitle must be passed as integer!",
                ExceptionCodeList::INPUT_VALIDATION_ERROR
            );
        }
    }

    /**
     * @param mixed $data
     * @param string $dataTitle
     * @throws PaysonException
     */
    protected function mustBeArray($data, $dataTitle)
    {
        if (!is_array($data)) {
            throw new PaysonException(
                "$dataTitle must be passed as array!",
                ExceptionCodeList::INPUT_VALIDATION_ERROR
            );
        }
    }

    /**
     * @param mixed $data
     * @param string $dataTitle
     * @throws PaysonException
     */
    protected function mustNotBeEmptyArray($data, $dataTitle)
    {
        if (!is_array($data)) {
            throw new PaysonException(
                "$dataTitle must be passed as array!",
                ExceptionCodeList::INPUT_VALIDATION_ERROR
            );
        }

        if (count($data) < 1) {
            throw new PaysonException(
                "$dataTitle must not be empty array!",
                ExceptionCodeList::INPUT_VALIDATION_ERROR
            );
        }
    }
    
    /**
     * @param mixed $data
     * @param array $haystack
     * @param string $dataTitle
     * @throws PaysonException
     */
    protected function mustBeInArray($data, $hayStack, $dataTitle)
    {
        if (!in_array($data, $hayStack)) {
            throw new PaysonException(
                "$dataTitle is invalid! Valid values are: " . implode(', ', $hayStack),
                ExceptionCodeList::INPUT_VALIDATION_ERROR
            );
        }

        if (count($data) < 1) {
            throw new PaysonException(
                "$dataTitle must not be empty array!",
                ExceptionCodeList::INPUT_VALIDATION_ERROR
            );
        }
    }

    /**
     * @param mixed $data
     * @param integer $minLength
     * @param integer $maxLength
     * @param string $dataTitle
     * @throws PaysonException
     */
    protected function lengthMustBeBetween($data, $minLength, $maxLength, $dataTitle)
    {
        $size = strlen($data);

        if ($size > $maxLength) {
            throw new PaysonException(
                "$dataTitle must contain maximum $maxLength characters!",
                ExceptionCodeList::INPUT_VALIDATION_ERROR
            );
        }

        if ($size < $minLength) {
            throw new PaysonException(
                "$dataTitle must contain minimum $minLength characters!",
                ExceptionCodeList::INPUT_VALIDATION_ERROR
            );
        }
    }
}
