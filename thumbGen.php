<?php
/*
Plugin Name: thumbGen
Plugin URI: http://www.sebastianbarria.com/plugins/thumbgen/
Description: This plugin creates a function named thumbGen() that allows to show any image in the specified size (plus many other things). It saves every generated thumbs in a cache directory, so it will not re-generate the thumb if it already exists.
Author: Sebastián Barría
Version: 2.7.1
Author URI: http://www.sebastianbarria.com/
*/

function thumbGen($img="",$width=0,$height=0,$arguments=""){
	$allowedArgs=array(
		"filename"=>"", //if you want to specify a new filename (in case of conflict with similar filenames)
		"md5"=>"1", //1,0 if you don't want the generated file to have an encoded name set this to 0
		"force"=>"0", //1,0 force thumb creation, even if it exists (NOT RECOMENDED! - use it just for debugging)
		"crop"=>"1", //1=true(auto crops the image); 0=false(don't crop the image)
		"halign"=>"center", //left,center,right
		"valign"=>"center", //top,center,bottom
		"effect"=>"", //grayscale,sephia
		"rotate"=>"0", //0-360
		"background"=>"transparent", //background color for use when the image is rotated
		"return"=>"0", //1=true(returns the image URL instead of echoing); 0=false(echoes the image URL)
		"preserveAnimation"=>"1", //force to preserve animation (all other args won't work)
		"quality"=>"7" //0-9
	);
	$arguments=explode("&",$arguments);
	$args=thumbGen_setupAllowedArguments($allowedArgs,$arguments);
	if(!is_numeric($args['quality']) or $args['quality']<0 or $args['quality']>9){ $args['quality']=7; }

	$folders=thumbgen_get_base_folders();
	
	$file=explode("/",$img);
	$fileName=$file[count($file)-1];
	$img=str_replace($img,rawurldecode($img),$img);
	$ext=explode(".",$fileName);
	$imageExtension=strtolower($ext[count($ext)-1]);
	if($imageExtension!="png" and $imageExtension!="gif"){ $imageExtension="jpg"; }
	$imageName=$args['filename']?$args['filename']:substr($fileName,0,strlen($fileName)-strlen($imageExtension)-1);
	$imageName=$imageName."_".$width."_".$height."_".str_replace("#","",implode("_",$args));
	if($args['md5']==1){ $imageName=md5($imageName); }
	$fileCache=$folders["baseFolder"].$folders["cachePath"].$imageName.".".$imageExtension;
	
	$animated=0;
	if($imageExtension=="gif" and $args['preserveAnimation']){
		$animated=thumbGen_isAnimation($img);
		if($animated){
			copy($img,$_SERVER['DOCUMENT_ROOT'].$fileCache);
		}
	}
	if(!$animated){
		if(!is_readable($folders["sitePath"].$fileCache) or $args['force']){
			if(preg_match('/^(http|ftp|https)\:\/\/' . addslashes($_SERVER['HTTP_HOST']) . '/i', $img)){
				$uploads = wp_upload_dir();
				$openImage = str_replace($uploads['baseurl'], $uploads['basedir'], $img);
			}
			else{
				$openImage = $img;
			}
			$image = thumbGen_openImage($openImage);
			if(!$image){
				if($defaultImage){
					$defaultImage=substr($defaultImage,0,1)=="/"?$folders["sitePath"].$defaultImage:$defaultImage;
					$image = thumbGen_openImage($defaultImage);
				}
				if(!$image){
					$widthTemp=$width;
					$heightTemp=$height;
					if(!$widthTemp and !$heightTemp){ $widthTemp=300; $heightTemp=300; }
					else if(!$widthTemp){ $widthTemp=$heightTemp; }
					else if(!$heightTemp){ $heightTemp=$widthTemp; }
					$image = imagecreatetruecolor($widthTemp, $heightTemp);
				}
			}
			else{
				if($args['rotate']){
					if($imageExtension=="png"){
						if($args['background']!="transparent"){
							$bgColor=thumbGen_hexToRGB($args['background']);
							$bg = imagecolorallocatealpha($image, $bgColor[0], $bgColor[1], $bgColor[2], 0);
							$image=imagerotate($image,$args['rotate'],$bg);
						}
						else{
							$bg = imagecolorallocatealpha($image, 255, 255, 255, 127);
							$image=imagerotate($image,$args['rotate'],-1);
						}
					}
					else{
						if($args['background']!="transparent"){
							$bgColor=thumbGen_hexToRGB($args['background']);
						}
						else{
							$bgColor=thumbGen_hexToRGB('#f0f');
							$bg = imagecolorallocatealpha($image,$bgColor[0],$bgColor[1],$bgColor[2],127);
							$image=imagerotate($image,$args['rotate'],$bg);
							imagecolortransparent($image,$bg);
						}
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
				
			@$newProportion=$width/$height;
			@$originalProportion=$x/$y;
					
			if($args['crop']){
				if($newProportion>$originalProportion){
					$px=$x;
					@$percentage=$width*100/$x;
					@$py=round($height/$percentage*100);
				}
				else if($newProportion==$originalProportion){
					$px=$x;
					$py=$y;
				}
				else{
					$py=$y;
					@$percentage=$height*100/$y;
					@$px=round($width/$percentage*100);
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
			@imagecopyresampled($newImage, $image, 0, 0, $offsetx, $offsety, $width, $height, $px, $py);
			if($imageExtension=="png"){ imagepng($newImage,$_SERVER['DOCUMENT_ROOT'].$fileCache,9-$args['quality']); }
			else if($imageExtension=="gif"){ imagegif($newImage,$_SERVER['DOCUMENT_ROOT'].$fileCache); }
			else{ imagejpeg($newImage,$_SERVER['DOCUMENT_ROOT'].$fileCache,$args['quality']*100); }
			if($args['effect']=="grayscale" or $args['effect']=="sephia"){
				imagefilter($newImage,IMG_FILTER_GRAYSCALE);
				if($args['effect']=="sephia"){ imagefilter($newImage,IMG_FILTER_COLORIZE,100,50,0); }
				if($imageExtension=="png"){ imagepng($newImage,$_SERVER['DOCUMENT_ROOT'].$fileCache,9-$args['quality']); }
				else if($imageExtension=="gif"){ imagegif($newImage,$_SERVER['DOCUMENT_ROOT'].$fileCache); }
				else{ imagejpeg($newImage,$_SERVER['DOCUMENT_ROOT'].$fileCache,$args['quality']*100); }
			}
			imagedestroy($newImage);
		}
	}

	if(!$args['return']){ echo $fileCache; }
	else{ return $fileCache."?originalWidth=$x&originalHeight=$y&newWidth=$width&newHeight=$height"; }
}
function thumbGen_isAnimation($filename){
	$filecontents=file_get_contents($filename);
	
	$str_loc=0;
	$count=0;
	while ($count < 2){
		$where1=strpos($filecontents,"\x00\x21\xF9\x04",$str_loc);
		if($where1 === FALSE){
			break;
		}
		else{
			$str_loc=$where1+1;
			$where2=strpos($filecontents,"\x00\x2C",$str_loc);
			if($where2 === FALSE){
				break;
			}
			else{
				if($where1+8 == $where2){
					$count++;
				}
				$str_loc=$where2+1;

			}
		}
	}
	
	if ($count>1){
		return(true);
	}
	else{
		return(false);
	}
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

//get base folders
function thumbgen_get_base_folders(){
	$folders=array();
	$folders["sitePath"]=rtrim(ABSPATH,"/");
	$folders["baseFolder"]=str_replace($_SERVER['DOCUMENT_ROOT'],"",$folders["sitePath"]);
	$folders["cachePath"]=get_option('thumbgen_cache_files');
	$folders["defaultImage"]=get_option('thumbgen_default_image');
	return $folders;
}

//add config page to the "settings" menu
add_action('admin_menu', 'add_thumbgen_admin');
function add_thumbgen_admin() {
	global $wpdb;
	add_options_page('thumbGen', 'thumbGen', 'edit_pages', 'thumbGen', 'thumbgen_options_page');
}

//set the default values on activation
function thumbgen_activation_hook() {
	$folders=thumbgen_get_base_folders();
	
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
	$folders=thumbgen_get_base_folders();
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
				$valor=get_option($option_name,"empty");
				if(!empty($valor) or $valor==""){ update_option($option_name,$newvalue); }
				else{ add_option($option_name,$newvalue,'','yes'); }
			}
		
			echo "<div class='updated'><p><strong>thumbGen options have been updated successfully</strong></p></div>";
			if(get_option('thumbgen_cache_files')){
				if(substr(get_option('thumbgen_cache_files'),0,1)=="/"){
					if(is_readable($folders["sitePath"].get_option('thumbgen_cache_files'))){
						if($_POST['clear_cache']){
							$dir=$folders["sitePath"].get_option('thumbgen_cache_files');
							$clearCache=opendir($dir);
							while ($archivo = readdir($clearCache)){
								if($archivo!=".." && $archivo!="."){ unlink($dir.$archivo); }
							}
							closedir($clearCache);
							echo "<div class='updated'><p><strong>The cache folder have been cleared</strong></p></div>";
						}
						
						if(is_writable($folders["sitePath"].get_option('thumbgen_cache_files'))){
							echo "<div class='updated'><p><strong>The specified folder seems to be fine. You have configured thumbGen!</strong></p></div>";
						}
						else{
							echo "<div class='error'><p><strong>The specified folder is not writable!. Please check the folder permissions or thumbGen will not work</strong></p></div>";
						}
					}
					else{
						if($_POST['create_folder']){
							if(@mkdir($folders["sitePath"].get_option('thumbgen_cache_files'),0755)){
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
						<small class="description">Use an absolute URL like /wp-content/themes/mytheme/no-image.jpg or http://www.mysite.com/wp-content/themes/mytheme/no-image.jpg<br />This one will be used if the requested image can't be found.<br />If you don't specify this image, the generated thumbnail will be a black image.</small>
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
	<?php
}
?>