<?php

require ("Tar.php");  // /netcat/require/lib/

nc_tgz_check_exec();

// проверить, есть ли внешний tar и возможность его запустить
function nc_tgz_check_exec() {
    // Global setting: DISABLE_TGZ_EXEC -- установить в true если не работает system("tar")
    // check whether to use system() call to tar [faster]
    if (!$GLOBALS["DISABLE_TGZ_EXEC"] && !preg_match("/Windows/i", php_uname())) {  // it's not Windows
        $err_code = 127;
        $tgz_version = @exec("tar --version", $output, $err_code);
        define("SYSTEM_TAR", ($err_code ? false : true));
    } else {
        define("SYSTEM_TAR", false);
    }
}

// извлечь файл из архива
function nc_tgz_extract($archive_name, $dst_path) {
    global $DOCUMENT_ROOT;

    @set_time_limit(0);
    if (SYSTEM_TAR) {
        exec("cd $DOCUMENT_ROOT; tar -zxf $archive_name -C $dst_path 2>&1", $output, $err_code);
        if ($err_code && !strpos($output[0], "time")) { // ignore "can't utime, permission denied"
            trigger_error("$output[0]", E_USER_WARNING);
            return false;
        }
        return true;
    } else {
        $current_dir = realpath('.');
        chdir($DOCUMENT_ROOT);

        $tar_object = new Archive_Tar($archive_name, "gz");
        $tar_object->setErrorHandling(PEAR_ERROR_PRINT);
        $result = $tar_object->extract($dst_path);

        chdir($current_dir);

        return $result;
    }
}

/* Создание архива формата .tgz
 *
 * @param $archive_name: имя создаваемого архива
 * @param $file_name: имена файлов и/или директорий, добавляемых в архив
 * @param $additional_path: имя начальной директории при создании архива, задается относительно корня системы ($DOCUMENT_ROOT.$SUB_FOLDER). Значение по умолчанию: пустая строка
 * @param array $exclude: пути к директориям, которые не будут добавлены в архив, относительно папки с netcat. Значение по умолчанию: NULL
 *  В случае использования системного tar создается список аргументов --exclude, содержащих список исключаемых директорий.
 *  В случае использования класса Archive_Tar список игнорируемых директорий устанавливается методом setIgnoreRegexp().
 *
 * @return (bool):
 *  true в случае удачного создания архива,
 *  false в случае ошибки
 */
function nc_tgz_create($archive_name, $file_name, $additional_path = '', array $exclude = NULL) {
    global $DOCUMENT_ROOT, $SUB_FOLDER;

    @set_time_limit(0);

    $path = $DOCUMENT_ROOT.$SUB_FOLDER.$additional_path;
    if (SYSTEM_TAR) {
        $exclude_cmd = '';
        if ($exclude) {
            $exclude_array = array();
            foreach($exclude as $item) {
                $exclude_array[] = "--exclude='./$item'";
            }
            $exclude_cmd = implode(' ', $exclude_array);
        }
        exec("cd $path; tar -zcf '$archive_name' $exclude_cmd $file_name 2>&1", $output, $err_code);
        if ($err_code) {
            trigger_error("$output[0]", E_USER_WARNING);
            return false;
        }
        return true;
    } else {
        $tar_object = new Archive_Tar($archive_name, "gz");
        $tar_object->setErrorHandling(PEAR_ERROR_PRINT);

        if ($exclude) {
            $exclude_regexp_parts = array();
            foreach($exclude as $item) {
                $exclude_regexp_parts[] = preg_quote($item, '#');
            }
            $exclude_regexp = '#^/(?:' . join('|', $exclude_regexp_parts) . ')$#';
            $tar_object->setIgnoreRegexp($exclude_regexp);
        }

        chdir($path);

        ob_start();
        $file_name_array = explode(' ', $file_name);
        $res = $tar_object->create($file_name_array);
        if (!$res) ob_end_flush();
        else ob_end_clean();

        return $res;

    }
}

