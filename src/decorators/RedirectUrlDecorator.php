<?php

namespace PHPPgAdmin\Decorators;

class RedirectUrlDecorator extends Decorator
{
    public function __construct($base, $queryVars = null)
    {

        //\PC::debug($base, 'RedirectUrlDecorator');

        $this->b = $base;
        if ($queryVars !== null) {
            $this->q = $queryVars;
        }
    }

    public function value($fields)
    {
        $url = Decorator::get_sanitized_value($this->b, $fields);

        if ($url === false) {
            return '';
        }

        //$this->prtrace('url', $url);

        if (!empty($this->q)) {
            $queryVars = Decorator::get_sanitized_value($this->q, $fields);

            $sep = '?';
            ksort($queryVars);
            foreach ($queryVars as $var => $value) {
                $varname  = Decorator::value_url($var, $fields);
                $varvalue = Decorator::value_url($value, $fields);
                if ($varname == 'subject') {
                    $url = '/' . str_replace('.php', '/' . $varvalue, $url);
                } else {
                    $url .= $sep . $varname . '=' . $varvalue;
                }

                $sep = '&';
            }
        }
        if (SUBFOLDER !== '' && (strpos($url, '/') === 0) && (strpos($url, SUBFOLDER) === false)) {
            $url = str_replace('//', '/', SUBFOLDER . '/' . $url);
        }
        return str_replace('.php', '', $url);
    }
}
