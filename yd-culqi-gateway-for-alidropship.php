<?php
/**
 *	Plugin Name: YD Culqi gateway for AliDropship
 *	Plugin URI: https://alidropship.com/
 *	Description: YD Culqi gateway for AliDropship
 *	Version: 2.0
 *	Text Domain: cg
 *	Author: Vitaly Kukin, Artem Gusev, George Murdasov
 *	Author URI: https://yellowduck.me/
 *  License: MIT
 *  License URI: http://www.opensource.org/licenses/mit-license.php
 */

if( ! defined('CG_VERSION') ) define( 'CG_VERSION', '1.0' );
if( ! defined('CG_PATH') )    define( 'CG_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Localization
 */
function cg_lang_init() {
	
	load_plugin_textdomain('cg');
}
add_action( 'init', 'cg_lang_init' );

if( is_admin() ) :

    register_activation_hook( __FILE__, 'cg_lang_init' );
	register_activation_hook( __FILE__, 'cg_install' );
	register_activation_hook( __FILE__, 'cg_activate' );

endif;

require( CG_PATH . 'core/init.php' );
