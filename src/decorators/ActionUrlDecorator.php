<?php

/**
 * PHPPgAdmin v6.0.0-RC1.
 */

namespace PHPPgAdmin\Decorators;

class ActionUrlDecorator extends Decorator
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
        //$this->prtrace($fields);
        $url = Decorator::get_sanitized_value($this->b, $fields);

        if (false === $url) {
            return '';
        }

        if (!empty($this->q)) {
            $queryVars = Decorator::get_sanitized_value($this->q, $fields);

            $sep = '?';
            ksort($queryVars);
            foreach ($queryVars as $var => $value) {
                $url .= $sep.Decorator::value_url($var, $fields).'='.Decorator::value_url($value, $fields);
                $sep = '&';
            }
        }

        return \SUBFOLDER.'/src/views/'.str_replace('.php', '', $url);
    }
}
