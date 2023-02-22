<?php

namespace Cube\Misc;

use Cube\App\App;
use Cube\Exceptions\InputException;
use Cube\Helpers\InputValidator\InputValidatorItem;
use Cube\Helpers\InputValidator\Rules\AmountRule;
use Cube\Helpers\InputValidator\Rules\EmailRule;
use Cube\Helpers\InputValidator\Rules\MaxLengthRule;
use Cube\Helpers\InputValidator\Rules\MinLengthRule;
use Cube\Helpers\InputValidator\Rules\NumberRule;
use Cube\Helpers\InputValidator\Rules\RequiredRule;
use Cube\Helpers\InputValidator\Rules\UrlRule;
use Cube\Http\Request;

class RequestValidator
{
    private Request $request;

    private array $_errors = array();

    private static array $_loaded_rules = array();

    private static bool $_has_loaded_rules = false;

    private static array $_common_rules = array(
        'min_length' => MinLengthRule::class,
        'max_length' => MaxLengthRule::class,
        'required' => RequiredRule::class,
        'number' => NumberRule::class,
        'amount' => AmountRule::class,
        'email' => EmailRule::class,
        'url' => UrlRule::class
    );

    /**
     * Constructor
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        self::loadRules();
    }

    /**
     * Add rules
     *
     * @param array $rules
     * @return $this
     */
    public function addRules(array $rules): self
    {
        every($rules, function ($rules_list, $key) {
            $item = new InputValidatorItem(
                $this,
                $this->request->input($key)
            );
            $item->apply($rules_list);
        });

        return $this;
    }

    /**
     * Get errors list
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->_errors;
    }

    /**
     * Get first error message
     *
     * @return string|null
     */
    public function getFirstError(): ?string
    {
        if($this->isValid()) {
            return null;
        }

        $first_key = array_keys($this->_errors)[0];
        return $this->_errors[$first_key][0];
    }

    /**
     * Return if validator is valid
     *
     * @return boolean
     */
    public function isValid(): bool
    {
        return (count($this->_errors) === 0);
    }

    /**
     * Attach Error
     *
     * @param string $name
     * @param string $error
     * @return $this
     */
    public function attachErrorForInput(string $name, string $error)
    {
        if(!isset($this->_errors[$name])) {
            $this->_errors[$name] = array();
        }

        $this->_errors[$name][] = $error;
        return $this;
    }

    /**
     * Load configured rules
     *
     * @return bool
     */
    public static function loadRules(): bool
    {
        if(static::$_has_loaded_rules) {
            return true;
        }

        $common_rules = static::$_common_rules;
        $config = App::getConfig('rules');

        $custom_rules = is_array($config) ? $config : [];
        static::$_loaded_rules = array_merge($common_rules, $custom_rules);
        static::$_has_loaded_rules = true;

        return true;
    }

    /**
     * Get rule
     *
     * @param string $name
     * @return string|callable
     */
    public static function getRule(string $name)
    {
        if(!self::$_has_loaded_rules) {
            self::loadRules();
        }

        if(!self::isRegistered($name)) {
            throw new InputException(
                sprintf('"%s" is not a registered input validator rule', $name)
            );
        }

        return self::$_loaded_rules[$name];
    }

    /**
     * Check if rule is registered & loaded
     *
     * @param string $name
     * @return boolean
     */
    public static function isRegistered(string $name): bool
    {
        return isset(self::$_loaded_rules[$name]);
    }
}