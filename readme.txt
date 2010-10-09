=== thumbGen ===
Contributors: sebastianbarria
Donate link: http://www.sebastianbarria.com/
Tags: thumb,generator,thumbnail,cache
Requires at least: 2.9.0
Tested up to: 2.9.2
Stable tag: trunk

This plugin creates (automatically) thumbnails from any image. Is optimized because saves the thumbs into a cache folder.

== Description ==

This plugin is made for developers.

It creates a function called thumbGen() that allows to show any image in the specified size. Additionally you can specify the way it crops the image and you can generate it in grayscale.

It saves all generated thumbs in a cache folder.

== Installation ==

Installation

Just donwload the zip file, upload it to your wordpress vía install plugins page (or uncompress it to your wordpress wp-content/plugins/ folder) and activate it. Once activated the plugin is ready to be used by calling its main function (see “Usage” section below).

To use this function you just need to use this line with a few parameters:

`<?php thumbGen(image,width,height,crop,center,grayscale); ?>`

[image:] the URL of the original image you need to create a thumbnail from (needed).

[width:] the width you need for the generated thumbnail (optional - default=0 - if not specified it gets the 
proportional value from the specified height).

[height:] the height you need for the generated thumbnail (optional - default=0 - if not specified it gets the proportional value from the specified width).

[crop:] if you want the thumbnail to be cropped (no image deformation) if the width and height are different from the original image, set this value as 1 or true. If you want the content of the thumbnail to be resized to fit the space (image deformation) set this to 0 or false (optional - default=1).

[center:] the same usage as crop parameter. If the content of the thumbnail to be cropped from the center of the original image set this value to 1 or true. If you want the content of the thumbnail to be cropped from the top left of the original image set this to 0 or false (optional - default=1).

[grayscale:] the same usage as crop and center parameter. If you want the generated thumbnail to be in grayscale, set this value to 1 or true. If you set this value as 0 or false, the thumbnail will be generated in the same colors of the original image (optional - default=1).

[return:] if set to 1 (or true) the image name will be returned instead of printed (optional - default=0).

== Frequently Asked Questions ==

= ¿Where do I get more information? =

[In the plugin page](http://www.sebastianbarria.com/thumbgen/ "Your favorite software")

== Screenshots ==

There's no screenshots, since this function create thumbnails...how could I get a screenshot of that?

== Changelog ==

= 2.0 =
* New settings page!!!
* Cache folder specification (if not exist, the plugin creates it)
* Clear cache option
* Default image specification (to show if the image doesn't exists)
* thumGen is able to open files from anywhere (your own site and from other ones too!)
* Is not required to send any parameter other than the image name (all have default values)
* New parameter "return", to select if the image name is printed or returned

= 1.0 =
* This is the first release

== Examples of usage ==

In this example I’ll not explain detailed how this Wordpress code works, but I’ll show this as an example of this plugin usage:

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
<img src='<?php thumbGen($img[0],171,56); ?>' alt='' />
<?php
}
?>
`

This example reads the first attached image of a post and save it’s information in a variable called $img. In the thumbGen function the first parameter is $img[0] and that’s the image URL. The second and third parameters are the width and height of the generated thumbnail we need. The rest of the parameters will use the default values.

