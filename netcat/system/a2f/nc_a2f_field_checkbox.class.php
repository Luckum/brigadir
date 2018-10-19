<?php

/**
 * Класс для реализации поля типа "Логическая переменная"
 */
class nc_a2f_field_checkbox extends nc_a2f_field {

    public function render_value_field($html = true) {

        if ($this->can_inherit_values) { // "наследовать (#INHERIT#) / нет / да ("on")
            $inherit = ($this->value == '#INHERIT#' || !$this->is_set);
            $is_on = (!$inherit && strlen($this->value));
            $is_off = (!$inherit && !strlen($this->value));

            $ret = "<select name='{$this->get_field_name()}'>" .
                   "<option value='#INHERIT#'" . ($inherit ? ' selected' : '') . ">" .
                        CONTROL_CUSTOM_SETTINGS_INHERIT .
                   "<option value=''" . ($is_off ? ' selected' : '') . ">" .
                        CONTROL_CUSTOM_SETTINGS_OFF .
                   "<option value='on'" . ($is_on ? ' selected' : '') . ">" .
                        CONTROL_CUSTOM_SETTINGS_ON .
                   "</select>";
        }
        else {
            $ret = "<input name='" . $this->get_field_name() . "' type='checkbox' " .
                   ($this->value == true ? " checked='checked'" : "") .
                   "  class='ncf_value_checkbox'>";
}

        if ($html) {
            $ret = "<div class='ncf_value'>" . $ret . "</div>\n";
        }

        return $ret;
    }


    /**
     *
     */
    protected function get_displayed_default_value() {
        return $this->default_value ? CONTROL_CUSTOM_SETTINGS_ON : CONTROL_CUSTOM_SETTINGS_OFF;
    }

}