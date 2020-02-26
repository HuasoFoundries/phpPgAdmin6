<?php

/**
 * PHPPgAdmin v6.0.0-RC8-17-g0a6763af
 */

namespace PHPPgAdmin\Decorators;

class UrlDecorator extends Decorator
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
                $url .= $sep . Decorator::value_url($var, $fields) . '=' . Decorator::value_url($value, $fields);
                $sep = '&';
            }
        }
        //$this->prtrace('url before', $url);
        if (\SUBFOLDER !== '' && (0 === \mb_strpos($url, '/')) && (false === \mb_strpos($url, \SUBFOLDER))) {
            $url = \str_replace('//', '/', \SUBFOLDER . '/' . $url);
        }
        //$this->prtrace('url after', $url);
        return $url;
    }
}
