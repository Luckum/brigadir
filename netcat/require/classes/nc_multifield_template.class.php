<?php

class nc_multifield_template {

    /** @var nc_multifield */
    private $multifield = null;
    private $template = array();
    private $max_priority = 0;

    /**
     * @param nc_multifield $multifield
     */
    public function __construct(nc_multifield $multifield) {
        $this->multifield = $multifield;
    }

    /**
     * @param $template
     * @return $this
     */
    public function set($template) {
        $this->template = $template;
        return $this;
    }

    /**
     * @return string
     */
    public function get_html() {
        return !empty($this->template) && isset($this->multifield->records[0])
            ? $this->template['prefix'] . $this->create_record_template() . $this->template['suffix']
            : '';
    }

    /**
     * @return string
     */
    private function create_record_template() {
        $records = array();
        $i = intval($this->template['i']);
        foreach ($this->multifield->records as $record) {
            $records[] = str_replace('%i%', $i, $this->apply_record_tpl($record));
            $i++;
        }
        return join($this->template['divider'], $records);
    }

    /**
     * @param $record
     * @return mixed
     */
    private function apply_record_tpl($record) {
        $record_tpl = $this->template['record'];
        foreach ($record as $key => $value) {
            $record_tpl = str_replace("%$key%", $value, $record_tpl);
        }
        return $record_tpl;
    }

    /**
     * @return string
     */
    public function get_form() {
        $html = $this->multifield->desc ? "<div>{$this->multifield->desc}:</div>" : "<div>{$this->multifield->name}:</div>";
        $html .= "<div class='nc-upload nc-upload-multifile'" .
                 " data-field-id='{$this->multifield->id}'" .
                 " data-field-name='{$this->multifield->name}'" .
                 " data-max-files='{$this->multifield->settings->max}'";

        if ($this->multifield->settings->use_name) {
            $html .= " data-custom-name='1'" .
                     " data-custom-name-caption='" . htmlspecialchars(strip_tags($this->multifield->settings->custom_name), ENT_QUOTES) . "'";
        }

        $html .= ">";
        $html .= "<div class='nc-upload-files'>";
        $html .= $this->get_edit_form();
        $html .= "</div>";
        $html .= "<div class='nc-upload-settings'>";
        $html .= '<span>';
        $html .= $this->get_setting_html('use_name');
        $html .= $this->get_setting_html('path');
        $html .= $this->get_setting_html('use_preview');
        $html .= $this->get_img_settings_html('preview');
        $html .= $this->get_img_settings_html('resize');
        $html .= $this->get_crop_settings_html();
        $html .= $this->get_crop_settings_html('preview');
        $html .= $this->get_setting_html('min');
        $html .= $this->get_setting_html('max');
        $html .= "<input type='hidden' name='settings_{$this->multifield->name}_hash' value='" . $this->multifield->settings->get_setting_hash() . "'/>";
        $html .= "<input class='nc-upload-input' type='file' name='f_{$this->multifield->name}_file[][]' multiple /></div>";
        $html .= "<script>\$nc(document).trigger('apply-upload');</script>";
        $html .= "</div>";

        return $html;
    }

    /**
     * @return null|string
     */
    private function get_edit_form() {
        $result = null;
        $this->max_priority = 0;
        if (isset($this->multifield->records[0]->Field_ID)) {
            $nc_core = nc_core::get_object();
            $doc_root = $nc_core->DOCUMENT_ROOT . $nc_core->SUB_FOLDER;

            $has_custom_name = $this->multifield->settings->use_name;
            $custom_name_caption = htmlspecialchars(strip_tags($this->multifield->settings->custom_name), ENT_QUOTES);

            foreach ($this->multifield->records as $record) {
                $file_name = $this->get_file_name($record->Path);
                if ($this->max_priority < $record->Priority) {
                    $this->max_priority = $record->Priority;
                }

                $file_size_string = nc_bytes2size($record->Size);
                $file_type = nc_file_mime_type($doc_root . $record->Path, $record->Path);

                $result .= "<div class='nc-upload-file' data-type='$file_type'>" .
                    "<div class='nc-upload-file-info'>" .
                    "<a class='nc-upload-file-name' href='{$record->Path}' target='_blank' tabindex='-1'" .
                    " title='" . htmlspecialchars("$file_name ($file_size_string)", ENT_QUOTES) . "'>" .
                    htmlspecialchars($file_name) .
                    "</a> <span class='nc-upload-file-size'>$file_size_string</span> " .
                    "<a href='#' class='nc-upload-file-remove' title='" . NETCAT_MODERATION_FILES_DELETE . "' tabindex='-1'>×</a></div>" .
                    ($has_custom_name
                        ? "<div class='nc-upload-file-custom-name'>" .
                            "<input type='text' name='name_multifile[$record->ID]' value='" .
                            htmlspecialchars($record->Name, ENT_QUOTES) .
                            "' placeholder='$custom_name_caption'></div>"
                        : ""
                    ) .
                    "<input class='nc-upload-file-priority' type='hidden' name='priority_multifile[{$record->Field_ID}][]' value='$record->ID' />" .
                    "<input class='nc-upload-file-remove-checkbox' type='checkbox' name='del_multifile[]' value='{$record->ID}' /></div>";
            }
        }
        return $result;
    }

    /**
     * @param $type
     * @return string
     */
    private function get_setting_html($type) {
        return "<input type='hidden' name='settings_{$this->multifield->name}[$type]' value='{$this->multifield->settings->{$type}}' />";
    }

    /**
     * @param $path
     * @return mixed
     */
    private function get_file_name($path) {
        $file_name = explode('/', $path);
        return $file_name[count($file_name) - 1];
    }

    /**
     * @param $type
     * @return string
     */
    private function get_img_settings_html($type) {
        return "<input type='hidden' name='settings_{$this->multifield->name}[$type][width]' value='{$this->multifield->settings->{$type . '_width'}}' />
                <input type='hidden' name='settings_{$this->multifield->name}[$type][height]' value='{$this->multifield->settings->{$type . '_height'}}' />
                <input type='hidden' name='settings_{$this->multifield->name}[$type][mode]' value='{$this->multifield->settings->{$type . '_mode'}}' />";
    }

    /**
     * @param string $type
     * @return string
     */
    private function get_crop_settings_html($type = '') {
        if ($type == 'preview') {
            $typeb = "[preview]";
            $type = $type . '_';
        }
        return "<input type='hidden' name='settings_{$this->multifield->name}{$typeb}[crop][x0]' value='{$this->multifield->settings->{$type.'crop_x0'}}' />
<input type='hidden' name='settings_{$this->multifield->name}{$typeb}[crop][y0]' value='{$this->multifield->settings->{$type.'crop_y0'}}' />
<input type='hidden' name='settings_{$this->multifield->name}{$typeb}[crop][x1]' value='{$this->multifield->settings->{$type.'crop_x1'}}' />
<input type='hidden' name='settings_{$this->multifield->name}{$typeb}[crop][y1]' value='{$this->multifield->settings->{$type.'crop_y1'}}' />
<input type='hidden' name='settings_{$this->multifield->name}{$typeb}[crop][mode]' value='{$this->multifield->settings->{$type.'crop_mode'}}' />
<input type='hidden' name='settings_{$this->multifield->name}{$typeb}[crop][width]' value='{$this->multifield->settings->{$type.'crop_width'}}' />
<input type='hidden' name='settings_{$this->multifield->name}{$typeb}[crop][height]' value='{$this->multifield->settings->{$type.'crop_height'}}' />
<input type='hidden' name='settings_{$this->multifield->name}{$typeb}[crop_ignore][width]' value='{$this->multifield->settings->{$type.'crop_ignore_width'}}' />
<input type='hidden' name='settings_{$this->multifield->name}{$typeb}[crop_ignore][height]' value='{$this->multifield->settings->{$type.'crop_ignore_height'}}' />";
    }

    /**
     * @param $name
     * @return bool
     */
    public function __get($name) {
        return isset($this->$name) ? $this->$name : false;
    }

}