<?php

namespace PHPPgAdmin\Decorators;

class BranchUrlDecorator extends Decorator
{
    public function __construct($base, $queryVars = null)
    {

        //\PC::debug($base, 'BranchUrlDecorator');

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

        if (!empty($this->q)) {
            $queryVars = Decorator::get_sanitized_value($this->q, $fields);

            $sep = '?';
            foreach ($queryVars as $var => $value) {
                $varname  = Decorator::value_url($var, $fields);
                $varvalue = Decorator::value_url($value, $fields);
                if ($varname == 'action') {
                    if ($varvalue == 'subtree') {
                        $url = '/tree/' . str_replace('.php', '/subtree', $url);
                    } else {
                        $url = '/tree/' . str_replace('.php', '', $url);
                    }
                }
                $url .= $sep . $varname . '=' . $varvalue;
                $sep = '&';
            }
        }
        if (SUBFOLDER !== '' && (strpos($url, '/') === 0) && (strpos($url, SUBFOLDER) === false)) {
            $url = str_replace('//', '/', SUBFOLDER . '/' . $url);
        }
        return str_replace('.php', '', $url);
    }
}
