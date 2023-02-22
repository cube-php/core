<?php

namespace Cube\Helpers\InputValidator\Rules;

use Cube\Helpers\InputValidator\InputValidatorItem;
use Cube\Interfaces\InputValidatorRuleInterface;

class AmountRule implements InputValidatorRuleInterface
{
    private static $defaultMessage = '{input} is not a valid amount';

    public static function rule(InputValidatorItem $validator, ?string $message = null)
    {
        $validator->apply('required|number:' . $message);
        $amt = (float) $validator->getInput()->getValue();

        if($amt <= 0) {
            $validator->attachError($message ?: self::$defaultMessage, [
                '{input}' => $validator->getInput()->getKey()
            ]);
        }
    }
}