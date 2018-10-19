<?php

/**
 * @property nc_multifield_settings $settings
 * @property nc_multifield_template $template
 * @property array $records
 * @property string $name
 * @property string $desc
 * @property string $format
 * @property int $id
 * @property array $format_parsed
 */
class nc_multifield {

    private $settings = null;
    private $template = null;
    private $records = array();
    private $name = null;
    private $id = null;
    private $desc = null;
    private $format;
    private $format_parsed;

    public function __construct($name, $desc = null, $format = null, $id = null) {
        $this->settings = new nc_multifield_settings($this);
        $this->template = new nc_multifield_template($this);
        $this->name = $name;
        $this->desc = $desc;
        $this->format = $format;
        $this->id = $id;

        require_once(nc_Core::get_object()->INCLUDE_FOLDER . '../admin/class.inc.php');
        $this->format_parsed = nc_field_parse_resize_options($this->format);

        if ($this->format_parsed['use_resize']) {
            $this->settings->resize($this->format_parsed['resize_width'], $this->format_parsed['resize_height']);
        }

        if ($this->format_parsed['use_crop']) {
            $this->settings->crop(
                    $this->format_parsed['crop_x0'], $this->format_parsed['crop_x1'],
                    $this->format_parsed['crop_y0'], $this->format_parsed['crop_y1'],
                    $this->format_parsed['crop_mode'], $this->format_parsed['crop_width'], $this->format_parsed['crop_height']
                    );
        }

        if ($this->format_parsed['crop_ignore']) {
            $this->settings->crop_ignore($this->format_parsed['crop_ignore_width'], $this->format_parsed['crop_ignore_height']);
        }

       if ($this->format_parsed['use_preview']) {
            if ($this->format_parsed['preview_use_resize'] || ($this->format_parsed['preview_width'] && $this->format_parsed['preview_height'])) {
                $this->settings->preview($this->format_parsed['preview_width'], $this->format_parsed['preview_height']);
            }
            if ($this->format_parsed['preview_use_crop']) {
                $this->settings->preview_crop(
                        $this->format_parsed['preview_crop_x0'], $this->format_parsed['preview_crop_x1'],
                        $this->format_parsed['preview_crop_y0'], $this->format_parsed['preview_crop_y1'],
                        $this->format_parsed['preview_crop_mode'], $this->format_parsed['preview_crop_width'], $this->format_parsed['preview_crop_height']
                        );
            }

            if ($this->format_parsed['preview_crop_ignore']) {
                $this->settings->preview_crop_ignore($this->format_parsed['preview_crop_ignore_width'], $this->format_parsed['preview_crop_ignore_height']);
            }
        }

        if ($this->format_parsed['multifile_min']) {
            $this->settings->min($this->format_parsed['multifile_min']);
        }
        if ($this->format_parsed['multifile_max']) {
            $this->settings->max($this->format_parsed['multifile_max']);
        }

    }

    public function set_data(array $data = null) {
        $this->records = $data;
        return $this;
    }

    public function add_record($record) {
        $this->records[] = $record;
    }
    
    public function set_template($template) {
        $this->template->set($template);
        return $this;
    }

    public function to_array() {
        return $this->records;
    }

    public function set_description($desc) {
        $this->desc = htmlspecialchars($desc);
    }

    public function form() {
        return $this->template->get_form();
    }

    public function get_record($record_num = 1) {
        $multifield = new self($this->name, $this->desc, $this->format, $this->id);
        return $multifield->set_data(array($this->records[+$record_num - 1]))->template->set($this->template->template)->get_html();
    }

    public function get_random_record() {
        return $this->get_record(mt_rand(1, $this->count()));
    }

    public function count() {
        return count($this->records);
    }

    public function __toString() {
        return $this->template->get_html();
    }

    public function __get($name) {
        return isset($this->$name) ? $this->$name : false;
    }
}