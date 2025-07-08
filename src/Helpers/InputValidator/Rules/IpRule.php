<?php

namespace Cube\Helpers\InputValidator\Rules;

use Cube\Helpers\InputValidator\InputValidatorItem;
use Cube\Interfaces\InputValidatorRuleInterface;

class IpRule implements InputValidatorRuleInterface

{
    private static $defaultMessage = '{input} is not a valid IP address';

    public static function rule(InputValidatorItem $validator, ?string $message = null)
    {
        $input = $validator->getInput();

        if (!filter_var($input->getValue(), FILTER_VALIDATE_IP)) {
            $validator->attachError($message ?: self::$defaultMessage, [
                '{input}' => $input->getKey()
            ]);
        }
    }
}
