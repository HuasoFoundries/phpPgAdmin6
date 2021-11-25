<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Decorators;

class BranchUrlDecorator extends Decorator
{
    public $base;

    /**
     * @var mixed
     */
    public $queryVars;

    public function __construct($base, $queryVars = null)
    {
        $this->base = $base;

        if (null !== $queryVars) {
            $this->queryVars = $queryVars;
        }
    }

    /**
     * @param mixed $fields
     *
     * @return string
     */
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

        $url = \str_replace('/src/views/', '/', $url);

        //if ('' !== containerInstance()->subFolder && (0 === \mb_strpos($url, '/')) && (0 !== \mb_strpos($url, \containerInstance()->subFolder))) {
        //    $url = \str_replace('//', '/', \containerInstance()->subFolder . '/' . $url);
        //}

        return \str_replace('.php', '', containerInstance()->subFolder . '/' . $url);
    }
}
