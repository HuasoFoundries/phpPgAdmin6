<?php

/**
 * PHPPgAdmin v6.0.0-beta.33
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
            foreach ($queryVars as $var => $value) {
                $varname  = Decorator::value_url($var, $fields);
                $varvalue = Decorator::value_url($value, $fields);
                if ('action' == $varname) {
                    if ('subtree' == $varvalue) {
                        $url = '/tree/'.str_replace('.php', '/subtree', $url);
                    } else {
                        $url = '/tree/'.str_replace('.php', '', $url);
                    }
                }
                $url .= $sep.$varname.'='.$varvalue;
                $sep = '&';
            }
        }
        if (\SUBFOLDER !== '' && (0 === strpos($url, '/')) && (false === strpos($url, \SUBFOLDER))) {
            $url = str_replace('//', '/', \SUBFOLDER.'/'.$url);
        }

        return str_replace('.php', '', $url);
    }
}
