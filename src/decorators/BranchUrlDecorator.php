<?php

/**
 * PHPPgAdmin v6.0.0-beta.47
 */

namespace PHPPgAdmin\Decorators;

class BranchUrlDecorator extends Decorator
{
    public function __construct($base, $queryVars = null)
    {
        //\PC::debug($base, 'BranchUrlDecorator');

        $this->b = $base;
        if (null !== $queryVars) {
            $this->q = $queryVars;
        }
    }

    public function value($fields)
    {
        $url = Decorator::get_sanitized_value($this->b, $fields);

        if (false === $url) {
            return '';
        }

        if (!empty($this->q)) {
            $queryVars = Decorator::get_sanitized_value($this->q, $fields);

            $sep = '?';
            ksort($queryVars);
            foreach ($queryVars as $var => $value) {
                $varname  = Decorator::value_url($var, $fields);
                $varvalue = Decorator::value_url($value, $fields);
                $url .= $sep.$varname.'='.$varvalue;
                $sep = '&';
            }
        }
        if (strpos($url, '/src/views') === false) {
            $url = str_replace('//', '/', '/src/views/'.$url);
        }
        if (\SUBFOLDER !== '' && (0 === strpos($url, '/')) && (0 !== strpos($url, \SUBFOLDER))) {
            $url = str_replace('//', '/', \SUBFOLDER.'/'.$url);
        }

        return str_replace('.php', '', $url);
    }
}
