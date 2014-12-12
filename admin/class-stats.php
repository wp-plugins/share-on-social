<?php
defined( 'ABSPATH' ) or die( "Access denied !" );

/**
 * Class to display Sos Stats Charts
 *
 * @package share-on-social
 * @subpackage
 *
 * @since 1.0.0
 * @author Maithilish
 *        
 */
class Sos_Stats {

    /**
     * add actions
     *
     * @ since 1.0.0
     */
    public function setup () {
        add_action( 'admin_menu', 
                array(
                        $this,
                        'register_stats_page'
                ) );
        add_action( 'admin_init', 
                array(
                        $this,
                        'init_chart_scripts'
                ) );
    }

    /**
     * add Statistics submenu and register page to display
     *
     * @ since 1.0.0
     */
    public function register_stats_page () {
        $menu_label = __( 'Statistics', 'sos-domain' );
        $page_title = __( 'Share on Social Statistics', 'sos-domain' );
        $stats_page = 'sos_stats_page';
        $page_hook_suffix = add_submenu_page( 'edit.php?post_type=sos', 
                $page_title, $menu_label, 'administrator', $stats_page, 
                array(
                        &$this,
                        'render_stats_page'
                ) );
        /*
         * link scripts only on a specific administration screen. see example:
         * Link Scripts Only on a Plugin Administration Screen in
         * http://codex.wordpress.org/Function_Reference/wp_enqueue_script
         */
        add_action( 'admin_print_scripts-' . $page_hook_suffix, 
                array(
                        $this,
                        'enqueue_chart_scripts'
                ) );
    }

    /**
     * Render stats page.
     * Google Charts API renders charts in these div elements
     *
     * @ since 1.0.0
     */
    public function render_stats_page () {
        $heading = __( 'Share Stats', 'sos-domain' );
        echo <<<EOD
        <h3>{$heading}</h3>
        <div>&nbsp;</div>
        <div id="summary_chart"></div>
        <div>&nbsp;</div>
        <div id="stats_chart"></div>		        
EOD;
    }

    /**
     * register Google Charts API and plugins chart scripts
     *
     * @ since 1.0.0
     */
    public function init_chart_scripts () {
        /*
         * For now we depend on jquery loaded by the theme. We may have to load
         * google jquery in case of version mismatch !!!
         */
        $google_api = 'https://www.google.com/jsapi';
        wp_register_script( 'sos_google_api', $google_api, 
                array(
                        'jquery'
                ), null, false );
        wp_register_script( 'sos_chart_script', SOS_URL . '/js/chart.js' );
    }

    /**
     * enqueue and localize scripts
     *
     * @ since 1.0.0
     */
    public function enqueue_chart_scripts () {
        wp_enqueue_script( 'sos_google_api' );
        wp_enqueue_script( 'sos_chart_script' );
        wp_localize_script( 'sos_chart_script', 'sos_chart', 
                array(
                        'ajax_url' => admin_url( 'admin-ajax.php' ),
                        'stats_nonce' => wp_create_nonce( 'sos-get-stats' ),
                        'stats_summary_nonce' => wp_create_nonce( 
                                'sos-get-stats-summary' ),
                        'summary_title' => __( 'Summary', 'sos-domain' ),
                        'stats_title' => __( 'Shares', 'sos-domain' )
                ) );
    }
}
