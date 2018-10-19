<?php

class nc_Template extends nc_Essence {

    const PARTIALS_DIR = 'partials';
    const TEMPLATE_EXT = 'html';
    const MAX_KEYWORD_LENGTH = 64;

    protected $db;
    protected $table;

    protected $partials_cache;
    protected $template_settings_cache;
    protected $keyword_cache;

    /**
     * Constructor function
     */
    public function __construct() {
        parent::__construct();

        $this->db    = $this->core->db;
        $this->table = nc_db_table::make('Template');
    }

    public function convert_subvariables($template_env) {
        // load system table fields
        $table_fields = $this->core->get_system_table_fields($this->essence);
        // count
        $counted_fileds = count($table_fields);

        // %FIELD replace with inherited template field value
        for ($i = 0; $i < $counted_fileds; $i++) {
            $template_env["Header"] = str_replace("%".$table_fields[$i]['name'], $template_env[$table_fields[$i]['name']], $template_env["Header"]);
            $template_env["Footer"] = str_replace("%".$table_fields[$i]['name'], $template_env[$table_fields[$i]['name']], $template_env["Footer"]);
        }

        return $template_env;
    }

    protected function inherit($template_env) {
        global $perm, $AUTH_USER_ID, $templatePreview;

        // Блок для предпросмотра макетов дизайна
        $magic_gpc = get_magic_quotes_gpc();
        if ($template_env["Template_ID"] == $templatePreview && !empty($_SESSION["PreviewTemplate"][$templatePreview])) {
            foreach ($_SESSION["PreviewTemplate"][$templatePreview] as $key => $value) {
                $template_env[$key] = $magic_gpc ? stripslashes($value) : $value;
            }
        }

        $parent_template = $template_env["Parent_Template_ID"];

        if ($parent_template) {
            $parent_template_env = $this->get_by_id($parent_template);

            // Если мы вызываем предпросмотр для макета, а он используется в качестве родительского.
            if ($parent_template_env["Template_ID"] == $templatePreview && !empty($_SESSION["PreviewTemplate"][$templatePreview])) {
                foreach ($_SESSION["PreviewTemplate"][$templatePreview] as $key => $value) {
                    $parent_template_env[$key] = $magic_gpc ? stripslashes($value) : $value;
                }
            }

            $parent_template = $template_env["Parent_Template_ID"];

            if (!$template_env["Header"]) {
                $template_env["Header"] = $parent_template_env["Header"];
            } else {
                if ($parent_template_env["Header"]) {
                    $template_env["Header"] = str_replace("%Header", $parent_template_env["Header"], $template_env["Header"]);
                }
            }
            if (!$template_env["Footer"]) {
                $template_env["Footer"] = $parent_template_env["Footer"];
            } else {
                if ($parent_template_env["Footer"]) {
                    $template_env["Footer"] = str_replace("%Footer", $parent_template_env["Footer"], $template_env["Footer"]);
                }
            }
            $template_env["Settings"] = $parent_template_env["Settings"].$template_env["Settings"];

            $template_env = $this->inherit_system_fields($this->essence, $parent_template_env, $template_env);
            $parent_template = $parent_template_env["Parent_Template_ID"];
        }

        return $template_env;
    }

    public function update($template_id, $params = array()) {
        $db = $this->db;

        $template_id = intval($template_id);
        if (!$template_id || !is_array($params)) {
            return false;
        }

        $query = array();
        foreach ($params as $k => $v) {
            $query[] = "`".$k."` = '".(preg_match('/validate_regexp/', $v) ? $db->prepare($v) : $db->escape($v))."'";
        }

        if (!empty($query)) {
            $db->query("UPDATE `Template` SET ".join(', ', $query)." WHERE `Template_ID` = '".$template_id."' ");
            if ($db->is_error)
                    throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
        }

        //unset($this->data[$template_id]);
        $this->data = array();
        return true;
    }

    public function get_parent($id, $all = 0) {
        $id = intval($id);
        $ret = array();
        $parent_id = $this->db->get_var("SELECT `Parent_Template_ID` FROM `Template` WHERE `Template_ID` = '".$id."' ");

        if (!$all) return intval($parent_id);

        if ($parent_id) {
            $ret[] = $parent_id;
            $ret = array_merge($ret, $this->get_parent($parent_id, 1));
        }

        return $ret;
    }

    public function get_childs($id) {
        $ret = array();
        $childs = $this->db->get_col("SELECT `Template_ID` FROM `Template` WHERE `Parent_Template_ID` = '".intval($id)."'");

        if (!empty($childs))
                foreach ($childs as $v) {
                $ret[] = $v;
                $ret = array_merge($ret, $this->get_childs($v));
            }


        return $ret;
    }

    /**
     * Возвращает абсолютный путь к папке с дополнительными шаблонами (partials)
     * или к конкретному дополнительному шаблону, если задан параметр $partial.
     * @param  integer $template_id
     * @param  string  $partial       ключевое слово дополнительного шаблона
     * @return string
     */
    public function get_partials_path($template_id, $partial = null) {
        if ($partial) {
            $partial .= '.' . self::TEMPLATE_EXT;
        }
        return rtrim($this->core->TEMPLATE_FOLDER, '/') .
               $this->get_by_id($template_id, 'File_Path') .
               self::PARTIALS_DIR . '/' .
               $partial;
    }

    /**
     * Возвращает true если макет имеет дополнительные шаблоны (partials) (только при File_Mode = 1)
     * @param  integer $template_id
     * @param  string  $name
     * @return boolean
     */
    public function has_partial($template_id, $name = null) {
        $partials = $this->get_template_partials($template_id);

        return $name ? isset($partials[$name]) : count($partials);
    }

    /**
     * Возвращает список дополнительных шаблонов для заданного макета дизайна
     * @param  integer $template_id
     * @return array
     */
    public function get_template_partials($template_id) {
        if (!isset($this->partials_cache[$template_id])) {
            $this->partials_cache[$template_id] = array();
            $partials_folder = $this->get_partials_path($template_id);
            if (file_exists($partials_folder)) {
                $files = scandir($partials_folder);
                foreach ($files as $file) {
                    if (is_file($partials_folder . $file)) {
                        $info = pathinfo($partials_folder . $file);
                        if ($info['extension'] == self::TEMPLATE_EXT) {
                            $name = $info['filename'];
                            $this->partials_cache[$template_id][$name] = $name;
                        }
                    }
                }
            }
        }

        return $this->partials_cache[$template_id];
    }


    /**
     * Возвращает массив с параметрами пользовательских настроек макета дизайна,
     * с учётом наследования по иерархии макетов
     * @param int $template_id
     * @return array
     */
    public function get_custom_settings($template_id) {
        $settings_hierarchy = array(); // настройки макетов, начиная снизу
        $template = array('Parent_Template_ID' => $template_id);
        while ($template['Parent_Template_ID']) {
            $template = $this->get_by_id($template['Parent_Template_ID']);
            $template_settings = nc_a2f::evaluate($template['CustomSettings']);
            if ($template_settings) { $settings_hierarchy[] = $template_settings; }
        }

        $i = count($settings_hierarchy);
        // для начала положим в результат настройки текущего макета, чтобы они были сверху списка:
        $inherited_settings = $settings_hierarchy[0];
        while (--$i >= 0) {
            $inherited_settings = array_merge($inherited_settings, $settings_hierarchy[$i]);
        }

        return (array)$inherited_settings;
    }

    /**
     * Возвращает значения по умолчанию, указанные для настроек макета дизайна
     * @param $template_id
     * @return array
     */
    public function get_settings_default_values($template_id) {
        if (!isset($this->template_settings_cache[$template_id])) {
            $nc_a2f = new nc_a2f($this->get_custom_settings($template_id));
            $this->template_settings_cache[$template_id] = $nc_a2f->get_values_as_array();
        }

        return $this->template_settings_cache[$template_id];
    }

    /**
     * @param string $keyword
     * @param int|null $template_id    ID макета дизайна, которому предназначается ключевое слово
     * @param int|null $parent_template_id
     * @return bool|string   возвращает true или текст ошибки
     */
    public function validate_keyword($keyword, $template_id = null, $parent_template_id = null) {
        $keyword = trim($keyword);
        $length = strlen($keyword);
        $template_id = (int)$template_id;

        // Пустое ключевое слово — OK
        if ($length == 0) {
            return true;
        }

        // Длина больше 64 — не ОК
        if ($length > self::MAX_KEYWORD_LENGTH) {
            return sprintf(CONTROL_TEMPLATE_KEYWORD_TOO_LONG, self::MAX_KEYWORD_LENGTH);
        }

        // Только цифры — не ОК
        if (preg_match('/^\d+$/', $keyword)) {
            return CONTROL_TEMPLATE_KEYWORD_ONLY_DIGITS;
        }

        // В ключевом слове допустимы только a-z 0-9 _
        if (!preg_match('/^\w+$/', $keyword)) {
            return CONTROL_TEMPLATE_KEYWORD_INVALID_CHARACTERS;
        }

        // Зарезервированные слова
        if (preg_match('/^(?:partials|assets|images?|styles?|scripts?|fonts?|img|css|js)$/', $keyword)) {
            return sprintf(CONTROL_CLASS_KEYWORD_RESERVED, $keyword);
        }

        // Определяем родительский шаблон, если не указан
        if ($parent_template_id === null && $template_id) {
            $parent_template_id = (int)$this->get_by_id($template_id, 'Parent_Template_ID');
        }
        else {
            $parent_template_id = (int)$parent_template_id;
        }

        // Уникальность ключевого слова в пределах родителя
        $existing_component = $this->db->get_row(
            "SELECT `Template_ID`, `Description`
               FROM `Template`
              WHERE `Keyword` = '$keyword'
                AND `Parent_Template_ID` = $parent_template_id
                AND `Template_ID` != '$template_id'", ARRAY_A); // $keyword безопасен

        // Ключевое слово уже используется
        if ($existing_component) {
            return sprintf(CONTROL_TEMPLATE_KEYWORD_NON_UNIQUE, $keyword, htmlspecialchars($existing_component['Description']));
        }

        // Ключевое слово уже присвоено этому макету (т. е. не изменилось)
        if ($template_id && $this->get_by_id($template_id, 'Keyword') == $keyword) {
            return true;
        }

        // Не должно быть папки с таким именем
        $parent_path = $parent_template_id ? $this->get_by_id($parent_template_id, 'File_Path') : "";
        $template_path = $parent_path . $keyword . '/';

        if (!$template_id || $template_path != $this->get_by_id($template_id, 'File_Path')) {
            $target_path = nc_core::get_object()->TEMPLATE_FOLDER . ltrim($parent_path, '/') . $keyword;
            if (file_exists($target_path)) {
                return sprintf(CONTROL_TEMPLATE_KEYWORD_PATH_EXISTS, $keyword);
            }
        }

        // Претензий не имеем
        return true;
    }

    /**
     * Получение ID макета по ключевому слову (для «корневых» макетов)
     * @param string $keyword
     * @return int
     */
    protected function get_id_by_keyword($keyword) {
        if (!isset($this->keyword_cache[$keyword])) {
            $this->keyword_cache[$keyword] = (int)$this->db->get_var(
                "SELECT `Template_ID`
                   FROM `Template`
                  WHERE `Parent_Template_ID` = 0
                    AND `Keyword` = '" . $this->db->escape($keyword) . "'"
            );
        }
        return $this->keyword_cache[$keyword];
    }

    /**
     *
     */
    public function clear_cache() {
        unset($this->data, $this->partials_cache, $this->template_settings_cache, $this->keyword_cache);
    }


}