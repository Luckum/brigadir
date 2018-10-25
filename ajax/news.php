<?php

session_start();

error_reporting(E_ALL^E_NOTICE);
ini_set('display_errors', 1);

require_once 'common.php';

$action = $_GET['action'];

switch ($action) {
    case "add_news":
        $start = $_POST['start'];
        $content = "";
        $items = db_simple(db_arr, 
            "SELECT SQL_CALC_FOUND_ROWS a.`Message_ID`, a.`User_ID`, a.`IP`, a.`UserAgent`, a.`LastUser_ID`, a.`LastIP`, a.`LastUserAgent`, a.`Priority`, a.`Parent_Message_ID`, a.`ncTitle`, a.`ncKeywords`, a.`ncDescription`, a.`ncSMO_Title`, a.`ncSMO_Description`, a.`ncSMO_Image`, sub.`Subdivision_ID`, CONCAT('', sub.`Hidden_URL`) AS `Hidden_URL`, cc.`Sub_Class_ID`, cc.`EnglishName`, a.`Checked`, a.`Created`, a.`Keyword`, a.`LastUpdated` + 0 AS LastUpdated, a.Header, DATE_FORMAT(a.`Date`,'%Y-%m-%d') as `Date`, DATE_FORMAT(a.`Date`,'%Y') as `Date_year`, DATE_FORMAT(a.`Date`,'%m') as `Date_month`, DATE_FORMAT(a.`Date`,'%d') as `Date_day`, DATE_FORMAT(a.`Date`,'%H') as `Date_hours`, DATE_FORMAT(a.`Date`,'%i') as `Date_minutes`, DATE_FORMAT(a.`Date`,'%s') as `Date_seconds`, a.Short, a.TextContent, a.Original_ID, a.Letter_ID
                FROM (`Message181` AS a )  LEFT JOIN `Subdivision` AS sub ON sub.`Subdivision_ID` = a.`Subdivision_ID`
                LEFT JOIN `Sub_Class` AS cc ON cc.`Sub_Class_ID` = a.`Sub_Class_ID`  
                WHERE 1  AND a.`Parent_Message_ID` = '0'  AND sub.`Catalogue_ID` = '1'  AND a.`Subdivision_ID` = '100'  AND a.`Sub_Class_ID` = '44'  AND a.`Checked` = 1  ORDER BY Date DESC LIMIT $start,10");
        
        if ($items) {
            foreach ($items as $item) {
                $fullDateLink = "/news/" . $item['Date_year'] . "/" . $item['Date_month'] . "/" . $item['Date_day'] . "/news_" . $item['Message_ID'] . ".html";
                
                $content .= '<div class="newsbox short-news">';
                $content .= '<p class="date all-news-date">' . $item['Date_day'] . '.' . $item['Date_month'] . '.' . $item['Date_year'] . '</p>';
                $content .= '<div class="context">';
                $content .= '<h3><a href="' . $fullDateLink . '">' . convertText($item['Header']) . '</a></h3>';
                $content .= convertText($item['Short']);
                $content .= '</div>';
            }
        }
        
        echo $content;
    break;
}

function convertText($text)
{
    return mb_convert_encoding($text, "utf8", "CP1251");
} 