<?php

namespace Cube\Helpers\InputValidator;

use Cube\Exceptions\InputException;
use Cube\Misc\Input;
use Cube\Misc\RequestValidator;

class InputValidatorItem
{
    private RequestValidator $validator;

    private Input $input;

    private array $errors = array();

    private array $rules = array();

    /**
     * Constructor
     *
     * @param RequestValidator $validator
     * @param Input $input
     */
    public function __construct(RequestValidator $validator, Input $input)
    {
        $this->validator = $validator;
        $this->input = $input;
    }

    /**
     * Apply rules to item
     *
     * @param array|string $rules
     * @return void
     */
    public function apply($rules)
    {
        $rules_list = is_array($rules)
            ? $this->processRules($rules)
            : array_map('trim', explode('|', $rules));

        $this->rules = array_merge($this->rules, $rules_list);
        $this->runValidation($rules_list);
    }

    /**
     * Attach error to validator item
     *
     * @param string $error
     * @param array $replacers
     * @return void
     */
    public function attachError(string $error, array $replacers = array()): void
    {
        $error = strtr($error, $replacers);
        $this->errors[] = $error;
        $this->validator->attachErrorForInput($this->getInput()->getKey(), $error);
    }

    /**
     * Check if item is valid
     *
     * @return boolean
     */
    public function isValid(): bool
    {
        return count($this->errors) < 1;
    }

    /**
     * Get input attached to item
     *
     * @return Input
     */
    public function getInput(): Input
    {
        return $this->input;
    }

    /**
     * Get current request validator
     *
     * @return RequestValidator
     */
    public function getRequestValidator(): RequestValidator
    {
        return $this->validator;
    }

    /**
     * Process rules
     *
     * @param array $rules
     * @return array
     */
    private function processRules(array $rules): array
    {
        $rules_list = [];
        every($rules, function ($value, $index) use (&$rules_list) {
            $has_message = !is_numeric($index);

            if (!$has_message) {
                $processed_rules = explode('|', $value);
                return $rules_list = array_merge($rules_list, $processed_rules);
            }

            return $rules_list[] = concat($index, ':', $value);
        });

        return array_merge($this->rules, $rules_list);
    }

    /**
     * Run validation
     *
     * @param array $rules
     * @return void
     */
    private function runValidation(array $rules): void
    {
        every($rules, function ($name) {
            $this->compileRule($name);
        });
    }

    /**
     * Compile rule
     *
     * @param string $rule
     * @return mixed
     */
    private function compileRule(string $rule)
    {
        $rule_vars = explode(':', $rule);
        $rule_class_id = $rule_vars[0];
        $args = array_slice($rule_vars, 1);
        $rule_class_name = RequestValidator::getRule($rule_class_id);

        if (!is_callable([$rule_class_name, 'rule'])) {
            throw new InputException(
                sprintf('"%s" is not a valid rule', $rule_class_name)
            );
        }

        return call_user_func_array(
            [$rule_class_name, 'rule'],
            [$this, ...$args]
        );
    }
}
