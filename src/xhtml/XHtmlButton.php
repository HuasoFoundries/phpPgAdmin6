<?php

/**
 * PHPPgAdmin v6.0.0-beta.46
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
