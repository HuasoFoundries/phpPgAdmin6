<?php

// declare(strict_types=1);

/**
 * PHPPgAdmin vv6.0.0-RC8-16-g13de173f
 *
 */

namespace PHPPgAdmin\XHtml;

\defined('BASE_PATH') || \define('BASE_PATH', \dirname(__DIR__, 2));
\defined('SUBFOLDER') || \define(
    'SUBFOLDER',
    \str_replace($_SERVER['DOCUMENT_ROOT'] ?? '', '', BASE_PATH)
);
\defined('DEBUGMODE') || \define('DEBUGMODE', false);

/**
 * XHtmlSimpleElement.
 *
 * Used to generate Xhtml-Code for simple xhtml elements
 * (i.e. elements, that can't contain child elements)
 *
 *
 * @author    Felix Meinhold
 */
class XHtmlSimpleElement
{
    use \PHPPgAdmin\Traits\HelperTrait;
    /**
     * @var string
     */
    const BASE_PATH = BASE_PATH;
    /**
     * @var string
     */
    const SUBFOLDER = SUBFOLDER;
    /**
     * @var string
     */
    const DEBUGMODE = DEBUGMODE;

    public $_element;

    public $_siblings = [];

    public $_htmlcode;

    public $_attributes = [];

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
        //$this->prtrace('is_element_string', $is_element_string, 'lower_classname', $lower_classname, '__CLASS__');
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
