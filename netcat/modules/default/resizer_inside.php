<?
// Добавляем возможность обрезания фото снизу

// Этот вариант будет не выдавать файл (readfile), а возвращать имя конечного файла после ресайза

// Добавлен пропорциональный ресайз при указании только одного размера

// Добавлено создание чб варианта

$quality = 100;

/*
function resize($img_filename,$w,$h,$mode,$new_file)
$img_filename	имя файла модифицируемого изображения
$w	новая ширина
$h	новая высота
$mode	режим(1 или 2)
1 - вписывание с заполнением всего пространства результирующей фотки (возможны отрезанные края)
2 - вписывание чтобы влезло все исходное фото (возможны белые поля)

$new_file	имя файла для сохранения
$wb создание чб варианта
*/

function resize($img_filename, $w, $h, $mode, $new_file, $wb) {
	global $quality;
        $quality = 100;
	if (!file_exists($img_filename)) {
		$img_filename = $_SERVER['DOCUMENT_ROOT'].$img_filename;
	} 
	if (!file_exists($img_filename)) {
		return;
	}
	$type_image = getimagesize($img_filename);
	switch ($type_image[2]) {
		case 1:
		$img = @imagecreatefromgif($img_filename);
		break;
		case 2:
		$img = @imagecreatefromjpeg($img_filename);
		break;
		case 3:
		$img = @imagecreatefrompng($img_filename);
		break;
		default:
		$img = FALSE;
		break;
	}
	$old_w = $type_image[0];
	$old_h = $type_image[1];
	
	// Если указана только ширина или высота
	if ($w == 0 or $h == 0) {
		if ($w) {
			$k = $old_w / $w;
			$h = intval($old_h / $k);
		} else {
			$k = $old_h / $h;
			$w = intval($old_w / $k);
		}
		$new_image = imagecreatetruecolor($w, $h);
		imagecopyresampled ($new_image, $img, 0, 0, 0, 0, $w, $h, $old_w, $old_h);
		imagejpeg($new_image, $new_file, $quality);
	} else {
		if ($mode == 1) {
			$scope_1 = $old_w/$w;
			$scope_2 = $old_h/$h;
			$scope = min($scope_1, $scope_2);
			$new_image = imagecreatetruecolor($w, $h);
			$color = imagecolorallocate ($new_image, 255, 255, 255);
			imagefill ($new_image, 0, 0, $color);
			if ($scope == $scope_2) {
				imagecopyresampled ($new_image, $img, 0, 0, (int)((($old_w/$scope-$w)/2) * $scope), 0, $w, $h, $w * $scope, $h * $scope);
				imagejpeg($new_image, $new_file, $quality);
			} else {
				imagecopyresampled ($new_image, $img, 0, 0, 0, (int)((($old_h/$scope-$h)/2) * $scope), $w, $h, $w * $scope, $h * $scope);
				imagejpeg($new_image, $new_file, $quality);
			}
		} elseif($mode == 2) {
			$scope_1 = $old_w/$w;
			$scope_2 = $old_h/$h;
			$scope = max($scope_1, $scope_2);
			$new_image = imagecreatetruecolor($w, $h);
			$color = imagecolorallocate ($new_image, 255, 255, 255);
			imagefill ($new_image, 0, 0, $color);
			if ($scope == $scope_1) {
				imagecopyresampled ($new_image, $img, 0, (int)(($h-($old_h/$scope))/2), 0, 0, $w, (int)($old_h/$scope), $old_w, $old_h);
				imagejpeg($new_image, $new_file, $quality);
			} else {
				imagecopyresampled ($new_image, $img, (int)(($w-($old_w/$scope))/2), 0, 0, 0, (int)($old_w/$scope), $h, $old_w, $old_h);
				imagejpeg($new_image, $new_file, $quality);
			}
		}
	}
	
	if ($wb) {
	
	}
}

function resize_function($src, $w, $h, $mode, $wb=false) {
    // В сложных случаях будем передавать $mode как массив
	if (is_array($mode)) {
		// Обрезаемая стороны
		$cropside = $mode['cropside'];
		$mode = $mode['mode'];
	}
	

    $target = str_replace('/netcat_files/', '', $src);
	$target = str_replace('/', '_', $target);
	$t = explode('.', $target);
	// Расширение файла
	$ext = $t[count($t)-1];
	unset($t[count($t)-1]);
	$t = implode('.', $t);
	
	$target = $_SERVER['DOCUMENT_ROOT'].'/netcat_files/resized/'.$t."_{$w}_{$h}_{$mode}.{$ext}";
	$short_target = '/netcat_files/resized/'.$t."_{$w}_{$h}_{$mode}.{$ext}";
	
	if (!file_exists($target)) {
		resize($_SERVER['DOCUMENT_ROOT'].$src, $w, $h, $mode, $target, $wb);
		if ($wb) {
			$target_wb = $target.'_wb';
			image2GrayColor($target, $target_wb);
		}
	} else {
		if ($wb) {
			$target_wb = $target.'_wb';
			if (!file_exists($target_wb)) {
				image2GrayColor( $target, $target_wb);
			}
		}
	}
	
	// header("Content-type: image/jpeg");
	if ($wb) {
		return $target_wb;
	} else {
		return $short_target;
	}
}

function image2GrayColor( $img_path, $output_path ){		
	global $quality;
			
	$type_img = imageType( $img_path );
	$gd 	  = gd_info();							
	
	if( $type_img == 3 AND $gd['PNG Support'] == 1 ){ 					
		
		$img_png = imagecreatefromPNG( $img_path );
		imagesavealpha( $img_png, TRUE );
		
		if( $img_png AND imagefilter( $img_png, IMG_FILTER_GRAYSCALE )) {			
			@unlink( $output_path );				
			imagepng( $img_png, $output_path );
			return showImages( $img_path, $output_path );	
		}
		else{
			return 'Error: PNG Support.';				
		} 
		   
		imagedestroy( $img_png );
	}
	elseif( $type_img == 2 AND $gd['JPG Support'] == 1 ) { 
		
		$img_jpg 	 = imagecreatefromJPEG( $img_path );
		if( $img_jpg AND imagefilter( $img_jpg, IMG_FILTER_GRAYSCALE )) {			
			@unlink( $output_path );				
			imagejpeg($img_jpg, $output_path, $quality);
			return;	
		}
		else{
			return 'Error: JPG Support.';				
		}
		 
		imagedestroy( $img_jpg );
	}
	elseif( $type_img == 1 AND $gd['GIF Create Support'] == 1  ) { 
		
		$img_gif 	 = imagecreatefromGIF( $img_path );
		if ( !$color_total = imagecolorstotal( $img_gif )) {
			$color_total = 256;		          
		}   
		imagetruecolortopalette( $img_gif, FALSE, $color_total );    
		
		for( $c = 0; $c < $color_total; $c++ ) {    
			 $col = imagecolorsforindex( $img_gif, $c );		        
			 $i   = ( $col['red']+$col['green']+$col['blue'] )/3;
			 imagecolorset( $img_gif, $c, $i, $i, $i );
		}		    
		@unlink( $output_path );
		
		if( imagegif( $img_gif, $output_path ) ){
			return showImages( $img_path, $output_path );					
		}
		else{
			return 'Error: GIF Create.';				
		}
		imagedestroy( $img_gif );			
	}
	else{
		return 'Error: This format is not supported.';
	}							
}

function imageType( $img_path ){
	if( function_exists( 'exif_imagetype' ) ){
		return exif_imagetype( $img_path );		
	}
	else{
		$arr_from_img = getimagesize ( $img_path ); 
		return $arr_from_img['2'];  			
	}			
}

?>