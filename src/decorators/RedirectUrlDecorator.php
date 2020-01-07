<?php

/**
 * PHPPgAdmin v6.0.0-RC1.
 */

namespace PHPPgAdmin\Decorators;

class RedirectUrlDecorator extends Decorator
{
    public function __construct($base, $queryVars = null)
    {
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

        //$this->prtrace('url', $url);

        if (!empty($this->q)) {
            $queryVars = Decorator::get_sanitized_value($this->q, $fields);

            $sep = '?';
            ksort($queryVars);
            foreach ($queryVars as $var => $value) {
                $varname = Decorator::value_url($var, $fields);
                $varvalue = Decorator::value_url($value, $fields);
                if ('subject' == $varname) {
                    $url = '/'.str_replace('redirect?', 'redirect/'.$varvalue.'?', $url);
                } else {
                    $url .= $sep.$varname.'='.$varvalue;
                }

                $sep = '&';
            }
        }
        if (\SUBFOLDER !== '' && (0 === strpos($url, '/')) && (false === strpos($url, \SUBFOLDER))) {
            $url = str_replace('//', '/', \SUBFOLDER.'/'.$url);
        }

        return str_replace('.php', '', $url);
    }
}
