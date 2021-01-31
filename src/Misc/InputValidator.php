<?php

namespace Cube\Misc;

use Closure;
use InvalidArgumentException;

class InputValidator
{
    
    /**
     * Input identifier
     * 
     * @param string
     */
    private $_id;

    /**
     * Input
     * 
     * @var mixed
     */
    private $_input;

    /**
     * Custom validation methods
     * 
     * @param
     */
    private static $_custom_validators = [];

    /**
     * Validation errors
     * 
     * @var array
     */
    private static $_validation_errors = [];

    /**
     * Validation error messages
     *
     * @var array
     */
    private static $_validation_errors_msgs = [];

    /**
     * Validator messages
     * 
     * @var array
     */
    private static $_messages = array(
        'max_length' => '{input} should not exceed a {length} characters',
        'min_length' => '{input} should be a minimum of {length} characters',
        'email' => '{input} is not a valid email address',
        'url' => '{input} is not a valid url',
        'number' => '{input} is not a number',
        'required' => '{input} is required',
        'equals' => '{input} must be equal to {value}',
        'greater_than' => '{input} must be greater than {value}',
        'lesser_than' => '{input} must be lesser than {value}'
    );

    /**
     * Constructor
     * 
     * @param string $id Input identifier
     * @param mixed $input
     */
    public function __construct($id, $input)
    {
        $this->_input = $input;
        $this->_id = $id;
    }

    /**
     * Callung anonymous methods
     *
     * @param string $name Method name
     * @param array $args Arguments
     * @return self
     */
    public function __call($name, $args)
    {

        $validators = static::$_custom_validators;

        if(!$this->hasValidation($name)) {
            throw new InvalidArgumentException('Unassigned validation method "' .$name. '"');
        }

        $validator = $this;
        $method_name = $validators[$name];
        $args = array_merge([$validator], $args);

        call_user_func_array($method_name, $args);
        return $validator;
    }

    /**
     * Attach new validation error
     * 
     * @return self
     */
    public function attachError($error, $vars = [])
    {
       $vars['input'] = strtolower(self::methodify($this->_id, ' '));

        foreach($vars as $key => $value) {
            $replacer = '{'. $key .'}';
            $error = str_replace($replacer, $value, $error);
        }

        static::$_validation_errors_msgs[] = $error;
        static::$_validation_errors[$this->_id][] = $error;

        return $this;
    }

    /**
     * Extend validator
     * 
     * @param array $methods Methods to extend the validator
     * 
     * @return void
     */
    public static function extend($methods)
    {
       foreach($methods as $name => $method) {
           static::$_custom_validators[static::methodify($name)] = $method;
       }
    }

    /**
     * Return error at specified index
     *
     * @param int $index
     * @return string
     */
    public static function getErrorByIndex($index)
    {
        return static::$_validation_errors_msgs[$index] ?? null;
    }

    /**
     * Return validator errors
     * 
     * @return array
     */
    public static function getErrors()
    {
        return static::$_validation_errors;
    }

    /**
     * Return the first error
     *
     * @return string
     */
    public static function getFirstError()
    {
        return static::getErrorByIndex(0);
    }

    /**
     * Return all errors
     *
     * @return string[]
     */
    public static function getListedErrors()
    {
        return static::$_validation_errors_msgs;
    }

    /**
     * Check if validator has been assigned
     *
     * @param string $name Validator method name
     * @return boolean
     */
    public function hasValidation($name)
    {
        return array_key_exists($name, static::$_custom_validators);
    }

    /**
     * Starts/refreshes input validator
     * 
     * @param array $extensions Callback functions to extend validator
     * 
     * @return void
     */
    public static function init($extensions = [])
    {
        static::$_validation_errors_msgs = static::$_validation_errors = [];

        #If there are any extensions call the extend method
        #And pass in the extensions
        if($extensions) {
            static::extend($extensions);
        }
    }

    /**
     * Check if validations are valid
     * 
     * @return bool
     */
    public static function isValid()
    {
        $errors = self::getErrors();
        return (count($errors) == 0);
    }

    /**
     * Get error message
     *
     * @param string $field Field name
     * @param string $msg Custom message
     * @return string
     */
    private static function getMessage($field, $msg)
    {
        return $msg ? $msg : static::$_messages[$field];
    }
    
    /**
     * Check if input's value is equal to value
     *
     * @param string|int|Input $value
     * @param string|null $msg Custom error message
     * 
     * @return self
     */
    public function equals($value, $msg = null)
    {
        if($value instanceof Input) {
            $value = $value->getValue();
        }
        
        $msg = static::getMessage('equals', $msg);

        if($value != $this->getValue()) {
            $this->attachError($msg, ['value' => $value]);
        }

        return $this;
    }

    /**
     * Email validator
     * 
     * @param string $msg Custom error message
     * 
     * @return self
     */
    public function email($msg = null)
    {
        $msg = static::getMessage('email', $msg);

        if(!filter_var($this->getValue(), FILTER_VALIDATE_EMAIL)) {
            $this->attachError($msg);
        }

        return $this;
    }

    /**
     * Return validator id
     * 
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Return validator value
     * 
     * @return string
     */
    public function getValue()
    {
        return $this->_input;
    }

    /**
     * Greater than validator
     * check if specified value is greater than input's value
     *
     * @param mixed $value
     * @param string|null $msg
     * @return self
     */
    public function greaterThan($value, $msg = null)
    {
        $msg = static::getMessage('greater_than', $msg);

        if($this->getValue() > $value) {
            $this->attachError($msg, [
                'value' => $value
            ]);
        }

        return $this;
    }

    /**
     * Lesser than validator
     * check if specified value is lesser than input's value
     *
     * @param mixed $value
     * @param string|null $msg
     * @return self
     */
    public function lesserThan($value, $msg = null)
    {
        $msg = static::getMessage('lesser_than', $msg);

        if($this->getValue() < $value) {
            $this->attachError($msg, [
                'value' => $value
            ]);
        }

        return $this;
    }
    
    /**
     * Maximum length validator
     * 
     * @param int $length
     * @param string $msg Custom error message
     * 
     * @return self
     */
    public function maxLength($length, $msg = null)
    {

        $msg = static::getMessage('max_length', $msg);

        if(strlen($this->getValue()) > $length) {
            $this->attachError($msg, ['length' => $length]);
        }

        return $this;
    }

    /**
     * Minimum length validator
     * 
     * @param int $length
     * @param string $msg Custom error message
     * 
     * @return self
     */
    public function minLength($length, $msg = null)
    {
        $msg = static::getMessage('min_length', $msg);

        if(strlen($this->getValue()) < $length) {
            $this->attachError($msg, ['length' => $length]);
        }

        return $this;
    }

    /**
     * Is number validator
     * 
     * @param string $msg Custom error message
     * 
     * @return self
     */
    public function number($msg = null)
    {
        $msg = $msg ?? static::$_messages['number'];

        if(!is_numeric($this->getValue())) {
            $this->attachError($msg, ['length']);
        }

        return $this;
    }

    /**
     * Required validator
     * 
     * @param string $msg Custom error message
     * 
     * @return self
     */
    public function required($msg = null)
    {
        $msg = static::getMessage('required', $msg);

        if(!$this->getValue()) {
            $this->attachError($msg);
        }

        return $this;
    }

    /**
     * Url validator
     * 
     * @param string $msg Custom error message
     * 
     * @return self
     */
    public function url($msg = null)
    {
        $msg = static::getMessage('url', $msg);

        if(!filter_var($this->getValue(), FILTER_VALIDATE_URL)) {
            $this->attachError($msg);
        }

        return $this;
    }

    /**
     * Register validators using stringified method names
     *
     * @param string $rules
     * @return self
     */
    public function validateStr($rules)
    {
        $methods = explode('|', $rules);
        $methods = array_filter($methods);
        
        if(!count($methods)) {
            throw new InvalidArgumentException('No validation rules passed');
        }

        $validator = $this;

        foreach ($methods as $method) {
            $vars = explode(':', $method);
            $method_name = static::methodify($vars[0]);

            if(!is_callable([$this, $method_name])) {
                throw new InvalidArgumentException('Input validator "' . $method_name . '" not assigned');
            }

            $args = array_slice($vars, 1);
            $validator = call_user_func_array([$this, $method_name], $args);
        }

        return $validator;
    }

    /**
     * Methodifier 
     *
     * @param string $str String to methodify
     * @param string $delimeter Delimeter
     * @return string
     */
    private static function methodify($str, $delimeter = '')
    {
        $vars = explode('_', $str);
        $val1 = $vars[0];

        $other_vals = array_slice($vars, 1);
        $recap_vals = array_map(function ($val) {
            return ucfirst($val);
        }, $other_vals);

        array_unshift($recap_vals, $val1);
        return implode($delimeter, $recap_vals);
    }
}