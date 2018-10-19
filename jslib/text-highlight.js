/*
	Text highlighter.
	Revision from 9 May 2007

	* Copyright Stas Davydov & Outcorp (c) 2007
		http://davidovsv.narod.ru/
		http://outcorp-ru.blogspot.com/

	* License: Feel free to use and modify this code as you wish, but don't forget to keep
	information from this comment in your code.

	* Description: Highlights text in any page element.
	If a whole text is not found, highlights text parts 
	splitted by spaces and punctuation.

	Text is highlighted with span element with class "found": 
	<span class="found">found text</span> 

	* Usage:

-------= fragment of HTML page =---------

<p id="test">Leningrad. Made in jopa</p>

<script type="text/javascript">
	mark(document.getElementById("test"), "jopa in", false, 2);
</script>

-------= fragment of HTML page =---------

	* Example: you can find example of usage on http://fast.blogslov.ru/

	* Compatibility: tested on Opera 9.10, FireFox 2.0.0.2, Internet Explorer 6.0

	* Parameters:
		el		-	HTML DOM element for text highlighting
		text	-	Text to highlight
		rec		-	Recursion flag, 
						set to "true" to don't highlight text parts,
						set to "false" to highlight text parts
		minLen  -	Minimum length of the highlighted text

	* Returns: 
		"true" if text was highlighted
		"false" if text wasn't highlighted

*/

	// Use regexp: /[\s!@#$%^&*()_\-+={}\[\];:\"\'\`~\.\,]/gi

	function textHighlight(el, text, rec, minLen) {
		var replaced = false;
		for(var child = el.firstChild; child != null; child = child.nextSibling) {
			if (child.nodeType == 3) {	// TEXT_NODE
				var idx = -1;
				while ((idx = child.nodeValue.toLowerCase().indexOf(
					text.toLowerCase(), idx + 1)) != -1) {
					if (idx > 0) {
						if (child.nodeValue.substr(idx-1, 1).match(/[^\s!@#$%^&*()_\-+={}\[\];:\"\'\`~\.\,]/gi))
							continue;
						var prefix = child.nodeValue.substr(0, idx);
						child.parentNode.insertBefore(document.createTextNode(prefix), child);
					}
					var found = document.createElement("span");
					found.setAttribute("class", "textHighlight");
					found.setAttribute("className", "textHighlight");
					found.appendChild(document.createTextNode(child.nodeValue.substr(idx, text.length)));
					var suffix = document.createTextNode(child.nodeValue.substr(idx + text.length));
					child.parentNode.insertBefore(found, child);
					child.parentNode.insertBefore(suffix, child);
					child.parentNode.removeChild(child);
					child = found;
					var replaced = true;
					break;
			   	}
			}
		}
		if (! rec && ! replaced && text.match(/[\s!@#$%^&*()_\-+={}\[\];:\"\'\`~\.\,]/gi)) {
			var words = text.replace(/[\s!@#$%^&*()_\-+={}\[\];:\"\'\`~\.\,]/gi, " ").split(" ");
			for(var i = 0; i < words.length; i++)
				if (minLen ? words[i].length >= minLen : words[i] != "")
					replaced |= mark(el, words[i], true, minLen);
		}
		return replaced;
	}
