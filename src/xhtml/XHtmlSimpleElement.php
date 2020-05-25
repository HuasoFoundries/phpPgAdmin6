<?php

/**
 * PHPPgAdmin 6.0.1
 */

namespace PHPPgAdmin\XHtml;

use PHPPgAdmin\ContainerUtils;

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
    use \PHPPgAdmin\Traits\HelperTrait;
    /**
     * @var string
     */
    const BASE_PATH = ContainerUtils::BASE_PATH;
    /**
     * @var string
     */
    const SUBFOLDER = ContainerUtils::SUBFOLDER;
    /**
     * @var string
     */
    const DEBUGMODE = ContainerUtils::DEBUGMODE;

    public $_element;

    public $_siblings = [];

    public $_htmlcode;

    public $_attributes = [];

    public $container;

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

    public function set_style(string $style): void
    {
        $this->set_attribute('style', $style);
    }

    public function set_class($class): void
    {
        $this->set_attribute('class', $class);
    }

    public function is_element()
    {
        $lower_classname = \mb_strtolower(\get_class($this));

        return \str_replace('phppgadmin\xhtml\xhtml', '', $lower_classname);
    }

    /**
     * Private function generates xhtml.
     */
    public function _html()
    {
        $this->_htmlcode = '<';

        foreach ($this->_attributes as $attribute => $value) {
            if (!empty($value)) {
                $this->_htmlcode .= \sprintf(' %s="%s" ', $attribute, $value);
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
    public function show(): void
    {
        echo $this->fetch();
    }

    public function set_attribute(string $attr, string $value): void
    {
        $this->_attributes[$attr] = $value;
    }
}
