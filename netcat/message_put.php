<?php

if (!class_exists("nc_System")) {
    die("Unable to load file.");
}

$updateString = "";
$fieldString = "";
$valueString = "";

$SQL_multifield = array();

// $i - счетчик полей
// $j - счетчик закаченных файлов

$multiple_changes = +$_POST['multiple_changes'];
$nc_multiple_changes = (array)$_POST['nc_multiple_changes'];
$updateStrings_tmp = array();


/**
 * @var int $fldCount
 * @var array $fld
 * @var array $fldType
 * @var array $fldNotNull
 * @var array $fldFmt
 * @var array $fldTypeOfEdit
 * @var array $fldDefault
 * @var array $fldID
 * @var array $fldFS
 * @var array $fldDisposition
 * @var nc_core $nc_core
 * @var nc_db $db
 */

do {
    if ($multiple_changes) {
        if (list($msg_id, $multiple_changes_fields) = each($nc_multiple_changes)) {
            foreach ($multiple_changes_fields as $multiple_changes_key => $multiple_changes_value) {
                $fldValue[array_search($multiple_changes_key, $fld)] = $multiple_changes_value;
            }
        } else {
            break; // выход из цикла do() если были перебраны все записи в $nc_multiple_changes
        }

        foreach (array('Priority', 'Keyword') as $system_field) {
            if (isset($nc_multiple_changes[$msg_id][$system_field])) {
                $updateStrings_tmp[] = "`$system_field` = '" . $db->escape($nc_multiple_changes[$msg_id][$system_field]) . "'";
            }
        }
    }

    $KeywordDefined = $KeywordNewValue = null;

    for ($i = 0, $j = 0; $i < $fldCount; $i++) {
        if (!(
                ($fldType[$i] == NC_FIELDTYPE_BOOLEAN && $fldNotNull[$i]) ||
                 $fldType[$i] == NC_FIELDTYPE_RELATION ||
                 $fldType[$i] == NC_FIELDTYPE_DATETIME ||
                ($fldType[$i] == NC_FIELDTYPE_MULTISELECT && !$multiple_changes))
             && !isset($_REQUEST["f_" . $fld[$i]])
             && !isset(${"f_" . $fld[$i]})
            && !isset($multiple_changes_fields[$fld[$i]])
        ) {
            $fldValue[$i] = '""';
            continue;
        }

        if (
            $nc_core->input->fetch_post_get('partial') &&
            ($fldType[$i] == NC_FIELDTYPE_BOOLEAN || $fldType[$i] == NC_FIELDTYPE_MULTISELECT) &&
            !isset($_REQUEST["f_" . $fld[$i]]) &&
            !isset(${"f_" . $fld[$i]})
        ) {
            $fldValue[$i] = '""';
            continue;
        }

        // set zero value for checkbox, if not checked - not in $_REQUEST
        if ($fldType[$i] == NC_FIELDTYPE_BOOLEAN && $fldNotNull[$i] && !isset($_REQUEST["f_" . $fld[$i]]) && !isset(${"f_" . $fld[$i]})) {
            $fldValue[$i] = 0;
            ${"f_" . $fld[$i]} = 0;
        }

        // для даты персонально
        if ($fldType[$i] == NC_FIELDTYPE_DATETIME) {
            $format = nc_field_parse_format($fldFmt[$i], NC_FIELDTYPE_DATETIME);
            switch ($format['type']) {
                case "event":
                    if (!(isset($_REQUEST["f_" . $fld[$i] . "_day"]) && isset($_REQUEST["f_" . $fld[$i] . "_month"]) && isset($_REQUEST["f_" . $fld[$i] . "_year"]) && isset($_REQUEST["f_" . $fld[$i] . "_hours"]) && isset($_REQUEST["f_" . $fld[$i] . "_minutes"]) && isset($_REQUEST["f_" . $fld[$i] . "_seconds"]))) {
                        continue 2;
                    }
                    break;
                case "event_date":
                    if (!(isset($_REQUEST["f_" . $fld[$i] . "_day"]) && isset($_REQUEST["f_" . $fld[$i] . "_month"]) && isset($_REQUEST["f_" . $fld[$i] . "_year"]))) {
                        continue 2;
                    }
                    break;
                case "event_time":
                    if (!(isset($_REQUEST["f_" . $fld[$i] . "_hours"]) && isset($_REQUEST["f_" . $fld[$i] . "_minutes"]) && isset($_REQUEST["f_" . $fld[$i] . "_seconds"]))) {
                        continue 2;
                    }
                    break;
                default: // В общем случае - меняем только если прислали хотя бы одно поле
                    if (!(isset($_REQUEST["f_" . $fld[$i] . "_day"]) || isset($_REQUEST["f_" . $fld[$i] . "_month"]) || isset($_REQUEST["f_" . $fld[$i] . "_year"]) || isset($_REQUEST["f_" . $fld[$i] . "_hours"]) || isset($_REQUEST["f_" . $fld[$i] . "_minutes"]) || isset($_REQUEST["f_" . $fld[$i] . "_seconds"]))) {
                        continue 2;
                    }
                    break;
            }
        }

        if ($fldType[$i] == NC_FIELDTYPE_STRING || $fldType[$i] == NC_FIELDTYPE_TEXT || $fldType[$i] == NC_FIELDTYPE_DATETIME || $fldType[$i] == NC_FIELDTYPE_MULTISELECT) {
            if (NC_FIELDTYPE_TEXT == $fldType[$i]) {
                $format = nc_field_parse_format($fldFmt[$i], NC_FIELDTYPE_TEXT);
            }

            //транслитерация
            if (NC_FIELDTYPE_STRING == $fldType[$i]) {
                //транслитерируем только, если пользователь сам не ввел значение поля, чтобы позволить ему вводить свои собственные
                if ($format_string[$i]['use_transliteration'] == 1 && empty($_REQUEST['f_' . $format_string[$i]['transliteration_field']])) {
                    $fieldValue = nc_transliterate($fldValue[$i], ($format_string[$i]['use_url_rules'] == 1 ? true : false));
                    if ($format_string[$i]['transliteration_field'] == 'Keyword') {
                        $fieldValue = nc_check_keyword_name($message, $fieldValue, $classID, $sub);
                    }
                    $updateString .= "`" . $format_string[$i]['transliteration_field'] . "` = \"" . $fieldValue . "\", ";
                    ${$format_string[$i]['transliteration_field'] . 'Defined'} = true;
                    ${$format_string[$i]['transliteration_field'] . 'NewValue'} = "\"" . $fieldValue . "\"";
                }
            }
            $fldValue[$i] = str_replace("\\'", "'", addslashes($fldValue[$i]));
            if ($fldType[$i] == NC_FIELDTYPE_DATETIME && empty($fldValue[$i])) {
                $fldValue[$i] = "NULL";
            } else {
                $fldValue[$i] = "\"" . $fldValue[$i] . "\"";
            }
        }

        if ($fldValue[$i] == "" && ($fldType[$i] == NC_FIELDTYPE_INT || $fldType[$i] == NC_FIELDTYPE_FLOAT || $fldType[$i] == NC_FIELDTYPE_SELECT || $fldType[$i] == NC_FIELDTYPE_RELATION)) {

            if ($fldNotNull[$i]) {
                if ($fldTypeOfEdit[$i] == 1) {
                    $fldValue[$i] = "NULL";
                }
                if ($fldTypeOfEdit[$i] > 1 && $fldDefault[$i] != "") {
                    $fldValue[$i] = "\"\"";
                }
            } else {
                if ($fldTypeOfEdit[$i] > 1 && $fldDefault[$i] != "") {
                    $fldValue[$i] = "\"\"";
                } // int
                elseif ($fldType[$i] == NC_FIELDTYPE_INT && $fldDefault[$i] != "" && $fldDefault[$i] == strval(intval($fldDefault[$i]))) {
                    $fldValue[$i] = "\"" . $fldDefault[$i] . "\"";
                } // float
                elseif ($fldType[$i] == NC_FIELDTYPE_FLOAT && $fldDefault[$i] != "" && $fldDefault[$i] == strval(str_replace(",", ".", floatval($fldDefault[$i])))) {
                    $fldValue[$i] = "\"" . $fldDefault[$i] . "\"";
                } // list
                elseif ($fldType[$i] == NC_FIELDTYPE_SELECT && $fldValue[$i] !== false) {
                    $fldValue[$i] = 0;
                } else {
                    $fldValue[$i] = "NULL";
                }
            }
        }

        if (NC_FIELDTYPE_MULTIFILE == $fldType[$i]) {
            $settings = $_POST['settings_' . $fld[$i]];

            if (!function_exists('nc_message_put_set_if')) {

                function nc_message_put_set_if($array, $key = 0) {
                    return "IF(ID = $array[$key], $key, " . (isset($array[++$key]) ? nc_message_put_set_if($array, $key) : --$key) . ")";
                }

            }

            $priority_array = array_map('intval', (array)$_POST['priority_multifile'][$fldID[$i]]);

            if ($priority_array[0]) {
                $SQL = "UPDATE `Multifield`
                           SET `Priority` = " . nc_message_put_set_if($priority_array) . "
                         WHERE `ID` IN (" . join(', ', $priority_array) . ")";
                $db->query($SQL);
            }

            if (!$settings['path']) {
                $settings['http_path'] = nc_standardize_path_to_folder($nc_core->HTTP_FILES_PATH . "/multifile/{$fldID[$i]}/");
            } else {
                $settings['http_path'] = $db->escape(nc_standardize_path_to_folder($settings['path']));
            }
            $settings['path'] = nc_standardize_path_to_folder($nc_core->DOCUMENT_ROOT . '/' . $nc_core->SUB_FOLDER . '/' . $settings['http_path']);

            if (!is_dir($settings['path'])) {
                $folders = explode('/', rtrim($settings['path'], '/'));

                for ($all = $end = count($folders) - 1; $all > 0; --$all) {
                    $folder_tmp[] = array_pop($folders);
                    if (is_dir(join('/', $folders))) {
                        break;
                    }
                }

                $folder_tmp = array_reverse($folder_tmp);

                for ($start = 0; $all <= $end; ++$start, ++$all) {
                    $folders[] = $folder_tmp[$start];
                    mkdir(join('/', $folders));
                }
            }

            $field_files = nc_save_multifile_from_post($fldID[$i], $fld[$i], $settings);

            if (is_array($settings['resize']) || is_array($settings['preview']) || is_array($settings['crop'])) {
                require_once $nc_core->INCLUDE_FOLDER . "classes/nc_imagetransform.class.php";
                foreach ($field_files as $file) {
                    if (!is_file($settings['path'] . $file['name'])) {
                        continue;
                    }

                    if ($settings['use_preview']) {
                        $mfs_preview_path = $settings['path'] . 'preview_' . $file['name'];
                        copy($settings['path'] . $file['name'], $mfs_preview_path);
                        if ($settings['preview']['width'] && $settings['preview']['height']) {
                            @nc_ImageTransform::imgResize($mfs_preview_path, $mfs_preview_path, $settings['preview']['width'], $settings['preview']['height'], $settings['preview']['mode']);
                        }
                        $mfs_crop = $settings['preview']['crop'];
                        if  (($mfs_crop['x1'] && $mfs_crop['y1']) || ($mfs_crop['mode'] == 1 && $mfs_crop['width'] && $mfs_crop['height'])) {
                            $preview_crop_ignore = $settings['preview']['crop_ignore']['width'] && $settings['preview']['crop_ignore']['height'];
                            @nc_ImageTransform::imgCrop($mfs_preview_path, $mfs_preview_path, $mfs_crop['x0'], $mfs_crop['y0'], $mfs_crop['x1'], $mfs_crop['y1'],
                                NULL, 90, 0, 0,
                                $preview_crop_ignore ? $settings['preview']['crop_ignore']['width'] : 0, $preview_crop_ignore  ? $settings['preview']['crop_ignore']['height'] : 0,
                                $mfs_crop['mode'], $mfs_crop['width'], $mfs_crop['height']
                            );
                        }
                    }

                    if ($settings['resize']['width'] && $settings['resize']['height']) {
                        @nc_ImageTransform::imgResize(
                            $settings['path'] .$file['name'],
                            $settings['path'] . $file['name'],
                            $settings['resize']['width'],
                            $settings['resize']['height'],
                            $settings['resize']['mode']
                        );
                    }

                    $mfs_crop = $settings['crop'];
                    if (($mfs_crop['x1'] && $mfs_crop['y1']) || ($mfs_crop['mode'] && $mfs_crop['width'] && $mfs_crop['height'])) {
                        $crop_ignore = $settings['crop_ignore']['width'] && $settings['crop_ignore']['height'];
                        @nc_ImageTransform::imgCrop(
                            $settings['path'] . $file['name'],
                            $settings['path'] . $file['name'],
                            $mfs_crop['x0'],
                            $mfs_crop['y0'],
                            $mfs_crop['x1'],
                            $mfs_crop['y1'],
                            NULL,
                            90,
                            0,
                            0,
                            $crop_ignore ? $settings['crop_ignore']['width'] : 0,
                            $crop_ignore ? $settings['crop_ignore']['height'] : 0,
                            $mfs_crop['mode'],
                            $mfs_crop['width'],
                            $mfs_crop['height']
                        );
                    }
                }
            }

            foreach ($field_files as $index => $file) {
                if (isset($_POST['multifile_new_priority'][$fldID[$i]][$index])) {
                    $priority = $_POST['multifile_new_priority'][$fldID[$i]][$index];
                }
                else if (isset($_POST['priority_multifile'])) {
                    $priority = sizeof($_POST['priority_multifile'][$fldID[$i]]) + $index;
                }
                else {
                    $priority = $index;
                }

                $values = array(
                    $fldID[$i],
                    '%msgID%',
                    "'" . $db->escape($file['custom_name']) . "'",
                    $file['size'],
                    "'" . $settings['http_path'] . $file['name'] . "'",
                    "'" . ($settings['preview'] ? $settings['http_path'] . 'preview_' . $file['name'] : '') . "'",
                    (int)$priority
                );

                $SQL_multifield[] = "(" . join(', ', $values) . ")";
            }

            $fldValue[$i] = '""';
        }


        if ($fldType[$i] == NC_FIELDTYPE_FILE) {
            $fldValue[$i] = $_FILES["f_" . $fld[$i]]["tmp_name"];

            if ($fldValue[$i] && $fldValue[$i] != "none" && is_uploaded_file($fldValue[$i])) {
                $_FILES["f_" . $fld[$i]]["name"] = str_replace(array('<', '>'), '_', $_FILES["f_" . $fld[$i]]["name"]);

                if ($user_table_mode && $action != "add" && !$message) {
                    $message = $AUTH_USER_ID;
                }
                if ($systemTableID == 4) {
                    $message = $TemplateID;
                }

                //перехват альтернативной папки из условий добавления/изменения
                $_FILES["f_" . $fld[$i]]['folder'] = ${"f_" . $fld[$i]}['folder'];

                $file_info = $nc_core->files->field_save_file(
                        $systemTableID ? $nc_core->get_system_table_name_by_id($systemTableID) : $classID,
                        $fldID[$i], $message, $_FILES["f_" . $fld[$i]], false,
                        array('sub' => $sub, 'cc' => $cc), false, false);

                //строка для записи в БД
                $fldValue[$i]           = $file_info['fldValue'];

                // save file path in the $f_Field_url
                ${"f_" . $fld[$i] . "_url"}         = $file_info['url'];
                ${"f_" . $fld[$i] . "_preview_url"} = $file_info['preview_url'];
                ${"f_" . $fld[$i] . "_name"}        = $file_info['name'];
                ${"f_" . $fld[$i] . "_size"}        = $file_info['size'];
                ${"f_" . $fld[$i] . "_type"}        = $file_info['type'];

                $j++;
            } elseif ($fldValue[$i] == "" || $fldValue[$i] == "none") {
                eval("\$fldValue[\$i] = \$f_" . $fld[$i] . "_old;");
            }

            $fldValue[$i] = "\"" . $db->escape($fldValue[$i]) . "\"";

        }

        if (($fldTypeOfEdit[$i] == 1 || (nc_field_check_admin_perm() && $fldTypeOfEdit[$i] == 2)) && empty(${$fld[$i] . "Defined"})) {
            $fieldString .= "`" . $fld[$i] . "`,";
            $valueString .= $fldValue[$i] . ",";
            if ($action == "change" && !($user_table_mode && $fld[$i] == $AUTHORIZE_BY && !($nc_core->get_settings('allow_change_login', 'auth') || in_array($current_user['UserType'], array('fb', 'vk', 'twitter', 'openid'))))) {
                $updateString .= "`" . $fld[$i] . "` = " . $fldValue[$i] . ", ";
            }
        }

        if ($multiple_changes) {
            $updateStrings_tmp[] = "`{$fld[$i]}` = {$fldValue[$i]}";
        }
    }

    $updateStrings[$msg_id] = join(', ', $updateStrings_tmp);
    $updateStrings_tmp = array();

} while ($multiple_changes);

if (!$user_table_mode && $cc && is_object($perm) && $perm->isSubClass($cc, MASK_MODERATE)) {
    $nc_fields_seo = array('ncTitle', 'ncKeywords', 'ncDescription', 'ncSMO_Title', 'ncSMO_Description');
    foreach ($nc_fields_seo as $nc_field) {
        if (!$nc_multiple_changes && isset($_REQUEST["f_$nc_field"])) {
            $nc_field_value = $db->escape(${"f_$nc_field"});
            $updateString .= "`$nc_field` = '$nc_field_value', ";
            $fieldString .= "`$nc_field`,";
            $valueString .= "'$nc_field_value',";
        }
    }
}

/**
 * Функция проверки ключевого слова объекта на уникальность, в случае совпадения
 * возвращает с уникальным числовым постфиксом "-номер"
 *
 * @param int $message_id
 * @param string $keyword
 * @param int $component_id
 * @param int $subdivision_id
 * @return string|null
 */
function nc_check_keyword_name($message_id = 0, $keyword, $component_id, $subdivision_id) {
    if (!$keyword) {
        return null;
    }

    $component_id = (int)$component_id;
    $message_id = (int)$message_id;
    $subdivision_id = (int)$subdivision_id;

    $db = nc_core::get_object()->db;

    $query_template =
        "(SELECT `Keyword`
           FROM `Message{$component_id}`
          WHERE `Subdivision_ID` = $subdivision_id
            AND `Keyword` #keyword_condition#
            AND `Message_ID` != $message_id)
         UNION DISTINCT
         (SELECT `EnglishName` AS `Keyword`
           FROM `Sub_Class`
          WHERE `Subdivision_ID` = $subdivision_id
            AND `EnglishName` #keyword_condition#)";

    $has_object_with_same_keyword_query = str_replace(
        '#keyword_condition#',
        "= '" . $db->escape($keyword) . "'",
        $query_template
    );
    $has_object_with_same_keyword = $db->get_var($has_object_with_same_keyword_query);

    if ($has_object_with_same_keyword) {
        // если уже заканчивается на "-число" — убираем его
        $keyword_without_postfix = preg_replace('/(-\d+)$/', '', $keyword);

        // выбираем ключевое слово с максимальной цифрой
        $max_existing_keyword_query = str_replace(
            '#keyword_condition#',
            "REGEXP '^" . $db->escape(preg_quote($keyword_without_postfix)) . "-[0-9]+$'",
            $query_template
        );

        $max_existing_keyword_query .= " ORDER BY LENGTH(`Keyword`) DESC, `Keyword` DESC LIMIT 1";
        $max_existing_keyword = $db->get_var($max_existing_keyword_query);
        if ($max_existing_keyword) {
            preg_match('/-(\d+)$/', $max_existing_keyword, $match);
            $keyword = $keyword_without_postfix . "-" . ($match[1] + 1);
        } else {
            $keyword = $keyword_without_postfix . "-1";
        }
    }

    return $keyword;
}
