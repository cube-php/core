<?php

namespace Cube\Helpers\InputValidator\Rules;

use Cube\Helpers\InputValidator\InputValidatorItem;
use Cube\Interfaces\InputValidatorRuleInterface;

class UrlRule implements InputValidatorRuleInterface
{
    private static $defaultMessage = '{input} is not a valid URL';

    public static function rule(InputValidatorItem $validator, ?string $message = null)
    {
        if(!$validator->getInput()->isUrl()) {
            $validator->attachError($message ?: self::$defaultMessage, [
                '{input}' => $validator->getInput()->getKey()
            ]);
        }
    }
}