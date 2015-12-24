<?php 
/*
    Plugin Name: Send24 
    Plugin URI: http://send24.com/
    Description: Send24
    Version: 1.0.0
    Author: Barbotkin
    Author URI: https://github.com/barbotkin
    License: http://www.gnu.org/licenses/gpl-2.0.html
*/


define( 'S_VERSION', '1.0.0' );
define('S_PLUGIN_BASENAME', plugin_basename(__FILE__)); 
define('S_PLUGIN_URL', plugin_dir_url(__FILE__ ));
define('S_PLUGIN_DIR', plugin_dir_path(__FILE__ ));
define('S_PLUGIN_CLASS', plugin_dir_path(__FILE__).'class/');

register_activation_hook(__FILE__, array('Send24', 'plugin_activation'));
register_uninstall_hook(__FILE__, array('Send24', 'plugin_uninstall'));

require_once( S_PLUGIN_CLASS . 'class.send24.php' );

add_action( 'init', array( 'Send24', 'init' ));
