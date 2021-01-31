<?php

namespace Cube\Misc;

use InvalidArgumentException;
use Cube\Misc\InputValidator;
use Cube\Interfaces\InputInterface;

class Input implements InputInterface
{

    /**
     * Key holder
     * 
     * @var mixed
     */
    private $_key = '';

    /**
     * Value holder
     * 
     * @var mixed
     */
    private $_value = '';

    /**
     * Input constructor
     * 
     * @param string $value
     * @param string $key
     */
    public function __construct($value, $key = '')
    {
        $this->_key = $key;
        $this->_value = $value;
    }

    /**
     * Return $this->_value when treated as string
     * 
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getValue();
    }

    /**
     * Check if input contains key
     *
     * @param string $key
     * @return bool
     */
    public function contains(string $key): bool
    {
        return $this->matches("/{$key}/i");
    }

    /**
     * Check if input's value matches specified value
     *
     * @param mixed $value
     * @return boolean
     */
    public function equals($value)
    {
        $val = $value instanceof self ? $value->getValue() : $value;
        return $this->getValue() == $val;
    }

    /**
     * Check if input's value matches specified value disregarding case
     *
     * @param string|int $value
     * @return void
     */
    public function equalsIgnoreCase($value)
    {
        $input_value = strtolower($this->getValue());
        $value = strtolower($value);

        return $input_value == $value;
    }

    /**
     * Input's value
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->_value ?? '';
    }

    /**
     * Check if $value is email
     * 
     * @return bool
     */
    public function isEmail()
    {
        return filter_var($this->_value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Check if $value is int
     * 
     * @return bool
     */
    public function isInt()
    {
        return (is_numeric($this->_value) || is_int($this->_value));
    }

    /**
     * Check if $value is regular expression
     * 
     * @return bool
     */
    public function isRegex()
    {
        return filter_var($this->_value, FILTER_VALIDATE_REGEXP);
    }

    /**
     * Check if $value is empty
     * 
     * @return bool
     */
    public function isEmpty()
    {
        return empty(trim($this->_value));
    }

    /**
     * Check if $value is URL
     * 
     * @return bool
     */
    public function isUrl()
    {
        return filter_var($this->_value, FILTER_VALIDATE_URL);
    }

    /**
     * Check if $value matches regex
     * 
     * @param string $regex Regular expression
     * 
     * @return bool
     */
    public function matches($regex)
    {
        return !!preg_match($regex, $this->_value);
    }

    /**
     * Create input's validator instance
     * 
     * @return InputValidator
     */
    public function validate($rules = null)
    {

        if(!$this->_key) {
            throw new InvalidArgumentException
                ('You are required to specify a key for input to use validator');
        }

        $validator = new InputValidator($this->_key, $this->_value);
        return $rules ? $validator->validateStr($rules) : $validator;
    }

    /**
     * Return input's value as boolean
     *
     * @return bool
     */
    public function toBoolean(): bool
    {
        return !!$this->getValue();
    }

    /**
     * Return input type casted to float
     *
     * @return float
     */
    public function toFloat(): float
    {
        return (float) $this->getValue();
    }

    /**
     * Return input's value as an integer
     *
     * @return int
     */
    public function toInt(): int
    {
        return (int) $this->getValue();
    }

    /**
     * Return input's value
     * 
     * @deprecated 0.12
     * 
     * @return string
     */
    public function value()
    {
        return $this->getValue();
    }
}