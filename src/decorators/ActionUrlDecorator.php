<?php

/**
 * PHPPgAdmin v6.0.0-RC5
 */

namespace PHPPgAdmin\Decorators;

class ActionUrlDecorator extends Decorator
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
        //$this->prtrace($fields);
        $url = Decorator::get_sanitized_value($this->base, $fields);

        if (false === $url) {
            return '';
        }

        if (!empty($this->queryVars)) {
            $queryVars = Decorator::get_sanitized_value($this->queryVars, $fields);

            $sep = '?';
            ksort($queryVars);
            foreach ($queryVars as $var => $value) {
                if (!is_scalar($value)) {
                    continue;
                }
                dump($fields, $var, $value);
                $url .= $sep.Decorator::value_url($var, $fields).'='.Decorator::value_url($value, $fields);
                $sep = '&';
            }
        }

        return \SUBFOLDER.'/src/views/'.str_replace('.php', '', $url);
    }
}
