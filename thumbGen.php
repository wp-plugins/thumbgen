<?php
/*
Plugin Name: thumbGen
Plugin URI: http://www.sebastianbarria.com/thumbgen/
Description: This plugin create a function called thumbGen() that allow to show any image in the specified size. It saves all generated thumbs in a cache directory under uploads.
Author: Sebastián Barría
Version: 1.0
Author URI: http://www.sebastianbarria.com/
*/

function thumbGen($img,$ancho,$alto,$recortar,$centrado,$grayscale){
	$ruta="wp-content/uploads/cache/";
	if(!file_exists($ruta)){ mkdir($ruta); }
	$dominio="http://".$_SERVER['HTTP_HOST']."/";
	$archivo=split("/",$img);
	$nombreArchivo=$archivo[count($archivo)-1];
	$ext=split("\.",$nombreArchivo);
	$extensionImagen=$ext[count($ext)-1];
	$nombreImagen=substr($nombreArchivo,0,strlen($nombreArchivo)-strlen($extensionImagen)-1);
	$archivoCache=$ruta.$nombreImagen."_".$ancho."_".$alto."_".$recortar."_".$centrado."_".$grayscale.".".$extensionImagen;
	$archivoCacheGS=$ruta.$nombreImagen."_".$ancho."_".$alto."_".$recortar."_".$centrado."_1.".$extensionImagen;
	if(substr($img,0,1)=="/"){
		$imgTraducida=substr($img,1);
	}
	else if(substr($img,0,7)=="http://"){
		$imgTraducida=substr($img,strlen($dominio));
	}
	else{
		$imgTraducida="";
	}
	
	if(!is_readable($archivoCache)){
		if(is_readable($imgTraducida)){ $image = open_image($imgTraducida); }
		else{ $image = open_image("wp-content/uploads/sin_imagen.jpg"); }
		
		$x=imagesx($image);
		$y=imagesy($image);
		$offsetx=0;
		$offsety=0;
		$px=$x;
		$py=$y;
		
		if(!$ancho){ $porcentaje=$alto*100/$y; $ancho=round($porcentaje*$x/100); }
		if(!$alto){ $porcentaje=$ancho*100/$x; $alto=round($porcentaje*$y/100); }
		if(!$ancho and !$alto){ $ancho=$x; $alto=$y; }
			
		$proporcionSolicitada=$ancho/$alto;
		$proporcionOriginal=$x/$y;
		if($recortar){
			if($proporcionSolicitada>$proporcionOriginal){
				$px=$x;
				$porcentaje=$ancho*100/$x;
				$py=round($alto/$porcentaje*100);
			}
			else if($proporcionSolicitada==$proporcionOriginal){
				$px=$x;
				$py=$y;
			}
			else{
				$py=$y;
				$porcentaje=$alto*100/$y;
				$px=round($ancho/$porcentaje*100);
			}
				
			if($centrado){
				$offsetx=round(($x-$px)/2);
				$offsety=round(($y-$py)/2);
			}
		}
		
		//generar imagen normal
		$nuevaImagen = imagecreatetruecolor($ancho, $alto);
		$white = imagecolorallocate($nuevaImagen, 255, 255, 255);
		imagefill($nuevaImagen,0,0,$white);
		imagecopyresampled($nuevaImagen, $image, 0, 0, $offsetx, $offsety, $ancho, $alto, $px, $py);
		imagejpeg($nuevaImagen,$archivoCache,90);
		if($grayscale){
			imagefilter($nuevaImagen,IMG_FILTER_GRAYSCALE);
			imagejpeg($nuevaImagen,$archivoCacheGS,90);
		}
		imagedestroy($nuevaImagen);
	}
	
	if($grayscale){ $archivoCache=$archivoCacheGS; }	
	echo "/".$archivoCache;
}
function open_image ($file) {
        $im = @imagecreatefromjpeg($file);
        if ($im !== false) { return $im; }
        $im = @imagecreatefromgif($file);
        if ($im !== false) { return $im; }
        $im = @imagecreatefrompng($file);
        if ($im !== false) { return $im; }
        return false;
}
?>