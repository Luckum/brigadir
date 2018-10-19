<?

$items = db_simple(db_arr, "
			   
SELECT a.Subdivision_ID, b.cms_page_id, b.serial FROM Message1 a
 inner join `brigadir`.`nw_pages_label` b on (a.Original_ID = b.cms_page_id)
where Module = 'menu' and label = 'right'
				   
");

foreach($items as $item) {
	$data =  unserialize($item['serial']);
	$items2 = db_simple(db_arr, "
		select a.name, a.setMenuProviderID, b.name as bname, b.address from `brigadir`.nw_menu a inner join `brigadir`.nw_pages b on (a.setMenuProviderID = b.id)
		where a.parent_id = {$data['menu_id']}
		order by a.place
	");
	$s = "<ul>\n";
	foreach($items2 as $item2) {
		$s .= "    <li><a href=\"{$item2['address']}\">{$item2['name']}</a></li>\n";
	}
	$s .= "</ul>\n";
	
	$values = array('RightBlock' => mysql_real_escape_string($s));
	db_upd($values, 'Subdivision', "Subdivision_ID = {$item['Subdivision_ID']}");
}


echo 'Закончено';


?>