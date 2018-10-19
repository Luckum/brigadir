$(document).ready(function(){
    $(".linkreps").each(function(){ // РґР»СЏ РІСЃРµС… СЌР»РµРјРµРЅС‚РѕРІ СЃ РєР»Р°СЃСЃРѕРј linkreps
        var href = $(this).attr('href');
        var onclick = '';
        var blank = '';
        var style = '';
        var title = '';
        if($(this).attr("encodedLink")) {
            href = $.base64.decode($(this).attr("encodedLink"));
		}
		
		blank = ' target="_blank"';
		if($(this).attr('noblank') == 1) blank = '';
		if($(this).attr('onClick')) onclick = ' onClick="'+$(this).attr('onClick')+'"';
		
		var s = $(this).attr('style');
		if(s != '') {
			style = ' style="'+$(this).attr('style')+'"';
		}
		if($(this).attr('title')) title = ' title="'+$(this).attr('title')+'"';
        // Р·Р°РјРµРЅСЏРµРј С‚РµРєСѓС‰РёР№ СЌР»РµРјРµРЅС‚ РЅР° СЃСЃС‹Р»РєСѓ СЃ РЅСѓР¶РЅРѕР№ РЅР°Рј СЃСЃС‹Р»РєРѕР№, СЃ РєР»Р°СЃСЃР°РјРё, РєРѕС‚РѕСЂС‹Рµ Р±С‹Р»Рё Сѓ СЌС‚РѕРіРѕ СЌР»РµРјРµРЅС‚Р°
        // Рё РІРЅСѓС‚СЂРµРЅРЅРёРј СЃРѕРґРµСЂР¶РёРјС‹Рј
        $(this).replaceWith('<a href="'+href+'" class="'+$(this).attr("class")+'"'+title+blank+onclick+style+'>'+$(this).html()+'</a>');
    });
});