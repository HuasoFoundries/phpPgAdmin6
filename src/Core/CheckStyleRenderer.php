<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Core;

use PHPMD\Renderer\XMLRenderer;
use PHPMD\Report;

/**
 * This class will render a Java-checkstyle compatible xml-report.
 * for use with cs2pr and others.
 */
class CheckStyleRenderer extends XMLRenderer
{
    /**
     * Temporary property that holds the name of the last rendered file, it is
     * used to detect the next processed file.
     *
     * @var string
     */
    private $fileName;

    /**
     * This method will be called when the engine has finished the source analysis
     * phase.
     */
    public function renderReport(Report $report)
    {
        $writer = $this->getWriter();
        $writer->write('<checkstyle>');
        $writer->write(\PHP_EOL);

        foreach ($report->getRuleViolations() as $violation) {
            $fileName = $violation->getFileName();

            if ($this->fileName !== $fileName) {
                // Not first file
                if (null !== $this->fileName) {
                    $writer->write('  </file>' . \PHP_EOL);
                }
                // Store current file name
                $this->fileName = $fileName;

                $writer->write('  <file name="' . $fileName . '">' . \PHP_EOL);
            }

            $rule = $violation->getRule();

            $writer->write('    <error');
            $writer->write(' line="' . $violation->getBeginLine() . '"');
            $writer->write(' endline="' . $violation->getEndLine() . '"');
            $writer->write(\sprintf(' severity="%s"', $this->mapPriorityToSeverity($rule->getPriority())));
            $writer->write(\sprintf(
                ' message="%s (%s, %s) "',
                \htmlspecialchars($violation->getDescription()),
                $rule->getName(),
                $rule->getRuleSetName()
            ));

            $this->maybeAdd('package', $violation->getNamespaceName());
            $this->maybeAdd('externalInfoUrl', $rule->getExternalInfoUrl());
            $this->maybeAdd('function', $violation->getFunctionName());
            $this->maybeAdd('class', $violation->getClassName());
            $this->maybeAdd('method', $violation->getMethodName());
            //$this->_maybeAdd('variable', $violation->getVariableName());

            $writer->write(' />' . \PHP_EOL);
        }

        // Last file and at least one violation
        if (null !== $this->fileName) {
            $writer->write('  </file>' . \PHP_EOL);
        }

        foreach ($report->getErrors() as $error) {
            $writer->write('  <file name="' . $error->getFile() . '">');
            $writer->write($error->getFile());
            $writer->write('<error  msg="');
            $writer->write(\htmlspecialchars($error->getMessage()));
            $writer->write(' severity="error" />' . \PHP_EOL);
        }

        $writer->write('</checkstyle>' . \PHP_EOL);
    }

    /**
     * Get a violation severity level according to the priority
     * of the rule that's being broken.
     *
     * @see https://checkstyle.sourceforge.io/version/4.4/property_types.html#severity
     * - priority 1 maps to error level severity
     * - priority 2 maps to warning level severity
     * - priority > 2 maps to info level severity
     *
     * @param int $priority priority of the broken rule
     *
     * @return string either error, warning or info
     */
    protected function mapPriorityToSeverity($priority)
    {
        if (2 < $priority) {
            return 'info';
        }

        return 2 === $priority ? 'warning' : 'error';
    }

    /**
     * This method will write a xml attribute named <b>$attr</b> to the output
     * when the given <b>$value</b> is not an empty string and is not <b>null</b>.
     *
     * @param string $attr  the xml attribute name
     * @param string $value the attribute value
     */
    protected function maybeAdd($attr, $value)
    {
        if (null === $value || \trim($value) === '') {
            return;
        }
        $this->getWriter()->write(' ' . $attr . '="' . $value . '"');
    }
}
