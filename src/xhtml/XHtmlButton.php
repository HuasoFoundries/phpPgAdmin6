<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\XHtml;

/**
 * Class to render button elements.
 */
class XHtmlButton extends XHtmlElement
{
    public function __construct($name, $text = null)
    {
        parent::__construct();

        $this->set_attribute('name', $name);

        if ($text) {
            $this->set_text($text);
        }
    }
}
