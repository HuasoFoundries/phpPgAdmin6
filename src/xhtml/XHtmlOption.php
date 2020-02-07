<?php

/**
 * PHPPgAdmin v6.0.0-RC7
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
