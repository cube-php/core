<?php

namespace Cube\Helpers\InputValidator\Rules;

use Cube\Helpers\InputValidator\InputValidatorItem;
use Cube\Interfaces\InputValidatorRuleInterface;

class NumberRule implements InputValidatorRuleInterface
{
    private static $defaultMessage = '{input} is not a valid number';

    public static function rule(InputValidatorItem $validator, ?string $message = null)
    {
        if (!is_numeric($validator->getInput()->getValue())) {
            $validator->attachError($message ?: self::$defaultMessage, [
                '{input}' => $validator->getInput()->getKey()
            ]);
        }
    }
}
