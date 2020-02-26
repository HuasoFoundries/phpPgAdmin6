<?php

// declare(strict_types=1);

/**
 * PHPPgAdmin vv6.0.0-RC8-16-g13de173f
 *
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
