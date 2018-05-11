<?php

/**
 * PHPPgAdmin v6.0.0-beta.45
 */

namespace PHPPgAdmin\XHtml;

/**
 * Class to render options elements.
 */
class XHtmlOption extends XHtmlElement
{
    public function __construct($text, $value = null)
    {
        parent::__construct(null);

        $this->set_text($text);
    }
}
