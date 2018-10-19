<?php

class nc_Page extends nc_System {

    protected $core;
    protected $metatags = array(); // title, keywords, description, smo_title, smo_description, smo_image
    // поля из таблицы Разделы, используемые для метаданных
    protected $title_field, $keywords_field, $description_field;
    protected $smo_title_field, $smo_description_field, $smo_image_field;
    protected $language_field;
    protected $field_usage = array();
    protected $h1 = null;
    protected $canonical_link;
    /** @var  nc_url */
    protected $url;

    /**
     *
     */
    public function __construct() {
        parent::__construct();
        $this->core = nc_Core::get_object();

        $fieldmap = $this->core->get_settings('FieldUsage');
        if ($fieldmap) {
            $fieldmap = $this->field_usage = unserialize($fieldmap);
        }

        $this->title_field = $fieldmap['title'];
        $this->keywords_field = $fieldmap['keywords'];
        $this->description_field = $fieldmap['description'];
        $this->smo_title_field = $fieldmap['smo_title'];
        $this->smo_description_field = $fieldmap['smo_description'];
        $this->smo_image_field = $fieldmap['smo_image'];
        $this->language_field = $fieldmap['language'];

        $events = "updateSubdivision,checkSubdivision,uncheckSubdivision,";
        $events .= "addSubClass,updateSubClass,checkSubClass,uncheckSubClass,dropSubClass,";
        $events .= "addMessage,updateMessage,checkMessage,uncheckeMessage,dropMessage";
        $this->core->event->bind($this, array($events => "update_subdivision"));

        $events = "updateClass,updateClassTemplate";
        $this->core->event->bind($this, array($events => "update_class"));

        $this->core->event->bind($this, array('updateTemplate' => "update_template"));
    }

    /**
     * Старое название метода fetch_page_metatags().
     * (Оставлено, т.к. по какой-то причине метод был описан в публичном API,
     * хотя вряд ли может пригодиться для разработчика сайтов.)
     *
     * @deprecated
     * @param string $url адрес страницы
     * @return array
     */
    public function get_meta_tags($url) {
        return $this->fetch_page_metatags($url);
    }

    /**
     * Функция получения title и мета-данных страниц.
     *
     * @param string $url адрес страницы
     * @return array
     */
    public function fetch_page_metatags($url) {
        $result = array();
        $contents = @file_get_contents($url);
        if (!$contents) {
            return false;
        }

        nc_preg_match('/<title>([^>]*)<\/title>/si', $contents, $match);

        if (isset($match) && is_array($match) && count($match) > 0) {
            $result['title'] = strip_tags($match[1]);
        }

        nc_preg_match_all('/<[\s]*meta[\s]*name=["\']?' . '([^>\'"]*)["\']?[\s]*' . 'content=["\']?([^>"\']*)["\']?[\s]*[\/]?[\s]*>/si', $contents, $match);

        if (isset($match) && is_array($match) && count($match) == 3) {
            $originals = $match[0];
            $names = $match[1];
            $values = $match[2];

            if (count($originals) == count($names) && count($names) == count($values)) {
                for ($i = 0, $limiti = count($names); $i < $limiti; $i++) {
                    $result[strtolower($names[$i])] = $values[$i];
                }
            }
        }

        return $result;
    }

    /**
     * Установить мета-тег для страницы
     *
     * @param string $item title, keywords, description, smo_title, smo_description, smo_image
     * @param string $value value
     */
    public function set_metatags($item, $value) {
        if ($item == 'smo_image') {
            // Пока что поле SMO_Image не обрабатывается как все прочие файловые
            // поля, а тип файловой системы — всегда NC_FS_ORIGINAL.
            // Преобразование raw-значения в путь файла производится здесь.
            $file_info = explode(':', $value);
            if (isset($file_info[3])) {
                $nc_core = nc_core::get_object();
                $files_path = $nc_core->SUB_FOLDER . $nc_core->HTTP_FILES_PATH;
                $value = $files_path . $file_info[3];
            }
        }

        $this->metatags[$item] = $value;
    }

    /**
     * Получить title для страницы
     *
     * @return string
     */
    public function get_title() {
        return nc_array_value($this->metatags, 'title', false);
    }

    /**
     * Получить keywords для страницы
     *
     * @return string|false
     */
    public function get_keywords() {
        return nc_array_value($this->metatags, 'keywords', false);
    }

    /**
     * Получить description для страницы
     *
     * @return string|false
     */
    public function get_description() {
        return nc_array_value($this->metatags, 'description', false);
    }

    /**
     * Получить SMO title для страницы
     *
     * @return string|false
     */
    public function get_smo_title() {
        return nc_array_value($this->metatags, 'smo_title', false);
    }

    /**
     * Получить SMO description для страницы
     *
     * @return string|false
     */
    public function get_smo_description() {
        return nc_array_value($this->metatags, 'smo_description', false);
    }

    /**
     * Получить путь к файлу SMO image для страницы (от корня сайта)
     *
     * @return string|false
     */
    public function get_smo_image() {
        return nc_array_value($this->metatags, 'smo_image', false);
    }

    /**
     * Получить блок мета-тэгов seo/smo для страницы
     *
     * @return string
     */
    public function get_metatags() {
        $meta_seo = $meta_smo_og = $meta_smo_twitter = '';
        $add_meta_smo_url = false;

        // SEO: keywords, description
        $keywords_value = $this->get_keywords();
        if ($keywords_value) {
            $meta_seo .= "\t<meta name=\"keywords\" content=\"" . htmlspecialchars($keywords_value, ENT_QUOTES) . "\" />\n";
        }

        $description_value = $this->get_description();
        if ($description_value) {
            $meta_seo .= "\t<meta name=\"description\" content=\"" . htmlspecialchars($description_value, ENT_QUOTES) . "\" />\n";
        }

        // SMO: title, description
        $smo_title_value = $this->get_smo_title();
        if ($smo_title_value) {
            $content = htmlspecialchars($smo_title_value, ENT_QUOTES);
            $meta_smo_og .= "\t<meta property=\"og:title\" content=\"" . $content . "\" />\n";
            $meta_smo_twitter .= "\t<meta property=\"twitter:title\" content=\"" . $content . "\" />\n";
            $add_meta_smo_url = true;
        }

        $smo_description_value = $this->get_smo_description();
        if ($smo_description_value) {
            $content = htmlspecialchars($smo_description_value, ENT_QUOTES);
            $meta_smo_og .= "\t<meta property=\"og:description\" content=\"" . $content . "\" />\n";
            $meta_smo_twitter .= "\t<meta property=\"twitter:description\" content=\"" . $content . "\" />\n";
            $add_meta_smo_url = true;
        }

        // SMO: image
        $smo_image_value = $this->get_smo_image();
        if ($smo_image_value) {
            $image_url = htmlspecialchars($this->get_url()->get_host_url() . $smo_image_value);
            $meta_smo_og .= "\t<meta property=\"og:image\" content=\"" . $image_url . "\" />\n";
            $meta_smo_twitter .= "\t<meta property=\"twitter:image\" content=\"" . $image_url . "\" />\n";
            $add_meta_smo_url = true;
        }

        // SMO: url
        if ($add_meta_smo_url) {
            $url = htmlspecialchars($this->get_url()->get_full_url());
            $meta_smo_og .= "\t<meta property=\"og:url\" content=\"" . $url . "\" />\n";
            $meta_smo_og .= "\t<meta property=\"og:type\" content=\"article\" />\n";
            $meta_smo_twitter .= "\t<meta property=\"twitter:url\" content=\"" . $url . "\" />\n";
            $meta_smo_twitter .= "\t<meta property=\"twitter:card\" content=\"summary\" />\n";
        }

        return $meta_seo . "\n" . $meta_smo_og . "\n" . $meta_smo_twitter;
    }

    /**
     * Установить метаданные по данным текущего раздела
     *
     * @param int $current_sub
     */
    public function set_current_metatags($current_sub) {
        if ($current_sub[$this->title_field]) {
            $this->set_metatags('title', $current_sub[$this->title_field]);
        }
        if ($current_sub[$this->keywords_field]) {
            $this->set_metatags('keywords', $current_sub[$this->keywords_field]);
        }
        if ($current_sub[$this->description_field]) {
            $this->set_metatags('description', $current_sub[$this->description_field]);
        }
        if ($current_sub[$this->smo_title_field]) {
            $this->set_metatags('smo_title', $current_sub[$this->smo_title_field]);
        }
        if ($current_sub[$this->smo_description_field]) {
            $this->set_metatags('smo_description', $current_sub[$this->smo_description_field]);
        }
        if ($current_sub[$this->smo_image_field]) {
            $this->set_metatags('smo_image', $current_sub[$this->smo_image_field]);
        }
    }

    /**
     * Имя поля, которое используется для языка
     */
    public function get_language_field() {
        return $this->language_field;
    }

    public function get_field_name($usage) {
        return $this->field_usage[$usage];
    }

    /**
     * Обновление Last-Modified у разделов
     *
     * @param int|array $sub_ids номер раздела или массив с номерами, если 0 - то все
     * @return bool
     */
    public function update_lastmodified($sub_ids = 0) {
        if (is_int($sub_ids) && $sub_ids === 0) {
            $where = '';
        }
        else {
            if (!is_array($sub_ids)) {
                $sub_ids = array($sub_ids);
            }
            $sub_ids = array_unique(array_map('intval', $sub_ids));
            if (empty($sub_ids)) {
                return false;
            }
            $where = " WHERE Subdivision_ID IN (" . join(',', $sub_ids) . ") ";
        }

        $this->core->db->query("UPDATE `Subdivision` SET `" . $this->get_field_name('last_modified') . "` = NOW() " . $where);
    }

    /**
     * Перехват события "изменение раздела" для обновления Last-Modified
     *
     * @param int $catalogue_id
     * @param int|array $sub_id
     */
    public function update_subdivision($catalogue_id, $sub_id) {
        $res = array();
        if (is_array($sub_id)) {
            foreach ($sub_id as $v) {
                $res = array_merge($res, nc_get_sub_children($v));
            }
        }
        else {
            $res = nc_get_sub_children($sub_id);
        }

        $this->update_lastmodified($res);
    }

    /**
     * Перехват события "изменение инфоблока" для обновления Last-Modified
     *
     * @param int|array $class_id
     */
    public function update_class($class_id) {
        $db = $this->core->db;
        if (!is_array($class_id)) {
            $class_id = array($class_id);
        }
        $class_id = array_map('intval', $class_id);
        $subs = $db->get_col(
            "SELECT sc.Subdivision_ID
               FROM `Sub_Class` AS `sc`, `Class` AS `c`
              WHERE sc.Class_ID = c.Class_ID
                AND (
                        c.Class_ID IN (" . join(',', $class_id) . ")
                        OR c.ClassTemplate IN (" . join(',', $class_id) . ")
                    )"
        );

        $this->update_lastmodified($subs);
    }

    /**
     * Перехват события "изменение макета дизайна"
     * @param int $id
     */
    public function update_template($id) {
        $db = $this->core->db;
        $id = intval($id);

        $childs = $this->core->template->get_childs($id);

        $t = array_merge(array($id), $childs);

        $cat = $db->get_var("SELECT `Catalogue_ID` FROM `Catalogue` WHERE `Template_ID` IN (" . join(',', $t) . ") ");

        if ($cat) {
            $this->update_lastmodified();
        }
        else {
            $subs = $db->get_col("SELECT `Subdivision_ID` FROM `Subdivision` WHERE `Template_ID` IN (" . join(',', $t) . ") ");
            if ($subs) {
                $this->update_subdivision(0, $subs);
            }
        }
    }

    /**
     * Метод посылает заголовок Last-Modified для текущего раздела
     * В зависимости от ncLastModifiedType заголовок может не посылаться,
     * посылаться текущее время или актуальное.
     * Для титульной страницы посылается текущее время.
     */
    public function send_lastmodified() {
        $current_sub = $this->core->subdivision->get_current();
        $title_sub_id = $this->core->catalogue->get_current('Title_Sub_ID');

        $lm = $this->get_field_name('last_modified');
        $lm_type = $this->get_field_name('last_modified_type');

        if ($current_sub[$lm_type] <= NC_LASTMODIFIED_NONE) {
            return 0;
        }

        $last_mod = false;
        if ($current_sub[$lm_type] == NC_LASTMODIFIED_CURRENT || $current_sub['Subdivision_ID'] == $title_sub_id) {
            $last_mod = time();
        }
        else if ($current_sub[$lm_type] == NC_LASTMODIFIED_YESTERDAY) {
            $last_mod = time() - 86400;
        }
        else if ($current_sub[$lm_type] == NC_LASTMODIFIED_HOUR) {
            $last_mod = time() - 3600;
        }
        else if ($current_sub[$lm_type] == NC_LASTMODIFIED_ACTUAL && $current_sub[$lm]) {
            $last_mod = strtotime($current_sub[$lm]);
        }

        if ($last_mod) {
            header("Last-Modified: " . nc_timestamp_to_gmt($last_mod));
        }
    }

    /**
     * @return string
     */
    public function get_h1() {
        return $this->h1;
    }

    /**
     * @param string $h1
     */
    public function set_h1($h1) {
        $this->h1 = $h1;
    }

    /**
     * @param mixed $canonical_link
     */
    public function set_canonical_link($canonical_link) {
        $this->canonical_link = $canonical_link;
    }

    /**
     * @return mixed
     */
    public function get_canonical_link() {
        return $this->canonical_link;
    }

    /**
     * Возвращает тэг <link rel="canonical"> для текущей страницы
     *
     * @return string
     */
    public function get_canonical_link_tag() {
        if (!$this->canonical_link) {
            return '';
        }
        else {
            return '<link rel="canonical" href="' . htmlspecialchars($this->canonical_link) . '">';
        }
    }

    /**
     * @return nc_url
     */
    protected function get_url() {
        return $this->url ?: nc_core::get_object()->url;
    }

    /**
     * @param nc_url $url
     */
    public function set_url(nc_url $url) {
        $this->url = $url;
    }

}