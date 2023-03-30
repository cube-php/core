<?php

namespace Cube\Helpers\InputValidator\Rules;

use Cube\Helpers\InputValidator\InputValidatorItem;
use Cube\Interfaces\InputValidatorRuleInterface;

class EqualsRule implements InputValidatorRuleInterface
{
    private static $defaultMessage = '{input} is not equal to {field}';

    public static function rule(InputValidatorItem $validator, string $field, ?string $message = null)
    {
        $validator->apply('required');
        $validator_input = $validator->getInput();

        $request = $validator->getRequestValidator()->getRequest();
        $request_input = $request->input($field);

        if (!$request_input || !$request_input->equals($validator_input->getValue())) {
            $validator->attachError($message ?: self::$defaultMessage, [
                '{input}' => $validator->getInput()->getKey(),
                '{field}' => $field
            ]);
        }
    }
}
