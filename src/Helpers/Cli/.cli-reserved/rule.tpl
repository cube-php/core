<?php

namespace App\Rules;

use Cube\Helpers\InputValidator\InputValidatorItem;
use Cube\Interfaces\InputValidatorRuleInterface;

class {className} implements InputValidatorRuleInterface
{

    private static $defaultMessage = '';

    public static function rule(InputValidatorItem $validator, ?string $message = null)
    {
        //handle rule
        $value = $validator->getInput()->getValue();
    }
}