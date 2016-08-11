<?php

namespace PHPPgAdmin\XHtml;

class XHTML_Option extends XHtmlElement {
	function XHTML_Option($text, $value = null) {
		XHtmlElement::XHtmlElement(null);
		$this->set_text($text);
	}
}
