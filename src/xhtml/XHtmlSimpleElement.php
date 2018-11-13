<?php

/**
 * PHPPgAdmin v6.0.0-beta.49
 */

namespace PHPPgAdmin\XHtml;

/**
 * XHtmlSimpleElement.
 *
 * Used to generate Xhtml-Code for simple xhtml elements
 * (i.e. elements, that can't contain child elements)
 *
 * @author    Felix Meinhold
 */
class XHtmlSimpleElement
{
    public $_element;
    public $_siblings = [];
    public $_htmlcode;
    public $_attributes = [];

    use \PHPPgAdmin\Traits\HelperTrait;

    /**
     * Constructor.
     *
     * @param null|mixed $element The element's name. Defaults to name of the
     *                            derived class
     */
    public function __construct($element = null)
    {
        $this->_element = $this->is_element();
    }

    public function set_style($style)
    {
        $this->set_attribute('style', $style);
    }

    public function set_class($class)
    {
        $this->set_attribute('class', $class);
    }

    public function is_element()
    {
        $lower_classname   = strtolower(get_class($this));
        $is_element_string = str_replace('phppgadmin\xhtml\xhtml', '', $lower_classname);
        //$this->prtrace('is_element_string', $is_element_string, 'lower_classname', $lower_classname, '__CLASS__');
        return $is_element_string;
    }

    /**
     * Private function generates xhtml.
     */
    public function _html()
    {
        $this->_htmlcode = '<';
        foreach ($this->_attributes as $attribute => $value) {
            if (!empty($value)) {
                $this->_htmlcode .= sprintf(' %s="%s" ', $attribute, $value);
            }
        }
        $this->_htmlcode .= '/>';

        return $this->_htmlcode;
    }

    /**
     * Returns xhtml code.
     */
    public function fetch()
    {
        return $this->_html();
    }

    /**
     * Echoes xhtml.
     */
    public function show()
    {
        echo $this->fetch();
    }

    public function set_attribute($attr, $value)
    {
        $this->_attributes[$attr] = $value;
    }
}
