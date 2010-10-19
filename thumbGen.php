<?php
/*
Plugin Name: thumbGen
Plugin URI: http://www.sebastianbarria.com/thumbgen/
Description: This plugin creates a function named thumbGen() that allows to show any image in the specified size (plus many other things). It saves every generated thumbs in a cache directory, so it will not re-generate the thumb if it already exists. ATTENTION: If you're upgrading from older version it will probable need you to do some fixes in the code. Please refer to the documentation at http://www.sebastianbarria.com/thumbgen/
Author: Sebastián Barría
Version: 2.5
Author URI: http://www.sebastianbarria.com/
*/

function thumbGen($img="",$width=0,$height=0,$arguments=""){
	$allowedArgs=array(
		"filename"=>"", //if you want to specify a new filename (in case of conflict with similar filenames)
		"md5"=>"1", //1,0 if you don't want the generated file to have an encoded name set this to 0
		"force"=>"0", //1,0 force thumb creation, even if it exists (NOT RECOMENDED! - use it just for debugging)
		"crop"=>"1", //1,0
		"halign"=>"center", //left,center,right
		"valign"=>"center", //top,center,bottom
		"effect"=>"", //grayscale,sephia
		"rotate"=>"0", //0-360
		"background"=>"transparent", //background color for use when the image is rotated
		"return"=>"0", //1,0
	);
	$arguments=explode("&",$arguments);
	$args=thumbGen_setupAllowedArguments($allowedArgs,$arguments);

	$sitePath=$_SERVER['DOCUMENT_ROOT'];
	$cachePath=get_option('thumbgen_cache_files');
	$defultImage=get_option('thumbgen_default_image');
	
	$file=explode("/",$img);
	$fileName=$file[count($file)-1];
	$ext=explode(".",$fileName);
	$imageExtension=strtolower($ext[count($ext)-1]);
	if($imageExtension!="png" and $imageExtension!="gif"){ $imageExtension="jpg"; }
	$imageName=$args['filename']?$args['filename']:substr($fileName,0,strlen($fileName)-strlen($imageExtension)-1);
	$imageName=$imageName."_".$width."_".$height."_".implode("_",$allowedArgs);
	if($args['md5']==1){ $imageName=md5($imageName); }
	$fileCache=$cachePath.$imageName.".".$imageExtension;
	$fileCacheGS=$cachePath.$imageName.".".$imageExtension;
	
	if(!is_readable($sitePath.$fileCache) or $args['force']){
		$openImage=substr($img,0,1)=="/"?$sitePath.$img:$img;
		$image = thumbGen_openImage($openImage);
		if(!$image){
			if($defultImage){ $image = thumbGen_openImage($defultImage); }
			else{ $image = imagecreatetruecolor($width, $height);}
		}
		else{
			if($args['rotate']){
				if($args['background']!="transparent"){
					$bgColor=thumbGen_hexToRGB($args['background']);
					$bg = imagecolorallocatealpha($image, $bgColor[0], $bgColor[1], $bgColor[2], 127);
					$image=imagerotate($image,$args['rotate'],$bg);
				}
				else{
					$image=imagerotate($image,$args['rotate'],-1);
				}
			}
		}

		$x=@imagesx($image);
		$y=@imagesy($image);
		$offsetx=0;
		$offsety=0;
		$px=$x;
		$py=$y;
		
		if(!$width){ $percentage=$height*100/$y; $width=round($percentage*$x/100); }
		if(!$height){ $percentage=$width*100/$x; $height=round($percentage*$y/100); }
		if(!$width and !$height){ $width=$x; $height=$y; }
			
		$newProportion=$width/$height;
		$originalProportion=$x/$y;
		if($args['crop']){
			if($newProportion>$originalProportion){
				$px=$x;
				$percentage=$width*100/$x;
				$py=round($height/$percentage*100);
			}
			else if($newProportion==$originalProportion){
				$px=$x;
				$py=$y;
			}
			else{
				$py=$y;
				$percentage=$height*100/$y;
				$px=round($width/$percentage*100);
			}
			
			//alignment	
			if($args['halign']=="left"){ $offsetx=0; }
			else if($args['halign']=="right"){ $offsetx=round(($x-$px)); }
			else{ $offsetx=round(($x-$px)/2); }
			
			if($args['valign']=="top"){ $offsety=0; }
			else if($args['valign']=="bottom"){ $offsety=round(($y-$py)); }
			else{ $offsety=round(($y-$py)/2); }
		}
		
		//generate image
		$newImage = imagecreatetruecolor($width, $height);
		$alpha=$imageExtension!="jpg"?127:0;
		if(!$bgColor){ $bgColor=thumbGen_hexToRGB($args['background']); }
		$bg = imagecolorallocatealpha($newImage, $bgColor[0], $bgColor[1], $bgColor[2], $alpha);
		imagefill($newImage,0,0,$bg);
		if($args['background']=="transparent" or !$args['rotate']){
			if($imageExtension=="png"){
				imagealphablending($newImage, false);
				imagesavealpha($newImage, true);
			}
			if($imageExtension=="gif"){
				imagecolortransparent($newImage,$bg);
			}
		}
		imagecopyresampled($newImage, $image, 0, 0, $offsetx, $offsety, $width, $height, $px, $py);
		if($imageExtension=="png"){ imagepng($newImage,$sitePath.$fileCache,7); }
		else if($imageExtension=="gif"){ imagegif($newImage,$sitePath.$fileCache); }
		else{ imagejpeg($newImage,$sitePath.$fileCache,90); }
		if($args['effect']=="grayscale" or $args['effect']=="sephia"){
			imagefilter($newImage,IMG_FILTER_GRAYSCALE);
			if($args['effect']=="sephia"){ imagefilter($newImage,IMG_FILTER_COLORIZE,100,50,0); }
			if($imageExtension=="png"){ imagepng($newImage,$sitePath.$fileCacheGS,7); }
			else if($imageExtension=="gif"){ imagegif($newImage,$sitePath.$fileCacheGS); }
			else{ imagejpeg($newImage,$sitePath.$fileCacheGS,90); }
		}
		imagedestroy($newImage);
	}
	
	if($grayscale){ $fileCache=$fileCacheGS; }	

	if(!$args['return']){ echo $fileCache; }
	else{ return $fileCache; }
}
function thumbGen_hexToRGB($color){
	if ($color[0] == '#'){ $color = substr($color, 1); }
	
	if (strlen($color) == 6){ list($r, $g, $b) = array($color[0].$color[1],$color[2].$color[3],$color[4].$color[5]); }
	else if (strlen($color) == 3) { list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]); }
	else { return false; }	
	
	$r = hexdec($r); $g = hexdec($g); $b = hexdec($b);
	return array($r, $g, $b);
}
function thumbGen_setupAllowedArguments($argsPermitidos,$argumentos){
	$tempArgs=array();
	foreach ($argsPermitidos as $k=>$v){
		$tempArgs[$k]=$v;
	}
	foreach ($argumentos as $arg){
		$arg=explode("=",$arg);
		if(array_key_exists($arg[0],$argsPermitidos)){
			$tempArgs[$arg[0]]=$arg[1];
		}
	}
	return $tempArgs;
}
function thumbGen_openImage ($file) {
        $im = @imagecreatefromjpeg($file);
        if ($im !== false) { return $im; }
        $im = @imagecreatefromgif($file);
        if ($im !== false) { return $im; }
        $im = @imagecreatefrompng($file);
        if ($im !== false) { return $im; }
        return false;
}
?>
<?php
//check variables
function myplugin_update_options($options){
	global $mypluginall;
	while (list($option, $value) = each($options)) {
		if( get_magic_quotes_gpc() ) { 
		$value = stripslashes($value);
		}
		$mypluginall[$option] =$value;
	}
	return $mypluginall;
}

//add config page to the "settings" menu
add_action('admin_menu', 'add_thumbgen_admin');
function add_thumbgen_admin() {
	global $wpdb;
	add_options_page('thumbGen', 'thumbGen', 10, 'thumbGen', 'thumbgen_options_page');
}

//set the default values on activation
function thumbgen_activation_hook() {
	$options=array(
		"thumbgen_cache_files"=>"/wp-content/thumbgen_cache/",
		"thumbgen_default_image"=>"",
	);
	foreach($options as $k=>$v){
		if(get_option($k)){ update_option($k,$v); }
		else{ add_option($k,$v,'','yes'); }
	}
}
register_activation_hook(__FILE__, 'thumbgen_activation_hook');

function thumbgen_filter_plugin_actions( $links, $file ){
	static $this_plugin;
	if ( ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);
	
	if ( $file == $this_plugin ){
		$settings_link = '<a href="options-general.php?page=thumbGen">' . __('Settings') . '</a>';
		array_unshift( $links, $settings_link ); // before other links
	}
	return $links;
}
add_filter( 'plugin_action_links', 'thumbgen_filter_plugin_actions', 10, 2 );

//options page
function thumbgen_options_page(){
?>
<div class="wrap">
	<h2>thumbGen</h2>
	<p>This plugin creates a function named thumbGen() that allows to show any image in the specified size (plus many other things). It saves every generated thumbs in a cache directory, so it will not re-generate the thumb if it already exists.</p>
	<p><strong>Note:</strong> Keep in mind that this plugin requires the GD2 library and permissions to write in the specified cache folder.</p>
	<?php
	//actualizar los datos
	if($_POST["action"] == "modify"){
		global $mypluginall;
		myplugin_update_options($_REQUEST['conf']);
		
		//actualizar variables
		$options=array("cache_files","default_image");
		foreach($options as $option){
			$option_name='thumbgen_'.$option;
			$newvalue=$mypluginall[$option];
			if(get_option($option_name)){ update_option($option_name,$newvalue); }
			else{ add_option($option_name,$newvalue,'','yes'); }
		}
	
		echo "<div class='updated'><p><strong>thumbGen options have been updated successfully</strong></p></div>";
		if(get_option('thumbgen_cache_files')){
			if(substr(get_option('thumbgen_cache_files'),0,1)=="/"){
				if(is_readable($_SERVER['DOCUMENT_ROOT'].get_option('thumbgen_cache_files'))){
					if($_POST['clear_cache']){
						$dir=$_SERVER['DOCUMENT_ROOT'].get_option('thumbgen_cache_files');
						$clearCache=opendir($dir);
						while ($archivo = readdir($clearCache)){
							if($archivo!=".." && $archivo!="."){ unlink($dir.$archivo); }
						}
						closedir($clearCache);
						echo "<div class='updated'><p><strong>The cache folder have been cleared</strong></p></div>";
					}
					
					if(is_writable($_SERVER['DOCUMENT_ROOT'].get_option('thumbgen_cache_files'))){
						echo "<div class='updated'><p><strong>The specified folder seems to be fine. You have configured thumbGen!</strong></p></div>";
					}
					else{
						echo "<div class='error'><p><strong>The specified folder is not writable!. Please check the folder permissions or thumbGen will not work</strong></p></div>";
					}
				}
				else{
					if($_POST['create_folder']){
						if(@mkdir($_SERVER['DOCUMENT_ROOT'].get_option('thumbgen_cache_files'),0777)){
							echo "<div class='updated'><p><strong>The specified folder doesn't exists</strong>. But don't worry... I've already created it ;)</p></div>";
						}
						else{
							echo "<div class='error'><p><strong>The specified folder doesn't exists</strong> and I was not able to create it :(</p></div>";
						}
					}
					else{
						echo "<div class='error'><p><strong>The specified folder doesn't exist. Please check your settings or thumbGen will not work</strong></p></div>";
					}
				}
			}
			else{
				echo "<div class='error'><p><strong>The specified folder is not valid. Remember to use the '/' at the beginning</p></div>";
			}
		}
		else{
			echo "<div class='error'><p><strong>You haven't specified the cache folder. If this is not configured properly, thumbGen will not work!</p></div>";
		}
	}
	?>
	<form method="post">
		<input type="hidden" name="action" value="modify">
		<table class="form-table" cellspacing="2" cellpadding="5" width="100%">
			<tr valign="top">
				<th scope="row">Path to store the cache files:</th>
				<td>
					<input type="text" class="code" size="50" value="<?php echo get_option('thumbgen_cache_files'); ?>" name="conf[cache_files]"> <span class="description">(default: "/wp-content/thumbgen_cache/")</span><br />
					<label for="create_folder"><input type="checkbox" name="create_folder" id="create_folder" value="1" />Try to create folder if it doesn't exists</label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Path to the default image:</th>
				<td>
					<input type="text" class="code" size="50" value="<?php echo get_option('thumbgen_default_image'); ?>" name="conf[default_image]"><br />
					<small class="description">Use an absolute URL like /wp-content/themes/mytheme/no-image.jpg or http://www.mysite.com/wp-content/themes/mytheme/no-image.jpg<br />This one will be used if the requested image can't be found.<br />If you don't specify this image, the generated thumbnail will be a white image.</small>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Clear cache folder:</th>
				<td>
					<label for="clear_cache"><input name="clear_cache" type="checkbox" id="clear_cache" value="1"  /></label>
				</td>
			</tr>
		</table>
		<p class="submit"><input type="submit" class="button-primary" value="<?php _e("Save Changes"); ?>"></p>
	</form>
	<fieldset>
		<legend>Donations</legend>
		<p>Your donations will be allways appreciated!</p>
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="hosted_button_id" value="A799JB6J57938">
			<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			<img alt="" border="0" src="https://www.paypal.com/es_XC/i/scr/pixel.gif" width="1" height="1">
		</form>
	</fieldset>
</div>
<?php } ?>