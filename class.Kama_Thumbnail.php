<?php

class Kama_Thumbnail{
	protected static $opt_name = 'kama_thumbnail';
	public static $instance;
	protected $opt;
	
	# changable
	public $src;
	public $width;
	public $height;
	public $notcrop;
	public $quality;
	public $post_id;
	public $no_stub;
	
	private $args;
	
	public static function instance(){
		is_null( self::$instance ) AND self::$instance = new self;
		return self::$instance;
	}
	
	function __construct(){
		$this->opt = (object) ( get_option( self::$opt_name ) ?: $this->def_options() );
		
		if( ! $this->opt->no_photo_url ) $this->opt->no_photo_url = KT_URL .'no_photo.png';
		if( ! $this->opt->cache_folder ) $this->opt->cache_folder = str_replace('\\', '/', WP_CONTENT_DIR . '/cache/thumb');
		
//		if( is_null( self::$instance ) )
//			$this->init();
	}
	
	function wp_init(){		
		if( is_admin() && ! defined('ADMIN_AJAX') ){
			add_action('admin_menu', array( $this, 'admin_options') ); // закомментируйте, чтобы убрать опции из админки
			add_action('admin_menu', array( $this, 'admin_clear') );
		}

		if( $this->opt->use_in_content ){
			add_filter('the_content', array( $this, 'replece_in_content') );
			add_filter('the_content_rss', array( $this, 'replece_in_content') );
		}
		
		// админка
		add_filter('save_post', array( $this, 'clear_post_meta') );

		// ссылка на настойки со страницы плагинов
		add_filter('plugin_action_links', array( $this, 'setting_page_link'), 10, 2 );		
	}	
		
	
	function def_options(){
		return array(
			'meta_key'       => 'photo_URL',
			'cache_folder'   => '', // полный путь до папки миниатюр
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
	function replece_in_content($content){
		if( false !== strpos( $content, 'mini') ){
			$content = preg_replace_callback('@<img\s([^>]*class=["\'][^>]*mini[^>]*["\'][^>]*)>@s', array( $this, '__replece_in_content'), $content );
		}
		
		return $content;
	}
	function __replece_in_content( $m ){
		$args = trim($m[1], '/ ');
		$args = preg_replace('@(?<!=)["\']\s+@', '&', $args );
		$args = preg_replace('@["\']@', '', $args );
		
		$this->set_args( $args );
		return $this->a_img();
	}
	
	
	# Берем ссылку на картинку из произвольного поля, или из текста, создаем произвольное поле.
	# Если в тексте нет картинки, ставим заглушку no_photo
	private function get_img_url_and_write_postmeta(){
		global $post, $wpdb;
		$pID = (int) ( $this->post_id ? $this->post_id : $post->ID );

		if( $src = get_post_meta( $pID, $this->opt->meta_key, true ) )
			return $src;

		// проверяем наличие стандартной миниатюры
		if( $_thumbnail_id = get_post_meta( $pID, '_thumbnail_id', true ) )
			$src = wp_get_attachment_url( (int) $_thumbnail_id );
		
		// получаем ссылку из контента
		if( ! $src ){
			$content = ( $this->post_id ) ? $wpdb->get_var( "SELECT post_content FROM {$wpdb->posts} WHERE ID = {$pID} LIMIT 1" ) : $post->post_content;
			$src = $this->get_url_from_text( $content );
		}
		
		// получаем ссылку из вложений - первая картинка
		if( ! $src ){
			$attch_img = get_children( array(
				'numberposts' => 1,
				'post_mime_type' => 'image',
				'post_parent' => $pID,
				'post_type' => 'attachment'
			) );
			$attch_img = array_shift( $attch_img );
			if( $attch_img )
				$src = wp_get_attachment_url( $attch_img->ID );
		}

		// добавим заглушку no-photo, чтобы постоянно не проверять
		if( ! $src ) $src = 'no_photo';
		
		update_post_meta( $pID, $this->opt->meta_key, $src, true );
		
		return $src;
	}
	
	// вырезаем ссылку из текста 
	private function get_url_from_text( $text ){
		if ( 
			false !== strpos( $text, 'src=') 
			&& 
			preg_match('@(?:<a[^>]+href=[\'"](.*?)[\'"][^>]*>)?<img[^>]+src=[\'"](.*?)[\'"]@i', $text, $match)
		){
			// проверяем УРЛ ссылки
			if( ($src = $match[1]) && ( ! preg_match('~.*\.(jpg|png|gif)$~i', $src) || ! $this->__is_allow_host( $src ) ) )
				$src = false;
			// проверям УРЛ картинки, если нет УРЛа ссылки
			if( ! $src && ($src = $match[2]) && ! $this->__is_allow_host( $src ) )
				$src = false;
		}
		return $src;
	}
	
	// проверяем что картинка с доступного (нашего) сервера
	private function __is_allow_host( $url ){
		$psrc = parse_url( $url );
		$allow_hosts = @$this->opt->allow_hosts .','. $_SERVER['HTTP_HOST'];

		if ( $psrc['host'] && (false === stripos($allow_hosts, $psrc['host'])) )
			return false;
			
		return true;
	}
	
	
	# Функция создает миниатюру. Возвращает УРЛ ссылку на миниатюру
	function do_thumbnail(){
		// если не передана ссылка, то ищем её в контенте и записываем пр.поле
		if( ! $this->src ) 
			$this->src = $this->get_img_url_and_write_postmeta();
		
		// проверяем нужна ли картинка заглушка
		if( $this->src == 'no_photo'){
			if( $this->no_stub )
				return false;
			else
				$this->src = $this->opt->no_photo_url;
		}
			
		$psrc = parse_url( $this->src );
		
		// картинка не определена
		if( ! $psrc['path'] ) return false;
			
		$doc_root = rtrim( $_SERVER['DOCUMENT_ROOT'], '/'); // для разных серверов
		// изменяем путь до корня сайта для поддоменов
		if( false !== strpos( @$this->opt->subdomen, $psrc['host'] ) )
			$doc_root = str_replace($_SERVER['HTTP_HOST'], $psrc['host'], $doc_root);
			
		$src_path  = $doc_root . $psrc['path']; // собираем полный путь
		preg_match('~\.([a-z]+)$~', $psrc['path'], $m );
		$ext       = strtolower($m[1]);
		$notcrop   = $this->notcrop ? 'notcrop' : '';
		$file_name = substr( md5($psrc['path']), -9) . "_{$this->width}x{$this->height}_{$notcrop}.{$ext}";
		$dest      = $this->opt->cache_folder . "/$file_name"; //файл миниатюры от корня сайта
		$out_link  = str_replace( rtrim( $_SERVER['DOCUMENT_ROOT'], '/'), '', $this->opt->cache_folder ) . "/$file_name"; //ссылка на изображение;

		// если миниатюра уже есть, то возвращаем её
		if ( file_exists( $dest ) )
			return $out_link;
		
		if( ! $this->__cache_folder_check() ){
			$this->show_message( __kt('Директории для создания миниатюр не существует. Создайте её: '. $this->opt->cache_folder ), 'error');
			return;
		}
		
		// Если физически файл не существует (бывает файла пропадет после переезда на новый хости или работ над сайтом).
		// В этом случае, для указаного УРЛ будет создана миниатюра из заглушки no_photo.png.
		// Чтобы после появления файла, миниатюра создалась правильно, нужно очистить кэш плагина.
		if ( ! file_exists( $src_path ) ){
			$this->src = $this->opt->no_photo_url;
			$psrc = parse_url( $this->src );
			$src_path = $doc_root . $psrc['path'];
		}
			
		# создаем миниатюру
		# проверим наличие библиотеки Imagick
		if ( extension_loaded('imagick') ){
			$this->make_thumbnail_Imagick( $src_path, $this->width, $this->height, $dest, $this->notcrop );
			
			return $out_link;
		}
		# проверим наличие библиотеки GD
		if( extension_loaded('gd') ){
			$this->make_thumbnail_GD( $src_path, $this->width, $this->height, $dest, $this->notcrop );
			
			return $out_link;
		}

		return false;
	}
	
	## проверяем наличие директории, пытаемся создать, если её нет	
	private function __cache_folder_check(){
		$is = true;
		if( ! is_dir( $this->opt->cache_folder ) ) $is = @ mkdir( $this->opt->cache_folder, 0755, true );
		
		return $is;
	}

	# ядро: создание и запись файла-картинки на основе библиотеки Imagick
	private function make_thumbnail_Imagick( $src_path, $width, $height, $dest, $notcrop ){
		$image = new Imagick( $src_path );
		
		# Select the first frame to handle animated images properly
		if ( is_callable( array( $image, 'setIteratorIndex') ) )
			$image->setIteratorIndex(0);
		
		// устанавливаем качество
		$format = $image->getImageFormat();
		if( $format == 'JPEG' || $format == 'JPG')
			$image->setImageCompression( Imagick::COMPRESSION_JPEG );
		$image->setImageCompressionQuality( $this->quality );
		
		$origin_h = $image->getImageHeight();
		$origin_w = $image->getImageWidth();		
		
		// получим координаты для считывания с оригинала и размер новой картинки
		list( $dx, $dy, $wsrc, $hsrc, $width, $height ) = $this->__resize_coordinates( $height, $origin_h, $width, $origin_w, $notcrop );
		
		// обрезаем оригинал
		$image->cropImage( $wsrc, $hsrc, $dx, $dy );
		$image->setImagePage( $wsrc, $hsrc, 0, 0);
		
		// Strip out unneeded meta data
		$image->stripImage();
		
		// уменьшаем под размер
		$image->scaleImage( $width, $height );
		
		$image->writeImage( $dest );
		chmod( $dest, 0755 );
		$image->clear();
		$image->destroy();		
	}
	
	# ядро: создание и запись файла-картинки на основе библиотеки GD
	private function make_thumbnail_GD( $src_path, $width, $height, $dest, $notcrop ){
		$size = @ getimagesize( $src_path );
		//die( print_r($size) );

		if( $size === false )
			return false; // не удалось получить параметры файла;
		
		list( $origin_w, $origin_h ) = $size;
		
		$format = strtolower( substr( $size['mime'], strpos($size['mime'], '/')+1 ) );

		// Создаем ресурс картинки
		$image = @ imagecreatefromstring( file_get_contents( $src_path ) );
		if ( ! is_resource( $image ) )
			return false; // не получилось получить картинку
		
		// получим координаты для считывания с оригинала и размер новой картинки
		list( $dx, $dy, $wsrc, $hsrc, $width, $height ) = $this->__resize_coordinates( $height, $origin_h, $width, $origin_w, $notcrop );
		
		// Создаем холст полноцветного изображения
		$thumb = imagecreatetruecolor( $width, $height );
		
		if( function_exists('imagealphablending') && function_exists('imagesavealpha') ) {
			imagealphablending( $thumb, false ); // режим сопряжения цвета и альфа цвета
			imagesavealpha( $thumb, true ); // флаг сохраняющий прозрачный канал
		}
		if( function_exists('imageantialias') )
			imageantialias( $thumb, true ); // включим функцию сглаживания	

		if( ! imagecopyresampled( $thumb, $image, 0, 0, $dx, $dy, $width, $height, $wsrc, $hsrc ) )
			return false; // не удалось изменить размер
		
		// 
		// Сохраняем картинку
		if( $format == 'png'){		
			// convert from full colors to index colors, like original PNG.
			if ( function_exists('imageistruecolor') && ! imageistruecolor( $thumb ) ){
				imagetruecolortopalette( $thumb, false, imagecolorstotal( $thumb ) );
			}
			imagepng( $thumb, $dest );
		} 
		elseif( $format == 'gif'){
			imagegif( $thumb, $dest );
		}
		else {
			imagejpeg( $thumb, $dest, $this->quality );
		}
		chmod( $dest, 0755 );
		imagedestroy($image);
		imagedestroy($thumb);
		  
		return true; 
	}
	
	# координаты кадрирования 
	# $height (необходимая высота), $origin_h (оригинальная высота), $width, $origin_w
	# @return array - отступ по Х и Y и сколько пикселей считывать по высоте и ширине у источника: $dx, $dy, $wsrc, $hsrc
	private function __resize_coordinates( $height, $origin_h, $width, $origin_w, $notcrop ){
		if( $notcrop ){
//		if( 1 ){
			// находим меньшую подходящую сторону у картинки и обнуляем её
			if( $width/$origin_w < $height/$origin_h ) $height = 0;
			else $width = 0;
		}
		
		// если не указана одна из сторон задаем ей пропорциональное значение
		if( ! $width ) 	$width = round( $origin_w * ($height/$origin_h) );
		if( ! $height ) $height = round( $origin_h * ($width/$origin_w) );
		
		// Определяем необходимость преобразования размера так чтоб вписывалась наименьшая сторона
		// if( $width < $origin_w || $height < $origin_h )
			$ratio = max( $width/$origin_w, $height/$origin_h );
			
		//срезать справа и слева
		$dx = $dy = 0;
		if( $height/$origin_h > $width/$origin_w ) 
			$dx = round( ($origin_w - $width*$origin_h/$height)/2 ); //отступ слева у источника
		else // срезать верх и низ
			$dy = round( ($origin_h - $height*$origin_w/$width)/2 ); // $height*$origin_w/$width)/2*6/10 - отступ сверху у источника *6/10 - чтобы для вертикальных фоток отступ сверху был не половина а процентов 30
		 
		// сколько пикселей считывать c источника
		$wsrc = round( $width/$ratio );
		$hsrc = round( $height/$ratio );
		
		return array( $dx, $dy, $wsrc, $hsrc, $width, $height );
	}
	
	
	// Обработка параметров для создания миниатюр ----
	function set_args( $args = ''){
		if( is_array( $args ) ) $this->args = $args;
		else{
			$args = preg_replace('~\s+&~', '&', $args ); // удалим лишние пробелы для parse_str
			parse_str( $args, $this->args );
		}
		//die(print_r($this->args));
		$rgs = & $this->args;
		$rgs = array_map('trim', $rgs);
		
		$this->width   = (int)    ( isset($rgs['w'])       ? $rgs['w']       : ( isset($rgs['width'])  ? $rgs['width'] : false ) );
		$this->height  = (int)    ( isset($rgs['h'])       ? $rgs['h']       : ( isset($rgs['height']) ? $rgs['height'] : false ) );
		$this->notcrop = (bool)   ( isset($rgs['notcrop']) ? true            : false );
		$this->src     = (string) ( isset($rgs['src'])     ? $rgs['src']     : '');
		$this->quality = (int)    ( isset($rgs['q'])       ? $rgs['q']       : $this->opt->quality );
		$this->post_id = (int)    ( isset($rgs['post_id']) ? $rgs['post_id'] : false );
		
		if( isset( $rgs['no_stub'] ) || isset( $this->opt->no_stub ) ) 
			$this->no_stub = true;
			
		// если не установленна ни одна из сторон
		if( ! $this->width && ! $this->height ) $this->width = $this->height = 100;
	
	}
	
	function src(){
		return $this->do_thumbnail();
	}
	
	function img(){
		$rgs = & $this->args;
		
		$class = isset($rgs['class']) ? trim($rgs['class'])                 : 'aligncenter';
		$alt   = isset($rgs['alt'])   ? trim($rgs['alt'])                   : '';
		$title = isset($rgs['title']) ? 'title="'. trim($rgs['title']) .'"' : '';
		
		$width = $height = '';
		if( ! $this->notcrop ){
			$width  = $this->width  ? "width='$this->width'"   : '';
			$height = $this->height ? "height='$this->height'" : '';
		}
		
		$out = '';
		if( $src = $this->src() )
			$out = "<img class=\"$class\" src=\"$src\" alt=\"$alt\" {$title} {$width} {$height}>\n";
		
		return $out;
	}

	function a_img(){
		$out = '';
		if( $img = $this->img() )
			$out = "<a href=\"{$this->src}\">$img</a>";
		
		return $out;
	}
	// / Обработка параметров для создания миниатюр ----
	
	
	## Удалет произвольное поле со ссылкой при обновлении поста, чтобы создать его снова
	function clear_post_meta( $post_id ){
		delete_post_meta( $post_id, $this->opt->meta_key );
	}

	function activation(){
		if( ! get_option(self::$opt_name) )
			update_option( self::$opt_name, self::def_options() );
	}
	function uninstall(){
		$clear = new Kama_Clear_Thumb();
		$clear->clear_cache();
		$clear->del_customs();
		
		delete_option( self::$opt_name );
		@ rmdir( $clear->opt->cache_folder );
	}
	
	
	// ADMIN PART ------------------------------------------------------
	function admin_clear(){
		new Kama_Clear_Thumb();		
	}
	function admin_options(){
		// Добавляем блок опций на базовую страницу "Чтение"
		add_settings_section( 'kama_thumbnail', __kt('Настройки Kama Thumbnail'), '', 'media' );

		// Добавляем поля опций. Указываем название, описание, 
		// функцию выводящую html код поля опции.
		add_settings_field( 'kt_options_field',
			'<a href="?kt_clear=clear_cache" class="button">'. __kt('Очистить кеш картинок') .'</a> <br><br> 
			<a href="?kt_clear=del_customs" class="button">'. __kt('Удалить произвольные поля') .'</a>',
			array( $this, 'options_field'), // можно указать ''
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
		
		$def_opt = (object) $this->def_options();
		
		$out = '';
		
		$out .= '
		<input type="text" name="'. $opt_name .'[cache_folder]" value="'. $opt->cache_folder .'" style="width:100%;"><br>
		'. __kt('Полный путь до папки кэша с правами 755 и выше. По умолчанию: не установлено. Текущий:') .' <code>'. $this->opt->cache_folder .'</code>
		<br><br>
		
		<input type="text" name="'. $opt_name .'[no_photo_url]" value="'. $opt->no_photo_url .'" style="width:100%;"><br>
		'. __kt('УРЛ картинки заглушки. По умолчанию: не установлено. Текущий:') .' <code>'. $this->opt->no_photo_url .'</code>
		<br><br>
		
		<input type="text" name="'. $opt_name .'[meta_key]" value="'. $opt->meta_key .'" style="width:100%;"><br>
		'. __kt('Название произвольного поля, куда будет записываться УРЛ миниатюры. По умолчанию:') .' <code>'. $def_opt->meta_key .'</code>
		<br><br>
		
		<input type="text" name="'. $opt_name .'[quality]" value="'. $opt->quality .'" style="width:50px;"><br>
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
		if( false === strpos( $plugin_file, basename(dirname(__FILE__)) ) ) return $actions;

		$settings_link = '<a href="'. admin_url('options-media.php') .'">'. __kt('Настройки') .'</a>'; 
		array_unshift( $actions, $settings_link );
		
		return $actions; 
	}
	
	function show_message( $text = '', $class = 'updated' ){
		$code = <<<CODE
	echo '<div id="message" class="$class notice is-dismissible"><p>$text</p></div>';	
CODE;
		add_action('admin_notices', create_function('', $code ) );
	}
	// / ADMIN PART ------------------------------------------------------
	
}


class Kama_Clear_Thumb extends Kama_Thumbnail{
	function __construct(){
		parent::__construct();
		
		if( isset($_GET['kt_clear']) && current_user_can('manage_options') )
			return $this->force_clear( $_GET['kt_clear'] );
		
		if( isset( $this->opt->auto_clear ) )
			$this->clear();
	}
	
	function clear(){	
		$cache_dir = $this->opt->cache_folder;
		$expire_time = time()+(3600*24*7);

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
		if( ! $cache_dir = $this->opt->cache_folder ){
			$this->show_message( __kt('Путь до папки кэша не установлен в настройках.'), 'error');
			return false;
		}
		
		if( ! is_dir( $cache_dir ) ) return true;

		foreach( glob( $cache_dir ."/*" ) as $obj ) unlink($obj);
		
		$this->show_message( __kt('Кэш <b>Kama Thumbnail</b> был очищен.') );
		
		return true;
	}

	function del_customs(){
		global $wpdb, $kt_meta_key;
		if( ! $kt_meta_key = $this->opt->meta_key )
			die( "meta_key option not set" );
			
		if( $wpdb->delete( $wpdb->postmeta, array('meta_key'=>$this->opt->meta_key ) ) )
			add_action('admin_notices', create_function('','echo "<div id=\"message\" class=\"updated\"><p>Все произвольные поля <i>". $GLOBALS[\'kt_meta_key\'] ."</i> были удалены.</p></div>";'));
		else
			add_action('admin_notices', create_function('','echo "<div id=\"message\" class=\"updated\"><p>Не удалось удалить произвольные поля <i>". $GLOBALS[\'kt_meta_key\'] ."</i>.</p></div>";'));		

		return;
	}
}

