<?php

namespace Cube\Helpers\InputValidator\Rules;

use Cube\Helpers\InputValidator\InputValidatorItem;
use Cube\Interfaces\InputValidatorRuleInterface;

class EmailRule implements InputValidatorRuleInterface
{
    private static $defaultMessage = '{input} is not a valid Email';

    public static function rule(InputValidatorItem $validator, ?string $message = null)
    {
        if(!$validator->getInput()->isEmail()) {
            $validator->attachError($message ?: self::$defaultMessage, [
                '{input}' => $validator->getInput()->getKey()
            ]);
        }
    }
}