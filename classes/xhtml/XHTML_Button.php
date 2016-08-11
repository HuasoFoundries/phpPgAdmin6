<?php

namespace PHPPgAdmin\XHtml;

class XHTML_Button extends XHtmlElement {
	function XHTML_Button($name, $text = null) {
		parent::XHtmlElement();

		$this->set_attribute("name", $name);

		if ($text) {
			$this->set_text($text);
		}

	}
}
