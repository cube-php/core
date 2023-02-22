<?php

namespace Cube\Helpers\InputValidator\Rules;

use Cube\Helpers\InputValidator\InputValidatorItem;
use Cube\Interfaces\InputValidatorRuleInterface;

class MaxLengthRule implements InputValidatorRuleInterface
{
    private static $defaultMessage = '{input} should not exceed {length} chars';
    
    public static function rule(InputValidatorItem $validator, int $length, ?string $message = null) {

        $input = $validator->getInput();

        if(strlen($input->getValue()) > $length) {
            $validator->attachError($message ?: self::$defaultMessage, [
                '{length}' => $length,
                '{input}' => $input->getKey()
            ]);
        }
    }
}