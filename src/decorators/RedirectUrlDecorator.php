<?php

/**
 * PHPPgAdmin v6.0.0-RC9-3-gd93ec300
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

        //$this->prtrace('url', $url);

        if (!empty($this->queryVars)) {
            $queryVars = Decorator::get_sanitized_value($this->queryVars, $fields);

            $sep = '?';
            \ksort($queryVars);

            foreach ($queryVars as $var => $value) {
                $varname  = Decorator::value_url($var, $fields);
                $varvalue = Decorator::value_url($value, $fields);

                if ('subject' === $varname) {
                    $url = '/' . \str_replace('redirect?', 'redirect/' . $varvalue . '?', $url);
                } else {
                    $url .= $sep . $varname . '=' . $varvalue;
                }

                $sep = '&';
            }
        }

        if (self::SUBFOLDER !== '' && (0 === \mb_strpos($url, '/')) && (false === \mb_strpos($url, self::SUBFOLDER))) {
            $url = \str_replace('//', '/', self::SUBFOLDER . '/' . $url);
        }

        return \str_replace('.php', '', $url);
    }
}
