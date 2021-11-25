<?php

/**
 * PHPPgAdmin6
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

    public function getLang(): array
    {
        return $this->lang;
    }
}
