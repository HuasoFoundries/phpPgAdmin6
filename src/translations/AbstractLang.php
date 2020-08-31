<?php

/**
 * PHPPgAdmin 6.0.0
 */

namespace PHPPgAdmin\Translations;

/**
 * Class providing translation for Ukrainian language.
 */
abstract class AbstractLang
{
    /**
     * @var array
     */
    protected $lang = [];

    /**
     * @return array
     */
    public function getLang(): array
    {
        return $this->lang;
    }
}
