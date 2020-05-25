<?php

/**
 * PHPPgAdmin 6.0.0
 */

namespace PHPPgAdmin\Decorators;

class BranchUrlDecorator extends Decorator
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
                $url .= $sep . $varname . '=' . $varvalue;
                $sep = '&';
            }
        }

        if (false === \mb_strpos($url, '/src/views')) {
            $url = \str_replace('//', '/', '/src/views/' . $url);
        }

        if (self::SUBFOLDER !== '' && (0 === \mb_strpos($url, '/')) && (0 !== \mb_strpos($url, self::SUBFOLDER))) {
            $url = \str_replace('//', '/', self::SUBFOLDER . '/' . $url);
        }

        return \str_replace('.php', '', $url);
    }
}
