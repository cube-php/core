<?php

namespace Cube\Helpers\InputValidator\Rules;

use Cube\Helpers\InputValidator\InputValidatorItem;
use Cube\Interfaces\InputValidatorRuleInterface;

class MinLengthRule implements InputValidatorRuleInterface
{
    private static $defaultMessage = '{input} should not be lesser than {length} chars';
    
    public static function rule(InputValidatorItem $validator, int $length, ?string $message = null) {

        $input = $validator->getInput();

        if(strlen($input->getValue()) < $length) {
            $validator->attachError($message ?: self::$defaultMessage, [
                '{length}' => $length,
                '{input}' => $input->getKey()
            ]);
        }
    }
}