<?php
defined( 'ABSPATH' ) or die( "Access denied !" );

/**
 * Helper and Util functions
 *
 * @package share-on-social
 * @subpackage include
 * @since 1.0.0
 * @author Maithilish
 *        
 */
class Sos_Helper {

    /**
     * whether debug is enabled in common settings
     *
     * @ since 1.0.0
     *
     * @return boolean
     */
    static function is_debug_enabled () {
        $options = get_option( 'sos_common_options' );
        if ( isset( $options[ 'sos_debug' ] ) && $options[ 'sos_debug' ] ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * static function to collect debug data
     *
     * @ since 1.0.0
     *
     * @param array $debug_data            
     */
    static function collect_debug_data ( $debug_data ) {
        global $sos_debug_data;
        if ( null == $sos_debug_data ) {
            /**
             */
            $sos_debug_data = array();
        }
        $sos_debug_data = array_merge( $sos_debug_data, 
                array(
                        $debug_data
                ) );
    }

    /**
     * ouput debug data array.
     * text is wrapped as CDATA so that it is not discarded during minifiy
     *
     * @ since 1.0.0
     */
    static function output_debug_data () {
        global $sos_debug_data;
        if ( false == Sos_Helper::is_debug_enabled() ) {
            return;
        }
        if ( 0 == count( $sos_debug_data ) ) {
            return;
        }
        echo PHP_EOL;
        echo '<!--' . PHP_EOL;
        echo '<![CDATA[' . PHP_EOL;
        echo PHP_EOL;
        echo 'Plugin [' . SOS_NAME . '] Version [' . SOS_VERSION . ']' . PHP_EOL;
        echo PHP_EOL;
        echo 'Debug info [ disable debug in production site !!! ]' . PHP_EOL;
        echo PHP_EOL;
        echo print_r( $sos_debug_data, true );
        echo PHP_EOL;
        echo ']]>' . PHP_EOL;
        echo ' -->' . PHP_EOL;
    }

    /**
     * get post id of the locker (sos custom post type)
     * WP loop has some side effects so we use wpdb query to get the post id
     *
     * @ since 1.0.0
     *
     * @param string $locker_id            
     * @return int - post id of the locker
     */
    static function get_locker_post_id ( $locker_id ) {
        $post_id = null;
        global $wpdb;
        $sql = $wpdb->prepare( 
                "select p.id from $wpdb->postmeta m, $wpdb->posts p " .
                         "where m.post_id = p.id and p.post_type = 'sos' " .
                         "and meta_key = 'locker_id' and meta_value = %s ", 
                        $locker_id );
        $results = $wpdb->get_results( $sql, ARRAY_A );
        foreach ( $results as $result ) {
            if ( isset( $result[ 'id' ] ) ) {
                $post_id = $result[ 'id' ];
            }
        }
        return $post_id;
    }
}