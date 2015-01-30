=== thumbGen ===
Contributors: sebastianbarria
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=A799JB6J57938
Tags: image,images,thumb,generator,thumbnail,developers,cache
Requires at least: 2.9.0
Tested up to: 4.1
Stable tag: trunk

This plugin creates a function named thumbGen() that allows to show any image in the specified size (plus many other things). It saves every generated thumbs in a cache directory, so it will not re-generate the thumb if it already exists.

== Description ==

This plugin is made for developers.

It creates a function named thumbGen() that allows to show any image in the specified size. Additionally you can specify values like crop, center, rotation and effects.

It saves all generated thumbs in a cache folder, so it won't overload your server at all.

== Installation ==

Just donwload the zip file, upload it to your wordpress via install plugins page (or uncompress it to your wordpress wp-content/plugins/ folder) and activate it. Once activated se the main folder and default image (in settings/thumbGen) and the plugin is ready to be used by calling its main function (see "Usage" section below).

== Usage ==

To use this function you just need to use this line code:

`<?php thumbGen(image,width,height,additional_parameters); ?>`

[image:] the URL of the original image you need to create a thumbnail from (needed).

[width:] the width you need for the generated thumbnail (default=0 - if not specified it gets the 
proportional value from the specified height).

[height:] the height you need for the generated thumbnail (default=0 - if not specified it gets the proportional value from the specified width).

note: if you don't specify the with AND height (or if you set both to 0), the image will be generated in the source size.

= Additional parameters =
[filename:] some people have troubles with duplicated names, so I've added this parameter for you to specify a new filename (or ID or something like that) in order to differentiate each file (if not set it will use the source filename).

[md5:] by default, the images are generated with an md5 encode filename. If you don't want the generated file to have an encoded name set this to 0

[force:] force thumb creation, even if it already exists (default=0) (NOT RECOMENDED! - use it just for testing or debugging)

[crop:] if you want the thumbnail to be cropped (no image deformation) if the width and height are different from the original image, set this value as 1 or true. If you want the content of the thumbnail to be resized to fit the space (image deformation) set this to 0 or false (default=1).

[halign:] horizontal align of the croped image. You can set it to left, center or right (default=center)

[valign:] vertical align of the croped image. You can set it to top, center or bottom (default=center)

[effect:] you can apply two effects: grayscale and sephia

[rotate:] you can specify a rotation angle

[background:] hex color (like #ffffff) to apply on the background ONLY when you rotate the image. If you don't want a color applied you can set this to transparent (default=transparent)

[return:] if set to 1 (or true) the image name will be returned instead of printed (default=0).

[preserveAnimation:] if set to 1 (or true) it will show animated gif's with motion but without applying other parameters. Otherwise, it will show the first frame of the animated gif resized.

[quality:] you can chooos from 0 to 9, where 0 is the worst quality (lower file size) and 9 is the best (bigger file size). The default value is 7.

== Frequently Asked Questions ==

= Where do I get more information? =

[In the plugin page](http://www.sebastianbarria.com/plugins/thumbgen/ "thumbGen")

== Screenshots ==

There's no screenshots, since this function create thumbnails...how could I get a screenshot of that?

== Changelog ==

= 2.7.1 =
* IMPROVED: better compatibility with older installations (refering to the black images problem)

= 2.7 =
* FIXED: Problem with black when using with Wordpress on folders (now is working on multisite installations and Wordpress installed in the root or folders)
* IMPROVED: Minor code changes

= 2.6.1 =
* FIXED: Problem with the multisite installation (thanks to Samuel Arendt)

= 2.6 =
* FIXED: Problem with the routes when the blog is inside a folder instead of the root (thanks to @atorresg)
* FIXED: Problem when opening files with strange characters (thanks to @javiarques)
* ADDED: New argument "preserveAnimation" to show animated gifs (when using this, all other args won't work)
* ADDED: New argument "quality" to select the output quality (from 0: worst quality to 9: best quality). This work equally for jpg and png format

= 2.5.6 =
* IMPROVED: Adapted the image URL to open: if it starts with the same HTTP_HOST, it is removed from the image URL. This fixes errors on some blocked servers

= 2.5.5 =
* ADDED: A fix for $_SERVER['REMOTE_ADDR'] on some windows servers (thanks Samuelm)

= 2.5.4 =
* ADDED: The image width and height (original and new) when the output is set to "return"

= 2.5.3 =
* FIXED: Minor fixes
* IMPROVED: Avoid showing errors. This way the plugin will allways show an image (black or default)

= 2.5.2 =
* FIXED: Minor fixes
* FIXED: The control panel to be able to set a default image

= 2.5.1 =
* FIXED: A bug in the names generation (thanks beetrootman!)
* FIXED: A problem with the gif images rotation and background

= 2.5 =
* IMPROVED: Main function updated!!! (ATTENTION, if you upgrade, maybe it will need to review your code)
* IMPROVED: check for the selected cache folder in the config page
* MODIFIED: The images are now generated with a md5 encoded name by default
* MODIFIED: functions renamed to thumbGen_function() for it not to cause problem with other plugins
* ADDED: A lot of new features: rotation, effects, background, halign, valign, etc.
* ADDED: A new option to force image generation (use only for testing)
* ADDED: New option in the config page. Now it ask you if you want to create the folder (if it not exists) instead of just creating

= 2.1 =
* IMPROVED: Documentation updated
* ADDED: Donation button added (try it!)

= 2.0 =
* IMPROVED: Is not required to send any parameter other than the image name (all have default values)
* ADDED: New settings page!!!
* ADDED: Cache folder specification (if not exist, the plugin creates it)
* ADDED: Clear cache option
* ADDED: Default image specification (to show if the image doesn't exists)
* ADDED: thumGen is able to open files from anywhere (your own site and from other ones too!)
* ADDED: Full support for image transparency
* ADDED: New parameter "return", to select if the image name is printed or returned

= 1.0 =
* This is the first release

== Examples of usage ==

In this example I will not explain detailed how this Wordpress code works, but I will show this as an example of this plugin usage:

`
<?php
$img="";
$args = array(
'post_parent'    => $post->ID,
'post_type'      => 'attachment',
'numberposts'    => 1,
'post_mime_type' => 'image'
);
$attachs = get_posts($args);
if ($attachs) {
$img=wp_get_attachment_image_src($attachs[0]->ID,'full');
}
if(!empty($img)){
?>
<img src='<?php thumbGen($img[0],171,56,"effect=grayscale&halign=left&valign=top"); ?>' alt='' />
<?php
}
?>
`

This example reads the first attached image of a post and save it's information in a variable called $img. In the thumbGen function the first parameter is $img[0] and that's the image URL. The second and third parameters are the width and height of the generated thumbnail we need. The rest of the parameters are defined in the string in the format parameter=value, concatenated with an &. the values not specified will use their default value.