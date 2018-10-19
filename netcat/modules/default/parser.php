<?

$parser_data = array();

class Parser {
    var $odd_even = array('odd', 'even');
	var $data = array();
	
    function parse($tname, $data) {
		global $parser_data;
        $blocks = array();
		
		// Если шаблон передан как аргумент функции
		if (strpos($template, '{') === false) {
            $template = $this->get_template($tname);
		} else {
		    $template = $tname;
		}
        
		$parser_data = $data;
		
		if (strpos($template, '{if ') !== false) {
			$m = preg_replace_callback ("'{if\s([^\{\}]+)\}(.*?)\{endif\}'si", 'callback_replacer', $template);
			//print_r($m);
			//die();
		}
		
        while ($block = $this->get_pair($template)) {
            $blocks[] = $block;
        }

        // Обработка каждого повторяемого блока по отдельности
        foreach ($blocks as $block) {
            if (!isset($data[$block['name']])) {
				if ($_GET['debugparser']) {
				    print_r($data);
				}
                die('Отсутствуют данные для секции '.$block['name'].' шаблона '.$tname);
            }

            $block_data = $data[$block['name']];
            $block_code = $block['code'];
            
            $block_parsed = '';
			$i = 1;
            foreach ($block_data as $item) {
                $block_parsed .= $this->parse_tpl($block_code, $item, $i);
				$i++;
            }
            
            $template = str_replace('{block_'.$block['name'].'}', $block_parsed, $template);
        }
		
		/* Блок обработки форм */
		preg_match_all("'\{form_(.*?)\}'", $template, $forms);
		if (isset($forms[1])) {
			$forms = $forms[1];
			foreach ($forms as $name) {
				$form_data['fields'] = pre_build_form($name);
				$data['form_'.$name] = $this->parse('views/'.'form_'.$name.'.php', $form_data);
			}
		}
		/*/Блок обработки форм */
		
		unset($parser_data);
        return $this->parse_tpl($template, $data); 
    }
    

    function get_pair(&$template) {
        $pair = array();
    
        $e1 = strpos($template, '{/');
        if ($e1 === false) {
            return false;
        }
        $e2 = strpos($template, '}', $e1);
        $ent = substr($template, $e1+2, $e2 - $e1 - 2);
        $b1 = strpos($template, '{'.$ent.'}');
        $b2 = $b1 + strlen('{'.$ent.'}');
        
        $l = strlen('{'.$ent.'}');
        if ($b1 > $e1) {
            die('Template parser error;');
        }
        
        $pair['name'] = $ent;
        $pair['code'] = substr($template, $b2, $e1 - $b2);
        $template = substr($template, 0, $b1).'{block_'.$ent.'}'.substr($template, $e2 + 1);
                
        return $pair;
    }
    
    
    function parse_tpl($template, $data, $i = 0) {
        foreach ($data as $key=>$value) {
            $search[] = '{'.$key.'}';
            $replacement[] = $value;
        }
		// Специальные коды
		$search[] = '{odd|even}';
		$replacement[] = $this->odd_even[$i % 2];
		
        return str_replace($search, $replacement, $template);
    }
    
    function get_template($tname) {
        ob_start();
        require($tname);
        $template = ob_get_contents();
        ob_end_clean();
        return $template;
    }
}

function callback_replacer($matches) {
	global $parser_data;

	$s = $matches[2];
	$pos = strpos($s, '{else}');
	if ($pos !== false) {
		if ($parser_data[$matches[1]]) {
			return substr($s, 0, $pos);
		} else {	
			return substr($s, $pos + 6, strlen($s) - $pos - 5);
		}
	} else {
		if ($parser_data[$matches[1]]) {
			return $s;
		} else {
			return '';
		}
	}
}


?>