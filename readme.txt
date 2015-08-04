=== Plugin Name ===
Stable tag: 1.7
Tested up to: 4.2.3
Requires at least: 2.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Contributors: Tkama
Tags: thumbnail

Create any thumbnails on fly and cache result. Auto-create of post thumbs based on: WP post thumbnail or first img in post content/attachment.


== Description ==
Super convenient way to create post thumbnails on the fly without server overload.

The best alternative to scripts like "thumbnail.php".

The plugin for developers firstly, because it don't do anything after install. In order to the plugin begin to work, you need use one of PHP function in your theme or plugin: `kama_thumb_src()`, `kama_thumb_img()`, `kama_thumb_a_img()`, like this:

`
<?php echo kama_thumb_img('w=150 &h=150'); ?>
`

Using the code in the loop you will get ready thumbnail IMG tag. Plugin takes post thumbnail image or find first image in post content, resize it and create cache, also it create custom field for the post with URL to original image. In simple words it cache all routine and in next page loads just take cache result.
	
You can make thumbs from custom URL, like this:
`<?php echo kama_thumb_img('w=150 &h=150 &src=URL_TO_IMG'); ?>`

`URL_TO_IMG` must be from local server: plugin don't work with external images, because of security.

After install use this functions in code:

* `kama_thumb_src( $args )` – thumb url
* `kama_thumb_img( $args )` – thumb IMG tag
* `kama_thumb_a_img( $args )` – thumb IMG tag wraped with `<a>`. A link of A will leads to original image.

Acceptable parameters of $arg:

* `w | width`  – (int) desired width (required)
* `h | height` – (int) desired height (required)
* `notcrop`    – (isset) set to resize image by one of the parameter: width | height.
* `q`          – (int) jpg compression quality (Default 85. max.100)
* `src`        – (str) URL to image. In this case plugin will not parse URL from post content.
* `alt`        – (str) alt attr of img tag
* `title`      - (str) title attr of img tag
* `class`      – (str) class attr of img tag.
* `no_stub`    – (isset) don't show picture stub if there is no picture. Return empty string.
* `post_id`    - (int) post ID. It needs when use function not from the loop. If pass the parameter plugin will exactly knows which post to process.

### Notes ###
* 1. You can pass `$args` as string or array:
`
// string
kama_thumb_img('w=200 &h=100 &alt=IMG NAME &class=aligncenter');

// array
kama_thumb_img( array(
	'width' => 200,
	'height' => 150,
	'class' => 'alignleft' 
) );
`

* 2. You can set only one side: `width` | `height`, then other side became proportional.
* 3. `src` parameter is for cases when you need create thumb from any image not image of WordPress post.
* 4. For test is there image for post, use this code:
`
if( ! $$img = kama_thumb_img('w=150&h=150&no_stub') )
	echo 'NO img';
`

## Examples ##
#### #1 Get Thumb ####

In the loop where you need the thumb 150х100:

`
<?php echo kama_thumb_img('w=150 &h=100 &class=alignleft myimg'); ?>
`
Result:
`
<img src='ссылка на миниатюру' alt='' class='alignleft myimg' width='150' height='100'>
`

#### #2 Not show stub image ####
`
<?php echo kama_thumb_img('w=150 &h=100 &no_stub'); ?>
`

#### #3 Get just thumb URL ####
`
<?php echo kama_thumb_src('w=100&h=80'); ?>
`
Result: `/wp-content/cache/thumb/ec799941f_100x80.png`

This url you can use like:
`
<img src='<?php echo kama_thumb_src('w=100 &h=80 &q=75'); ?>' alt=''>
`

#### #4 kama_thumb_a_img() function ####
`
<?php echo kama_thumb_a_img('w=150 &h=100 &class=alignleft myimg &q=75'); ?>
`
Result:
`
<a href='ORIGINAL_URL'><img src='THUMB_URL' alt='' class='alignleft myimg' width='150' height='100'></a>
`

#### #5 Thumb of any image URL (server locale) ####
`
<?php echo kama_thumb_img('src=http://yousite.com/IMAGE_URL.jpg &w=150 &h=100 &class=alignleft'); ?>
`

#### #6 Parameter post_id ####
`
<?php echo kama_thumb_img("w=150 &h=100 &post_id=50"); ?>
`


== Frequently Asked Questions ==
Comming soon...

== Installation ==

### Instalation via Admin Panel ###
1. Go to `Plugins > Add New > Search Plugins` enter "Kama Thumbnail"
2. Find the plugin in search results and install it.


### Instalation via FTP ###
1. Download the `.zip` archive
2. Open `/wp-content/plugins/` directory
3. Put `kama-thumbnail` folder from archive into opened `plugins` folder
4. Activate the `Kama Thumbnail` in Admin plugins page
5. Go to `Settings > Media` page to customize plugin



== Screenshots ==
Comming soon...



== TODO ==
Comming soon...


== Changelog ==
= 1.7 =
* Fix: refactor - separate one class to two: "WP Plugin" & "Thumb Maker". Now code have better logic!

= 1.6.5 =
* + EN localisation

= 1.6.4 =
* Added: now cache_folder & no_photo_url detected automatically
* Added: notcrop parametr


== Upgrade Notice ==
All upgrades are made automatically
