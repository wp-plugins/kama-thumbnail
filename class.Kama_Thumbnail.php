<?php

class Kama_Thumbnail{
	public static $opt_name = 'kama_thumbnail';
	public static $opt;
		
	function __get( $name ){
		if( $name == 'opt' ) return self::$opt;
	}
		
	function __construct(){
		self::$opt = (object) ( ($tmp=get_option(self::$opt_name)) ? $tmp : self::def_options() );

		if( ! @ self::$opt->no_photo_url ) self::$opt->no_photo_url = KT_URL .'no_photo.png';
		if( ! @ self::$opt->cache_folder ) self::$opt->cache_folder = str_replace('\\', '/', WP_CONTENT_DIR . '/cache/thumb');
		if( ! @ self::$opt->cache_folder_url ) self::$opt->cache_folder_url = content_url() .'/cache/thumb';
		
		// allow_hosts
		$ah = & self::$opt->allow_hosts;
		if( $ah && ! is_array( $ah ) ){
			$ah = preg_split('~\s*,\s*~', trim( $ah ) ); // сделаем массив
			foreach( $ah as & $host )
				$host = str_replace('www.', '', $host );
		}
		else 
			$ah = array();

		$this->wp_init();
	}
		
	function wp_init(){
		// админка
		if( is_admin() && ! defined('DOING_AJAX') ){
			add_action('admin_menu', array( & $this, 'admin_options') ); // закомментируйте, чтобы убрать опции из админки
			add_action('admin_menu', array( & $this, 'claer_handler') );
			
			add_filter('save_post', array( & $this, 'clear_post_meta') );
			
			// ссылка на настойки со страницы плагинов
			add_filter('plugin_action_links', array( & $this, 'setting_page_link'), 10, 2 );		
		}

		if( self::$opt->use_in_content ){
			add_filter('the_content', array( & $this, 'replece_in_content') );
			add_filter('the_content_rss', array( & $this, 'replece_in_content') );
		}
		
		if( ! defined('DOING_AJAX') ){			
			// l10n
			$locale = get_locale();
			if( $locale != 'ru_RU' ){
				$patt   = KT_DIR . 'lang/'. self::$opt_name .'-%s.mo';
				$mofile = sprintf( $patt, $locale );
				if( ! file_exists( $mofile ) )
					$mofile = sprintf( $patt, 'en_US' );

				load_textdomain( self::$opt_name, $mofile );
			}
		}

	}
	
	public static function def_options(){
		return array(
			'meta_key'       => 'photo_URL',
			'cache_folder'   => '', // полный путь до папки миниатюр
			'cache_folder_url' => '', // URL до папки миниатюр
			'no_photo_url'   => '', // УРЛ на заглушку
			'use_in_content' => 1,  // искать ли класс mini у картинок в тексте, чтобы изменить их размер
			'auto_clear'     => 0,
			'no_stub'        => 0,
			'quality'        => 85,
			'subdomen'       => '', // поддомены на котором могут быть исходные картинки (через запятую): img.site.ru,img2.site.ru
			'allow_hosts'    => '', // доступные хосты, кроме родного, через запятую
		);
	}
	
	# Функции поиска и замены в посте
	function replece_in_content( $content ){
		if( false !== strpos( $content, 'mini') ){
			$content = preg_replace_callback('@<img\s([^>]*class=["\'][^>]*mini[^>]*["\'][^>]*)>@s', array( & $this, '__replece_in_content'), $content );
		}
		
		return $content;
	}
	function __replece_in_content( $m ){
		$args = trim($m[1], '/ ');
		$args = preg_replace('@(?<!=)["\']\s+@', '&', $args );
		$args = preg_replace('@["\']@', '', $args );
		
		$kt = new Kama_Make_Thumb( $args );
		return $kt->a_img();
	}
		
	## Удалет произвольное поле со ссылкой при обновлении поста, чтобы создать его снова
	function clear_post_meta( $post_id ){
		update_post_meta( $post_id, self::$opt->meta_key, '' );
	}
	
	

	// ADMIN PART ------------------------------------------------------
	function activation(){
		if( ! get_option(self::$opt_name) )
			update_option( self::$opt_name, self::def_options() );
	}
	function uninstall(){
		$clear->clear_cache();
		$clear->del_customs();
		
		delete_option( self::$opt_name );
		@ rmdir( $clear->opt->cache_folder );
	}
	
	## для вывода сообещний в админке
	static function show_message( $text = '', $class = 'updated' ){
		$echo = '<div id="message" class="'. $class .' notice is-dismissible"><p>'. $text .'</p></div>';
		add_action('admin_notices', create_function('', "echo '$echo';" ) );
	}
	
	function admin_options(){
		// Добавляем блок опций на базовую страницу "Чтение"
		add_settings_section('kama_thumbnail', __kt('Настройки Kama Thumbnail'), '', 'media' );

		// Добавляем поля опций. Указываем название, описание, 
		// функцию выводящую html код поля опции.
		add_settings_field( 'kt_options_field',
			'<a href="?kt_clear=clear_cache" class="button">'. __kt('Очистить кеш картинок') .'</a> <br><br> 
			<a href="?kt_clear=del_customs" class="button">'. __kt('Удалить произвольные поля') .'</a>',
			array( & $this, 'options_field'), // можно указать ''
			'media', // страница
			'kama_thumbnail' // секция
		);

		// Регистрируем опции, чтобы они сохранялись при отправке 
		// $_POST параметров и чтобы callback функции опций выводили их значение.
		register_setting('media', self::$opt_name );
	}
	
	function options_field(){
		$opt_name = self::$opt_name;
		$opt = (object) get_option( $opt_name );
		
		$def_opt = (object) self::def_options();
		
		$out = '';
		
		$out .= '
		<input type="text" name="'. $opt_name .'[cache_folder]" value="'. $opt->cache_folder .'" style="width:100%;" placeholder="'. $this->opt->cache_folder .'"><br>
		'. __kt('Полный путь до папки кэша с правами 755 и выше. По умолчанию: пусто.') .'
		<br><br>
		
		<input type="text" name="'. $opt_name .'[cache_folder_url]" value="'. $opt->cache_folder_url .'" style="width:100%;" placeholder="'. $this->opt->cache_folder_url .'"><br>
		'. __kt('УРЛ папки кэша. По умолчанию: пусто.') .'
		<br><br>
		
		<input type="text" name="'. $opt_name .'[no_photo_url]" value="'. $opt->no_photo_url .'" style="width:100%;" placeholder="'. $this->opt->no_photo_url .'"><br>
		'. __kt('УРЛ картинки заглушки. По умолчанию: пусто.') .'
		<br><br>
		
		<input type="text" name="'. $opt_name .'[meta_key]" value="'. $opt->meta_key .'" style="width:100%;"><br>
		'. __kt('Название произвольного поля, куда будет записываться УРЛ миниатюры. По умолчанию:') .' <code>'. $def_opt->meta_key .'</code>
		<br><br>
		
		<input type="text" name="'. $opt_name .'[allow_hosts]" value="'. $opt->allow_hosts .'" style="width:100%;"><br>
		'. __kt('Хосты через запятую с которых разрешается создавать миниатюры. Пр.: <i>sub.mysite.com</i>') .'
		<br><br>
		
		<input type="text" name="'. $opt_name .'[quality]" value="'. $opt->quality .'" style="width:50px;"> 
		'. __kt('Качество создаваемых миниатюр от 0 до 100. По умолчанию:') .' <code>'. $def_opt->quality .'</code>
		<br><br>
		
		<label>
			<input type="checkbox" name="'. $opt_name .'[no_stub]" value="1" '. checked(1, @ $opt->no_stub, 0) .'> '. __kt('Не показывать картинку-заглушку.') .'
		</label><br><br>
		
		<label>
			<input type="checkbox" name="'. $opt_name .'[auto_clear]" value="1" '. checked(1, @ $opt->auto_clear, 0) .'> '. __kt('Автоматическая очистка папки кэша каждые 7 дней.') .'
		</label><br><br>
		
		<label>
			<input type="checkbox" name="'. $opt_name .'[use_in_content]" value="1" '. checked(1, @ $opt->use_in_content, 0) .'> '. __kt('Искать класс <code>mini</code> у картинки в тексте поста и сделать из нее миниатюру по указанным у нее размерам.') .'
		</label>
		';
		echo $out;
	}
	
	function setting_page_link( $actions, $plugin_file ){
		if( false === strpos( $plugin_file, basename(KT_DIR) ) ) return $actions;

		$settings_link = '<a href="'. admin_url('options-media.php') .'">'. __kt('Настройки') .'</a>'; 
		array_unshift( $actions, $settings_link );
		
		return $actions; 
	}
	// / ADMIN PART ------------------------------------------------------
	
	
	// CLEAR -----------------------------------
	function claer_handler(){		
		if( isset($_GET['kt_clear']) && current_user_can('manage_options') )
			return $this->force_clear( $_GET['kt_clear'] );
		
		if( isset( self::$opt->auto_clear ) )
			$this->clear();
	}
	
	function clear(){	
		$cache_dir = self::$opt->cache_folder;
		$expire_time = time() + (3600*24*7);

		$expire = @ file_get_contents( $cache_dir .'/expire');
		if( $expire && (int) $expire < time() )
			$this->clear_cache();

		@ file_put_contents( $cache_dir .'/expire', $expire_time );
		
		return;
	}
	
	# ?kt_clear=clear_cache - очистит кеш картинок ?kt_clear=del_customs - удалит произвольные поля
	function force_clear( $do ){
		switch( $do ){
			case 'clear_cache': $text = $this->clear_cache(); break;
			case 'del_customs': $text = $this->del_customs(); break;
		}
	}

	function clear_cache(){
		if( ! $cache_dir = self::$opt->cache_folder ){
			self::show_message( __kt('Путь до папки кэша не установлен в настройках.'), 'error');
			return false;
		}
		
		if( ! is_dir($cache_dir) ) return true;

		foreach( glob($cache_dir .'/*') as $obj ) unlink($obj);
		
		self::show_message( __kt('Кэш <b>Kama Thumbnail</b> был очищен.') );
		
		return true;
	}

	function del_customs(){
		global $wpdb;
		if( ! self::$opt->meta_key )
			return self::show_message('meta_key option not set.', 'error');
			
		if( $wpdb->delete( $wpdb->postmeta, array('meta_key'=>self::$opt->meta_key ) ) )
			self::show_message( sprintf( __kt('Все произвольные поля <code>%s</code> были удалены.'), self::$opt->meta_key ) );
		else
			self::show_message( sprintf( __kt('Не удалось удалить произвольные поля <code>%s</code>'), self::$opt->meta_key ) );

		return;
	}
	// / CLEAR -----------------------------------
	
}


