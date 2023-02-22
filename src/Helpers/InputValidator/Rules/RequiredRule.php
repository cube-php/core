<?php

namespace Cube\Helpers\InputValidator\Rules;

use Cube\Helpers\InputValidator\InputValidatorItem;
use Cube\Interfaces\InputValidatorRuleInterface;

class RequiredRule implements InputValidatorRuleInterface
{
    private static $defaultMessage = '{input} is required';

    public static function rule(InputValidatorItem $validator, ?string $message = null)
    {
        if(empty($validator->getInput()->getValue())) {
            $validator->attachError($message ?: self::$defaultMessage, [
                '{input}' => $validator->getInput()->getKey()
            ]);
        }
    }
}