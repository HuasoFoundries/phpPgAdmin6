<?php

/*
 * PHPPgAdmin v6.0.0-beta.30
 */

namespace PHPPgAdmin\XHtml;

class XHtmlOption extends XHtmlElement
{
    public function __construct($text, $value = null)
    {
        parent::__construct(null);

        $this->set_text($text);
    }
}
