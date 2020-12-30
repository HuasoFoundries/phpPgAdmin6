<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin\Decorators;

class RedirectUrlDecorator extends Decorator
{
    public function __construct($base, $queryVars = null)
    {
        $this->base = $base;

        if (null !== $queryVars) {
            $this->queryVars = $queryVars;
        }
    }

    public function value($fields)
    {
        $url = Decorator::get_sanitized_value($this->base, $fields);

        if (false === $url) {
            return '';
        }

        if (!empty($this->queryVars)) {
            $queryVars = Decorator::get_sanitized_value($this->queryVars, $fields);

            $sep = '?';
            \ksort($queryVars);

            foreach ($queryVars as $var => $value) {
                $varname = Decorator::value_url($var, $fields);
                $varvalue = Decorator::value_url($value, $fields);

                if ('subject' === $varname) {
                    $url = \str_replace('redirect?', 'redirect/' . $varvalue . '?', $url);
                } else {
                    $url .= $sep . $varname . '=' . $varvalue;
                }

                $sep = '&';
            }
        }

        if ('' !== containerInstance()->subFolder && (0 === \mb_strpos($url, '/')) && (false === \mb_strpos($url, \containerInstance()->subFolder))) {
            //    $url = \str_replace('//', '/', \containerInstance()->subFolder . '/' . $url);
        }

        return \str_replace('.php', '', $url);
    }
}
