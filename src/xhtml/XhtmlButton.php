<?php

namespace PHPPgAdmin\XHtml;

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
