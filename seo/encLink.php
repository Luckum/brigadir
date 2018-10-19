<?php
function encLink($text, $noblank = true, $skip=false) {
    if($skip===false) $skip=-1;
    #$text = str_replace('"', "'", $text);
    $expr = '/<a ([\s\S]*?)<\/a>/i';
    preg_match_all($expr,$text,$links);
    foreach($links[0] as $key=>$val)
    {
        if($key!=$skip)
        {
            preg_match_all('/href=[\'|"]([\s\S]*?)[\'|"]/i',$val,$href);
            //if($_SERVER['REQUEST_URI'] == '/') $rt=true; else $rt=false; //убрал сбрасывание для относительных урлов, в нашей ситуации немного мешает.
            if(!isset($href[1][0]) || !strstr($href[1][0],'http://')) continue;
            preg_match_all('/style=[\'|"]([\s\S]*?)[\'|"]/i',$val,$style);
            preg_match_all('/on[c|C]lick="([\s\S]*?)"/i',$val,$onClick);
            preg_match_all('/class=[\'|"]([\s\S]*?)[\'|"]/i',$val,$class);
            preg_match_all('/title=[\'|"]([\s\S]*?)[\'|"]/i',$val,$title); //Добавил сохранение тайтла линка
            preg_match_all("/<[^>]+>(.*)<\/[^>]+>/U",$val,$html);
            $text = str_replace($val, "<a href='#' ".($noblank ? "noblank='1'" : "noblank='0'") .(isset($onClick[1][0]) ? " onClick=\"".str_replace('"', "'", $onClick[1][0])."\"" : NULL).(isset($style[1][0]) ? " style='".$style[1][0]."'" : NULL).(isset($title[1][0]) ? " title='".$title[1][0]."'" : NULL)." encodedLink='".base64_encode($href[1][0])."' class='linkreps".(isset($class[1][0]) ? ' '.$class[1][0] : NULL)."'>".$html[1][0]."</a>", $text);
            //добавил вариант с возможностью указывания target = _blank
        }
    }
    return $text;
}
?>