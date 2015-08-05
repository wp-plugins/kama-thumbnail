<?php

if( ! is_admin() ) return true;

if( version_compare(PHP_VERSION, '5.3', '>=') ) return true;

php53_notice('Kama Tumbnail');

function php53_notice( $software_name, $notice = '', $notice_cap = 'activate_plugins', $notice_hook = 'all_admin_notices'){
    if(!$notice){
        $notice = sprintf('%1$s requires PHP v5.3 (or higher).', $software_name);
        $notice .= ' '.sprintf('You\'re currently running <code>PHP v%1$s</code>.', PHP_VERSION);
        $notice .= ' A simple update is necessary. Please ask your web hosting company to do this for you.';
        $notice .= ' '.sprintf('To remove this message, please deactivate %1$s.', $software_name);
    }
    
	$notice_handler = create_function('', 'if(current_user_can(\''.str_replace("'", "\\'", $notice_cap).'\'))'.
                                          '  echo \'<div class="error"><p>'.str_replace("'", "\\'", $notice).'</p></div>\';');
    
	add_action( $notice_hook, $notice_handler );
}

return false;