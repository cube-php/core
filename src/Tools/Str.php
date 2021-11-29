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
     * String as array
     *
     * @param bool $renove_chars
     * @return array
     */
    public function words(bool $remove_chars = false): array
    {
        $str = $this->string;

        if($remove_chars) {
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
     * @param string $search
     * @return boolean
     */
    public function includes(string $search): bool
    {
        return false !== stripos($this->string, $search);
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