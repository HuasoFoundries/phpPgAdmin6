<?php

/**
 * PHPPgAdmin v6.0.0-beta.49
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
        $content .= sprintf('<input type="hidden" name="action" value="%s" />%s', $action, PHP_EOL);
        $content .= sprintf('<input type="hidden" name="table" value="%s" />%s', $table, PHP_EOL);
        $content .= sprintf('<input type="submit" value="%s" />%s', $add, PHP_EOL);
        $content .= sprintf('<input type="submit" name="cancel" value="%s" />', $cancel);

        return $content;
    }

    /**
     * Prints inputs for action, table and submit/cancel buttons.
     *
     * @param array $inputs   array of inputs with their name, type and value
     * @param array $buttons  array of buttons with their name, type and value
     * @param array $cheboxes array of cheboxes with their name, id, checked state, type and text for label
     */
    public function getFormInputsAndButtons($inputs, $buttons, $cheboxes = [])
    {
        $content = $this->misc->form;

        foreach ($cheboxes as $checkbox) {
            $content .= sprintf('<p>%s', PHP_EOL);
            $content .= sprintf('<input type="%s" name="%s" id="%s" %s />', $checkbox['type'], $checkbox['name'], $checkbox['id'], $checkbox['checked'] ? 'checked="checked"' : '');
            $content .= sprintf('<label for="%s">%s</label>', $checkbox['id'], $checkbox['labeltext']);
            $content .= sprintf('</p>%s', PHP_EOL);
        }

        foreach ($inputs as $input) {
            $content .= sprintf('<input type="%s" name="%s" value="%s" />%s', $input['type'], $input['name'], $input['value'], PHP_EOL);
        }

        $content .= sprintf('<p>%s', PHP_EOL);
        foreach ($buttons as $button) {
            $content .= sprintf('<input type="%s" name="%s" value="%s" />%s', $button['type'], $button['name'], $button['value'], PHP_EOL);
        }

        $content .= sprintf('</p>%s', PHP_EOL);

        return $content;
    }
}
