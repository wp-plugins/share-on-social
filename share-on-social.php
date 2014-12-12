<?php

/**
 * Plugin Name: Share on Social 
 * Plugin URI: https://wordpress.org/plugins/share-on-social/ 
 * Description: Adds share locker to hide the content until user shares the blog on social network. Share is posted to user's wall. 
 * Version: 1.0.0 
 * Author: Maithilish 
 * Text Domain: sos-domain 
 * Author URI: http://www.codedrops.in/about 
 * License: GPLv2
 */
defined( 'ABSPATH' ) or die( "Access denied !" );

/**
 *
 * @var define constants
 */
define( 'SOS_NAME', 'share-on-social' );
define( "SOS_VERSION", '1.0.0' );
// define( 'SOS_DEV', true );

/**
 *
 * @var plugin URL
 */
define( "SOS_URL", trailingslashit( plugin_dir_url( __FILE__ ) ) );

// plugin path
/**
 *
 * @var plugin path
 */
define( "SOS_PATH", plugin_dir_path( __FILE__ ) );

/**
 *
 * @var basename share-on-social/share-on-social.php
 */
define( "SOS_PLUGIN_BASENAME", plugin_basename( __FILE__ ) );

/**
 *
 * @var plugin main file - share-on-social.php
 */
define( "SOS_PLUGIN_FILE", __FILE__ );

/**
 *
 * @var text domain
 */
define( "TD", 'sos-domain' );

// entry point
setup_sos_plugin();

/**
 * init plugin either in admin mode or as frontend
 *
 * @since 1.0.0
 */
function setup_sos_plugin () {
    /*
     * setup includes the required files but defers the actual loading to
     * plugins_loaded action. This ensures that tests are run in isolation but
     * required files included for testing. In test env, frontend files are
     * included by default as is_admin() returns false
     */
    require_once SOS_PATH . 'include/class-helper.php';
    
    load_plugin_textdomain( 'sos-domain', false, 'share-on-social/langs' );
    
    add_action( 'shutdown', 'Sos_Helper::output_debug_data' );
    
    if ( is_admin() ) {
        
        require_once SOS_PATH . 'admin/class-activator.php';
        $sos_activator = new Sos_Activator();
        $sos_activator->setup();
        
        // ajax runs in admin context
        require_once SOS_PATH . 'admin/class-ajax.php';
        $sos_ajax = new Sos_Ajax();
        $sos_ajax->setup();
        
        require_once SOS_PATH . 'admin/class-admin.php';
        add_action( 'plugins_loaded', 'load_sos_admin' );
    } else {
        require_once SOS_PATH . 'frontend/class-frontend.php';
        add_action( 'plugins_loaded', 'load_sos_frontend' );
    }
}

/**
 * load and setup Sos_Admin class
 *
 * @ since 1.0.0
 */
function load_sos_admin () {
    if ( get_option( 'sos_test' ) ) {
        return;
    }
    $sos_admin = new Sos_Admin();
    $sos_admin->setup();
}

/**
 * load and setup Sos_Frondend class
 *
 * @ since 1.0.0
 */
function load_sos_frontend () {
    if ( get_option( 'sos_test' ) ) {
        return;
    }
    $sos_frontend = new Sos_Frontend();
    $sos_frontend->setup();
}
