<?php

/**
 * PHPPgAdmin v6.0.0-beta.48
 */

namespace PHPPgAdmin\Traits;

/**
 * Common trait to print form parts that appear on different controller dialogs.
 */
trait FormTrait
{
    public $misc;

    /**
     * Prints inputs for action, table and submit/cancel buttons.
     *
     * @param string $action value for action input
     * @param string $table  value for table input
     * @param string $add    text for add button
     * @param string $cancel text for cancel button
     */
    public function getActionTableAndButtons($action, $table, $add, $cancel)
    {
        $content = $this->misc->form;
        $content .= sprintf('<input type="hidden" name="action" value="%s" />%s', $action, "\n");
        $content .= sprintf('<input type="hidden" name="table" value="%s" />%s', $table, "\n");
        $content .= sprintf('<input type="submit" value="%s" />%s', $add, "\n");
        $content .= sprintf('<input type="submit" name="cancel" value="%s" />', $cancel);

        return $content;
    }
}
