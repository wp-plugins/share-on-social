<?php
defined( 'ABSPATH' ) or die( "Access denied !" );

require_once SOS_PATH . 'admin/class-sos.php';
require_once SOS_PATH . 'admin/class-options.php';
require_once SOS_PATH . 'admin/class-help.php';
require_once SOS_PATH . 'admin/class-stats.php';

/**
 * Class to load admin modules
 *
 * @package share-on-social
 * @subpackage admin
 * @since 1.0.0
 * @author Maithilish
 *        
 */
class Sos_Admin {

    /**
     * initializes the admin modules
     *
     * @since 1.0.0
     */
    function setup () {
        $sos = new Sos();
        $sos->setup();
        
        $sos_options = new Sos_Options();
        $sos_options->setup();
        
        $sos_ajax = new Sos_Ajax();
        $sos_ajax->setup();
        
        $sos_help = new Sos_Help();
        $sos_help->setup();
        
        $sos_stats = new Sos_Stats();
        $sos_stats->setup();
        
        $options = get_option( SOS_NAME );
    }
}