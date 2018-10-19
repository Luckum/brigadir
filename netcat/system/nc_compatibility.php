<?php

/**
 * Файл, содержащий функции совместимости для различных версий PHP 
 */

/**
 * Функция http_response_code для версий < 5.4.0
 */
if (!function_exists('http_response_code')) {

    function http_response_code($newcode = NULL)
    {
        static $code = 200;
        if ($newcode !== NULL) {
            header('X-PHP-Response-Code: ' . $newcode, true, $newcode);
            if (!headers_sent())
                $code = $newcode;
        }
        return $code;
    }

}