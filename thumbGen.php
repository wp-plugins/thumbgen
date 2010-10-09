<?php
/*
Plugin Name: thumbGen
Plugin URI: http://www.sebastianbarria.com/thumbgen/
Description: This plugin create a function called thumbGen() that allow to show any image in the specified size. It saves all generated thumbs in a cache directory under uploads.
Author: Sebastián Barría
Version: 2.0
Author URI: http://www.sebastianbarria.com/
*/

function thumbGen($img,$width=0,$height=0,$crop=1,$centered=1,$grayscale=0,$return=0){
	$sitePath=$_SERVER['DOCUMENT_ROOT'];
	$cachePath=get_option('thumbgen_cache_files');
	$defultImage=get_option('thumbgen_default_image');
	
	$file=explode("/",$img);
	$fileName=$file[count($file)-1];
	$ext=explode(".",$fileName);
	$imageExtension=strtolower($ext[count($ext)-1]);
	if($imageExtension=="jpeg"){ $imageExtension="jpg"; }
	$imageName=substr($fileName,0,strlen($fileName)-strlen($imageExtension)-1);
	$fileCache=$cachePath.$imageName."_".$width."_".$height."_".$crop."_".$centered."_".$grayscale.".".$imageExtension;
	$fileCacheGS=$cachePath.$imageName."_".$width."_".$height."_".$crop."_".$centered."_1.".$imageExtension;
	
	if(!is_readable($sitePath.$fileCache)){
		$openImage=substr($img,0,1)=="/"?$sitePath.$img:$img;
		$image = open_image($openImage);
		if(!$image){
			if($defultImage){ $image = open_image($defultImage); }
			else{ $image = imagecreatetruecolor($width, $height);}
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
		if($crop){
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
				
			if($centered){
				$offsetx=round(($x-$px)/2);
				$offsety=round(($y-$py)/2);
			}
		}
		
		//generar imagen normal
		$newImage = imagecreatetruecolor($width, $height);
		$alpha=$imageExtension!="jpg"?127:0;
		$white = imagecolorallocatealpha($newImage, 255, 255, 255, $alpha);
		imagefill($newImage,0,0,$white);
		if($imageExtension=="png"){
			imagealphablending($newImage, false);
			imagesavealpha($newImage, true);
		}
		if($imageExtension=="gif"){
			imagecolortransparent($newImage,$white);
		}
		imagecopyresampled($newImage, $image, 0, 0, $offsetx, $offsety, $width, $height, $px, $py);
		if($imageExtension=="png"){ imagepng($newImage,$sitePath.$fileCache,7); }
		else if($imageExtension=="gif"){ imagegif($newImage,$sitePath.$fileCache); }
		else{ imagejpeg($newImage,$sitePath.$fileCache,90); }
		if($grayscale){
			imagefilter($newImage,IMG_FILTER_GRAYSCALE);
			if($imageExtension=="png"){ imagepng($newImage,$sitePath.$fileCacheGS,7); }
			else if($imageExtension=="gif"){ imagegif($newImage,$sitePath.$fileCacheGS); }
			else{ imagejpeg($newImage,$sitePath.$fileCacheGS,90); }
		}
		imagedestroy($newImage);
	}
	
	if($grayscale){ $fileCache=$fileCacheGS; }	

	if(!$return){ echo $fileCache; }
	else{ return $fileCache; }
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
	// Static so we don't call plugin_basename on every plugin row.
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
	<p>This plugin creates a function named thumbGen() that allows to show any image in the specified size. It saves all generated thumbs in a cache directory.</p>
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
				}
				else{
					if(@mkdir($_SERVER['DOCUMENT_ROOT'].get_option('thumbgen_cache_files'))){
						echo "<div class='updated'><p><strong>The specified folder doesn't exists</strong>. But don't worry... I've already created it ;)</p></div>";
					}
					else{
						echo "<div class='error'><p><strong>The specified folder doesn't exists</strong> and I was not able to create it :(</p></div>";
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
</div>
<?php } ?>