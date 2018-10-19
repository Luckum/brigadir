<?php

function testSH()
{
return 'test';	
}

function encodeExternalLinks($text)
{
	$expr ='/href="([\s\S]*?)"/i';
	preg_match_all($expr,$text,$links);
	foreach($links[1] as $key=>$val)
	{
		if(!strstr($val,$_SERVER['HTTP_HOST']) && strstr($val,'http://'))
		{
			$text = str_replace('href="'.$val.'"', 'href="#" encodedLink="'.base64_encode($val).'" class="linkrep"', $text);
		}
	}
	return $text;
}

function encLink($text, $skip=false)
{
		if($skip===false) $skip=-1;
		#$text = str_replace('"', "'", $text);
		$expr = '/<a ([\s\S]*?)<\/a>/i';
		preg_match_all($expr,$text,$links);
		foreach($links[0] as $key=>$val)
		{
			if($key!=$skip)
			{
				preg_match_all('/href=[\'|"]([\s\S]*?)[\'|"]/i',$val,$href);
				if($_SERVER['REQUEST_URI'] == '/') $rt=true; else $rt=false;
				if($rt && !strstr($href[1][0],$_SERVER['HTTP_HOST']) && !strstr($href[1][0],'http://')) continue;
				preg_match_all('/style=[\'|"]([\s\S]*?)[\'|"]/i',$val,$style);
				preg_match_all('/on[c|C]lick="([\s\S]*?)"/i',$val,$onClick);
				preg_match_all('/class=[\'|"]([\s\S]*?)[\'|"]/i',$val,$class);
				preg_match_all("/<[^>]+>(.*)<\/[^>]+>/U",$val,$html);
				$text = str_replace($val, "<a href='#' noblank='1'".($onClick[1][0] ? " onClick=\"".str_replace('"', "'", $onClick[1][0])."\"" : NULL).($style ? "style='".$style[1][0]."'" : NULL)." encodedLink='".base64_encode($href[1][0])."' class='linkreps".($class[1][0] ? ' '.$class[1][0] : NULL)."'>".$html[1][0]."</a>", $text);
			}
		}
	return $text;
}
?>