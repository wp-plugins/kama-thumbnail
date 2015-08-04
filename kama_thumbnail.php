<?php

__kt('Создает миниатюры постов на лету и кэширует результат. Из какой картинки создавать миниатюру плагин получает из: миниатюр WP / первая картинка в контенте / первая картинка вложение. Для использования в тексте поста задайте картинке класс "mini": <code>class="mini"</code> и укажите ширину и/или высоту картинке. В теме/плагине используйте функции: <code>kama_thumb_a_img("w=200 &h=100")</code>, <code>kama_thumb_img("w=200 &h=100")</code>, <code>kama_thumb_src("w=200 &h=100")</code>. Все аргументы: <code>src</code>, <code>post_id</code>, <code>w/width</code>, <code>h/height</code>, <code>notcrop</code>, <code>q</code>, <code>alt</code>, <code>class</code>, <code>title</code>, <code>no_stub</code>.');

/*
Plugin Name: Kama Thumbnail
Author: Kama 
Description: Создает миниатюры постов на лету и кэширует результат. Из какой картинки создавать миниатюру плагин получает из: миниатюр WP / первая картинка в контенте / первая картинка вложение. Для использования в тексте поста задайте картинке класс "mini": <code>class="mini"</code> и укажите ширину и/или высоту картинке. В теме/плагине используйте функции: <code>kama_thumb_a_img("w=200 &h=100")</code>, <code>kama_thumb_img("w=200 &h=100")</code>, <code>kama_thumb_src("w=200 &h=100")</code>. Все аргументы: <code>src</code>, <code>post_id</code>, <code>w/width</code>, <code>h/height</code>, <code>notcrop</code>, <code>q</code>, <code>alt</code>, <code>class</code>, <code>title</code>, <code>no_stub</code>.
Plugin URI: http://wp-kama.ru/?p=142
Text Domain: kama_thumbnail
Domain Path: lang
Version: 1.6.5.1
*/

define('KT_DIR', dirname(__FILE__) .'/' );
define('KT_URL', plugins_url('', __FILE__) .'/' );


/*
spl_autoload_register('kt_classloads'); // autoload classes
function kt_classloads( $class ){
	if ( 0 !== strncmp('Kama_', $class, 5) ) return;
	
	require_once KT_DIR . "class.{$class}.php";
}
*/


//require KT_DIR .'class.Kama_Clear_Thumb.php';
require KT_DIR .'class.Kama_Thumbnail.php';


register_uninstall_hook( __FILE__, array('Kama_Thumbnail', 'uninstall') );
register_activation_hook( __FILE__, array('Kama_Thumbnail', 'activation') );


add_action('plugins_loaded', 'Kama_Thumbnail_load');
function Kama_Thumbnail_load(){
	// kt init
	Kama_Thumbnail::instance()->wp_init();
	
	// l10n
	$locale = get_locale();
	if( $locale != 'ru_RU' ){
		$patt   = KT_DIR . 'lang/kama_thumbnail-%s.mo';
		$mofile = sprintf( $patt, $locale );
		if( ! file_exists( $mofile ) )
			$mofile = sprintf( $patt, 'en_US' );

		load_textdomain('kama_thumbnail', $mofile );
	}
}

## локализация
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
function kama_thumb_src( $args = ''){
	$kt = Kama_Thumbnail::instance();
	$kt->set_args( $args );
	return $kt->src();
}

# вернет картинку (готовый тег img)
function kama_thumb_img( $args=''){
	$kt = Kama_Thumbnail::instance();
	$kt->set_args( $args );
	return $kt->img();
}

# вернет ссылку-картинку
function kama_thumb_a_img( $args=''){
	$kt = Kama_Thumbnail::instance();
	$kt->set_args( $args );
	return $kt->a_img();
}

