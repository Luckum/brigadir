$(document).ready(function(){
    $(".linkreps").each(function(){ // для всех элементов с классом linkreps
        var href = $(this).attr('href');
        var onclick = '';
        var blank = '';
        var style = '';
        var title = '';
        if($(this).attr("encodedLink"))
        {
            href = $.base64.decode($(this).attr("encodedLink"));
            blank = ' target="_blank"';
            if($(this).attr('noblank') == 1) blank = '';
            if($(this).attr('onClick')) onclick = ' onClick="'+$(this).attr('onClick')+'"';
            if($(this).attr('style')) style = ' style="'+$(this).attr('style')+'"';
            if($(this).attr('title')) title = ' title="'+$(this).attr('title')+'"';
        }
        // заменяем текущий элемент на ссылку с нужной нам ссылкой, с классами, которые были у этого элемента
        // и внутренним содержимым
        $(this).replaceWith('<a href="'+href+'" class="'+$(this).attr("class")+'"'+title+blank+onclick+'>'+$(this).html()+'</a>');
    });
});