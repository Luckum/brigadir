<?php

/**
 * Класс для реализации поля типа "Файл"
 */
class nc_a2f_field_file extends nc_a2f_field {

    protected $upload, $filename, $filesize, $filepath, $filetype;

    public function get_subtypes() {
        return array('any', 'image');
    }

    public function render_value_field($html = true) {
        $ret = '<div class="nc-upload"><div class="nc-upload-files">';

        // старый файл
        if ($this->value) {
            $file_size_string = nc_bytes2size($this->value['size']);

            $ret .= "<input type='hidden' name='" . $this->get_field_name('old') . "' value='" . $this->value['all'] . "' />" .
                    "<div class='nc-upload-file' data-type='" . htmlspecialchars($this->value['type'], ENT_QUOTES) . "'>" .
                    "<div class='nc-upload-file-info'>" .
                    "<a class='nc-upload-file-name' href='" . htmlspecialchars($this->value['path'], ENT_QUOTES) . "' target='_blank' title='" .
                    htmlspecialchars("{$this->value['name']} ($file_size_string)", ENT_QUOTES) . "'>" .
                    htmlspecialchars($this->value['name'], ENT_QUOTES) . "</span> " .
                    "<span class='nc-upload-file-size'>$file_size_string</span> " .
                    "<a href='#' class='nc-upload-file-remove'>×</a></div>" .
                    "<input id='kill" . $this->name . "' class='nc-upload-file-remove-hidden' type='hidden' name='" .
                    $this->get_field_name('kill') . "' value='0' />" .
                    "</div>";
        }

        $ret .= "</div><input class='nc-upload-input' name='" . $this->get_field_name() . "' type='file' />";
        $ret .= "<script>\$nc(document).trigger(\"apply-upload\");</script>";
        $ret .= "</div>";

        if ($html) {
            $ret = "<div class='ncf_value'>" . $ret . "</div>\n";
        }

        return $ret;
    }

    protected function get_displayed_default_value() {
        if (!$this->default_value) {
            return '';
        }

        $file_info = null;

        if (is_string($this->default_value)) {
            $file_info = $this->file_string_to_array($this->default_value);
        }

        if (is_array($this->default_value)) {
            $file_info = $this->default_value;
        }

        if ($file_info) {
            return "<a href='" . $file_info['path'] .
                   "' target='_blank'>" .
                   $file_info['name'] .
                   "</a>";
        }


        return $this->default_value;
    }

    public function set_value($value) {
        $this->value = false;
        if (is_string($value)) {
            $this->value = $this->file_string_to_array($value);
            $this->is_set = true;
        }
        else if (is_array($value) && isset($value['path']) && isset($value['name'])) {
            $this->value = $value;
            $this->is_set = true;
        }
        return 0;
    }

    protected function file_string_to_array($value) {
        $result = array();
        list($filename, $filetype, $filesize, $filepath) = explode(':', $value);
        if (!$filepath) {
            return false;
        }
        $nc_core = nc_Core::get_object();
        $result['resultpath'] = $nc_core->SUB_FOLDER . $nc_core->HTTP_FILES_PATH . $filepath;
        $result['path'] = $result['resultpath'];
        $result['type'] = $filetype;
        $result['size'] = $filesize;
        $result['name'] = $filename;
        $result['all'] = $value;
        return $result;
    }

    public function save($value) {
        $nc_core = nc_Core::get_object();

        $array_name = $this->parent->get_array_name();

        if (!empty($value['old']) && !empty($value['kill'])) {
            list ($filename, $filetype, $filesize, $filepath) = explode(':', $value['old']);
            unlink($nc_core->FILES_FOLDER . $filepath);
            $this->value = $value['old'] = '';
        }

        if ($_FILES[$array_name]['error'][$this->name]) {
            if ($value['old']) {
                $this->value = $value['old'];
            }
            return 0;
        }

        $tmp_name = $_FILES[$array_name]['tmp_name'][$this->name];
        $filetype = $_FILES[$array_name]['type'][$this->name];
        $filename = $_FILES[$array_name]['name'][$this->name];

        // nothing was changed
        if (!empty($value['old']) && empty($value['kill']) && !$filetype) {
            if ($value['old']) {
                $this->value = $value['old'];
            }
            return 0;
        }

        $folder = $nc_core->FILES_FOLDER . 'cs/';
        $put_file_name = nc_transliterate($filename);
        $put_file_name = nc_get_filename_for_original_fs($put_file_name, $folder, array());

        $nc_core->files->create_dir($folder);
        move_uploaded_file($tmp_name, $folder . $put_file_name);
        $filesize = filesize($folder . $put_file_name);
        if ($filesize) {
            $this->value = $filename . ':' . $filetype . ':' . $filesize . ':cs/' . $put_file_name;
        }
        else {
            $this->value = '';
        }

        $this->upload = true;
        $this->filename = $filename;
        $this->filetype = $filetype;
        $this->filesize = $filesize;
        $this->filepath = $folder . $put_file_name;
    }
}

