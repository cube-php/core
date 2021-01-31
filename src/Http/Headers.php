<?php

namespace Cube\Http;

use Cube\Misc\Collection;

class Headers extends Collection
{

    /**
     * Headers constructor
     * 
     */
    public function __construct()
    {

        if(!is_callable('headers_list')) {
            return;
        }

        $headers_list = headers_list();

        foreach($headers_list as $value) {
            $this->set(
                $this->getHeaderName($value),
                $this->getHeaderValue($value));
        }

        $this->removeAll();
    }

    /**
     * Input a raw header
     * 
     * @param string $content Header conetn
     * 
     * @return void
     */
    public function raw($content)
    {
        header($content);
    }

    /**
     * Render all headers
     * 
     * @return void
     */
    public function render()
    {
        $headers = $this->all();

        foreach($headers as $header => $value)
        {
            $header = $this->refixHeader($header);
            $header_content = "{$header}: {$value}";
            header($header_content);
        }
    }

    /**
     * Remove all previously set headers
     * 
     * @return void
     */
    private function removeAll()
    {
        foreach(headers_list() as $header_name)
        {    
            $header = $this->getHeaderName($header_name);
        }
    }

    /**
     * Get header name from header string
     * 
     * @return string
     */
    private function getHeaderName($header)
    {
        $header_vars = explode(':', $header);
        return $header_vars[0];
    }

    /**
     * Get header value from header string
     * 
     * @return string
     */
    private function getHeaderValue($header)
    {
        $header_vars = explode(':', $header);
        return trim($header_vars[1] ?? '');
    }

    /**
     * Refix header name
     * 
     * @return string
     */
    private function refixHeader($name)
    {
        $new_name = array();
        $name_vars = explode('-', $name);

        foreach($name_vars as $name_var) {
            $first_letter = strtoupper(substr($name_var, 0, 1));
            $rest_letters = substr($name_var, 1);

            $new_name[] = $first_letter . $rest_letters;
        }

        return implode('-', $new_name);
    }
}