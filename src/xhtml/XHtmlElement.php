<?php

/**
 * PHPPgAdmin v6.0.0-beta.50
 */

namespace PHPPgAdmin\XHtml;

/**
 *  XHtmlElement.
 *
 *  Used to generate Xhtml-Code for xhtml elements
 *  that can contain child elements
 */
class XHtmlElement extends XHtmlSimpleElement
{
    public $_text;
    public $_htmlcode = '';
    public $_siblings = [];

    public function __construct($text = null)
    {
        parent::__construct();

        if ($text) {
            $this->set_text($text);
        }
    }

    /*
     * Adds an xhtml child to element
     *
     * @param XHtmlElement $object    The element to become a child of element
     */
    public function add(&$object)
    {
        array_push($this->_siblings, $object);
    }

    /*
     * The CDATA section of Element
     *
     * @param    string   $text Text content of the element
     */
    public function set_text($text)
    {
        if ($text) {
            $this->_text = htmlspecialchars($text);
        }
    }

    public function fetch()
    {
        return $this->_html();
    }

    public function _html()
    {
        $this->_htmlcode = "<{$this->_element}";
        foreach ($this->_attributes as $attribute => $value) {
            if (!empty($value)) {
                $this->_htmlcode .= sprintf(' %s="%s" ', $attribute, $value);
            }
        }
        $this->_htmlcode .= '>';

        if ($this->_text) {
            $this->_htmlcode .= $this->_text;
        }

        foreach ($this->_siblings as $obj) {
            $this->_htmlcode .= $obj->fetch();
        }

        $this->_htmlcode .= "</{$this->_element}>";

        return $this->_htmlcode;
    }

    // Returns siblings of Element
    public function get_siblings()
    {
        return $this->_siblings;
    }

    public function has_siblings()
    {
        return 0 != count($this->_siblings);
    }
}
