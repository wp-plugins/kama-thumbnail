=== Plugin Name ===
Stable tag: 1.9.4
Tested up to: 4.3.0
Requires at least: 2.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Contributors: Tkama
Tags: thumbnail

Create any thumbnails on fly and cache result. Auto-create of post thumbs based on: WP post thumbnail or first img in post content/attachment.


== Description ==
Super convenient way to create post thumbnails on the fly without server overload.

The best alternative to scripts like "thumbnail.php".

### Usage ###

The plugin for developers firstly, because it don't do anything after install. In order to the plugin begin to work, you need use one of PHP function in your theme or plugin: `kama_thumb_src()`, `kama_thumb_img()`, `kama_thumb_a_img()`, like this:

`
<?php echo kama_thumb_img('w=150 &h=150'); ?>
`

Using the code in the loop you will get ready thumbnail IMG tag. Plugin takes post thumbnail image or find first image in post content, resize it and create cache, also it create custom field for the post with URL to original image. In simple words it cache all routine and in next page loads just take cache result.
	
You can make thumbs from custom URL, like this:
`<?php echo kama_thumb_img('w=150 &h=150 &src=URL_TO_IMG'); ?>`

The `URL_TO_IMG` must be from local server: by default, plugin don't work with external images, because of security. But you can set allowed hosts on settings page.

After install use this functions in code:

* `kama_thumb_src( $args )` – thumb url
* `kama_thumb_img( $args )` – thumb IMG tag
* `kama_thumb_a_img( $args )` – thumb IMG tag wraped with `<a>`. A link of A will leads to original image.

Acceptable parameters of `$arg`:

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
1. You can pass `$args` as string or array:
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

2. You can set only one side: `width` | `height`, then other side became proportional.
3. `src` parameter is for cases when you need create thumb from any image not image of WordPress post.
4. For test is there image for post, use this code:
`
if( ! kama_thumb_img('w=150&h=150&no_stub') )
	echo 'NO img';
`

### Examples ###
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

#### #4 `kama_thumb_a_img()` function ####
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
Get thumb of post ID=50:

`
<?php echo kama_thumb_img("w=150 &h=100 &post_id=50"); ?>
`

### I don't need plugin ###
This plugin can be easily used not as a plugin, but as a simple php file.

If you are themes developer, and need all it functionality, but you need to install the plugin as the part of your theme, this short instruction for you:

1. Create folder in your theme, let it be 'thumbmaker' - it is for convenience.
2. Download the plugin and copy the files: `class.Kama_Make_Thumb.php` and `no_photo.png` to the folder you just create.
3. Include `class.Kama_Make_Thumb.php` file into theme `functions.php`, like this:
`require 'thumbmaker/class.Kama_Make_Thumb.php';`
4. Bingo! Use functions: `kama_thumb_*()` in your theme code.
5. If necessary, open `class.Kama_Make_Thumb.php` and edit options (at the top of the file): cache folder URL/PATH, custom field name etc.

* Conditions of Use - mention of this plugin in describing of your theme.


== Screenshots ==

1. Setting block on standart "Media" admin page.


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


== Changelog ==
= 1.9.4 =
* Fix: ext detection if img URL have querya rgs like <code>*.jpg?foo</code>

= 1.9.3 =
* improve: DOCUMENT ROOT detection if allow_url_fopen and CURL disabled on server

= 1.9.2 =
* fix: trys to get image by abs server path, if none of: CURL || allow_url_fopen=on is set on server

= 1.9.1 =
* Fix: getimagesizefromstring() only work in php 5.4+

= 1.9.0 =
* added: Images parses from URL with curl first

= 1.8.0 =
* added: Images parses from URL. It fix some bugs, where plugin couldn't create abs path to img.
* added: Allowed hosts settings. Now you can set sites from which tumbs will be created too.

= 1.7.2 =
* Back to PHP 5.2 support :(

= 1.7.1 =
* PHP lower then 5.3 now not supported, because it's bad practice...

= 1.7 =
* Fix: refactor - separate one class to two: "WP Plugin" & "Thumb Maker". Now code have better logic!

= 1.6.5 =
* added: EN localisation

= 1.6.4 =
* Added: now cache_folder & no_photo_url detected automatically
* Added: notcrop parametr


== Upgrade Notice ==
All upgrades are made automatically
