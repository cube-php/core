<?php

namespace Cube\Tools;

use Stringable;

class Str implements Stringable
{

    protected $string;

    /**
     * Class constructor
     *
     * @param string $string
     */
    public function __construct(string $string)
    {
        $this->string = $string;
    }

    /**
     * Get string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->string;
    }

    /**
     * Return string in capitalized form
     *
     * @return string
     */
    public function capitalize(): string
    {
        $words = array_map('strtolower', $this->words());
        $new_words = array_map('ucfirst', $words);
        return implode(' ', $new_words);
    }

    /**
     * Number of words in string
     *
     * @return integer
     */
    public function count(): int
    {
        return str_word_count($this->string);
    }

    /**
     * Length of characters in string
     *
     * @return integer
     */
    public function length(): int
    {
        return strlen($this->string);
    }

    /**
     * Get first character from string
     *
     * @return string
     */
    public function first(): string
    {
        return substr($this->string, 0, 1);
    }

    /**
     * Get last character from string
     *
     * @return string
     */
    public function last(): string
    {
        return substr($this->string, -1, 1);
    }

    /**
     * String as array
     *
     * @param bool $renove_chars
     * @return array
     */
    public function words(bool $remove_chars = false): array
    {
        $str = $this->string;

        if ($remove_chars) {
            $str = preg_replace('/[^a-z0-9\s]+/i', ' ', $str);
            $str = preg_replace('/\s+/', ' ', $str);
        }

        return explode(' ', trim($str));
    }

    /**
     * String in camel case
     *
     * @return string
     */
    public function camel(): string
    {
        $words = array_map('strtolower', $this->words());
        $new_words = every($words, function ($word) {
            $the_word = str_split($word);
            $first_word = strtoupper($the_word[0]);
            $subsequent_words = array_slice($the_word, 1);
            $new_words = array_merge([$first_word], $subsequent_words);
            return implode('', $new_words);
        });

        return implode($new_words);
    }

    /**
     * String in kebab case
     *
     * @return string
     */
    public function kebab(): string
    {
        $content_arr = array_map('strtolower', $this->words());
        return implode('-', $content_arr);
    }

    /**
     * Slugify content
     *
     * @return string
     */
    public function slug(): string
    {
        $content_arr = array_map('strtolower', $this->words(true));
        return implode('-', $content_arr);
    }

    /**
     * String in lower case
     *
     * @return string
     */
    public function lower(): string
    {
        return strtolower($this->string);
    }

    /**
     * String in uppercase
     *
     * @return string
     */
    public function upper(): string
    {
        return strtoupper($this->string);
    }

    /**
     * Return string in snake case
     *
     * @return string
     */
    public function snake(): string
    {
        $words = array_map('strtolower', $this->words());
        return implode('_', $words);
    }

    /**
     * Check if string includes substring
     *
     * @param string|array $search
     * @return boolean
     */
    public function includes(string|array $search): bool
    {
        $search = is_string($search) ? [$search] : $search;
        foreach ($search as $item) {
            if (false !== stripos($this->string, $item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return value if string is json
     *
     * @return boolean
     */
    public function isJson(): bool
    {
        json_decode($this->string);
        return !json_last_error();
    }

    /**
     * Check if string is one of $values
     *
     * @param array $values
     * @param boolean $case_sensitive
     * @return boolean
     */
    public function isOneOf(array $values, bool $case_sensitive = false): bool
    {
        $choices = $case_sensitive ? $values : array_map('strtolower', $values);
        return in_array(
            strtolower($this->string),
            $choices
        );
    }

    /**
     * Substring of string
     * 
     * @param int $start 
     * @param null|int $length 
     * @return string 
     */
    public function substr(int $start, ?int $length = null): string
    {
        return substr($this->string, $start, $length);
    }

    /**
     * Check if string ends with $end
     *
     * @param string $start
     * @return boolean
     */
    public function endsWith(string $end): bool
    {
        return str_ends_with($this->string, $end);
    }

    /**
     * Check if string starts with $start
     *
     * @param string $start
     * @return boolean
     */
    public function startsWith(string $start): bool
    {
        return str_starts_with($this->string, $start);
    }

    /**
     * Instance of class from string
     *
     * @param string $str
     * @return self
     */
    public static function from(string $str)
    {
        return new self($str);
    }
}
