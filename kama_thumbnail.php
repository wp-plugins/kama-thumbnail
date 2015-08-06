<?php

__kt('Создает миниатюры постов на лету и кэширует результат. Из какой картинки создавать миниатюру плагин получает из: миниатюр WP / первая картинка в контенте / первая картинка вложение. Для использования в тексте поста задайте картинке класс "mini": <code>class="mini"</code> и укажите ширину и/или высоту картинке. В теме/плагине используйте функции: <code>kama_thumb_a_img("w=200 &h=100")</code>, <code>kama_thumb_img("w=200 &h=100")</code>, <code>kama_thumb_src("w=200 &h=100")</code>.');

/*
Plugin Name: Kama Thumbnail
Author: Kama 
Description: Создает миниатюры постов на лету и кэширует результат. Из какой картинки создавать миниатюру плагин получает из: миниатюр WP / первая картинка в контенте / первая картинка вложение. Для использования в тексте поста задайте картинке класс "mini": <code>class="mini"</code> и укажите ширину и/или высоту картинке. В теме/плагине используйте функции: <code>kama_thumb_a_img("w=200 &h=100")</code>, <code>kama_thumb_img("w=200 &h=100")</code>, <code>kama_thumb_src("w=200 &h=100")</code>.
__Plugin URI: http://wp-kama.ru/?p=142
Text Domain: kama_thumbnail
Domain Path: lang
Version: 1.8.0
*/

define('KT_DIR', dirname(__FILE__) .'/' );
define('KT_URL', plugins_url('', __FILE__) .'/' );

//if( ! require KT_DIR .'inc/is_php53.php' ) return;

/*
spl_autoload_register('kt_classloads'); // autoload classes
function kt_classloads( $class ){
	if ( 0 !== strncmp('Kama_', $class, 5) ) return;
	
	require_once KT_DIR . "class.{$class}.php";
}
*/


require KT_DIR .'class.Kama_Thumbnail.php';
require KT_DIR .'class.Kama_Make_Thumb.php';


register_uninstall_hook( __FILE__, array('Kama_Thumbnail', 'uninstall') );
register_activation_hook( __FILE__, array('Kama_Thumbnail', 'activation') );


add_action('plugins_loaded', create_function('','new Kama_Thumbnail();') );

## l10n
function __kt( $text ){ 
	return __( $text, 'kama_thumbnail');
}



/** 
 * Функции вызова (для шаблона)
 *
 * Аргументы: src, post_id, w/width, h/height, q, alt, class, title, no_stub(не показывать заглушку)
 * Примечание: если не определяется src и переменная $post определяется неправилно, то определяем параметр
 * post_id - идентификатор поста, чтобы правильно получить произвольное поле с картинкой.
 */
# вернет только ссылку
function kama_thumb_src( $args = '' ){
	$kt = new Kama_Make_Thumb( $args );
	return $kt->src();
}

# вернет картинку (готовый тег img)
function kama_thumb_img( $args='' ){
	$kt = new Kama_Make_Thumb( $args );
	return $kt->img();
}

# вернет ссылку-картинку
function kama_thumb_a_img( $args='' ){
	$kt = new Kama_Make_Thumb( $args );
	return $kt->a_img();
}


