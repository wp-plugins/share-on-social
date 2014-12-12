<?php
defined( 'ABSPATH' ) or die( "Access denied !" );

/**
 * Plugin's ajax routines
 *
 * @package share-on-social
 * @subpackage admin
 * @since 1.0.0
 * @author Maithilish
 *        
 */
class Sos_Ajax {

    /**
     * register ajax methods
     *
     * @ since 1.0.0
     */
    public function setup () {
        // register ajax
        add_action( 'wp_ajax_nopriv_save-stats', 
                array(
                        $this,
                        'save_stats'
                ) );
        add_action( 'wp_ajax_save-stats', 
                array(
                        $this,
                        'save_stats'
                ) );
        add_action( 'wp_ajax_get-stats', 
                array(
                        $this,
                        'get_stats'
                ) );
        add_action( 'wp_ajax_get-stats-summary', 
                array(
                        $this,
                        'get_stats_summary'
                ) );
        // purge data older than 60 days
        $this->purge_data( 60 );
    }

    /**
     * save stat sent by client when shared by user
     *
     * @ since 1.0.0
     */
    public function save_stats () {
        $valid_req = check_ajax_referer( 'sos-save-stat', false, false );
        if ( false == $valid_req ) {
            wp_die( '-1' );
        }
        // check_ajax_referer('sos-save-stat');
        
        if ( ! isset( $_POST[ 'type' ] ) || ! isset( $_POST[ 'share_name' ] ) ) {
            wp_die( '-1' );
        }
        $share_on = $_POST[ 'type' ];
        $share_name = $_POST[ 'share_name' ];
        
        $this->update_sos_stats( $share_on, $share_name );
        $this->update_sos_stats_summary( $share_on, $share_name );
        
        wp_die( '1' );
    }

    /**
     * update share stat to wp_options table
     *
     * @ since 1.0.0
     *
     * @param string $share_on
     *            - name of social network
     * @param string $share_name
     *            - name of the share
     */
    public function update_sos_stats ( $share_on, $share_name ) {
        $stat = array(
                'date' => strtotime( 'now UTC' ), // UTC timestamp
                'type' => $share_on,
                'share_name' => $share_name
        );
        
        $stats = get_option( 'sos_stats' );
        if ( null == $stats ) {
            $stats = array();
        }
        $stats[] = $stat;
        update_option( 'sos_stats', $stats );
    }

    /**
     * update share summary to wp_options table
     *
     * @ since 1.0.0
     *
     * @param string $share_on
     *            - name of social network
     * @param string $share_name
     *            - name of the share
     */
    public function update_sos_stats_summary ( $share_on, $share_name ) {
        $stats = get_option( 'sos_stats_summary' );
        if ( false == isset( $stats[ $share_on ] ) ) {
            $stats[ $share_on ] = 0;
        }
        $stats[ $share_on ] += 1;
        update_option( 'sos_stats_summary', $stats );
    }

    /**
     * send stats to client
     *
     * @ since 1.0.0
     */
    public function get_stats () {
        $valid_req = check_ajax_referer( 'sos-get-stats', false, false );
        if ( false == $valid_req ) {
            wp_die( '-1' );
        }
        $data = $this->get_stats_json();
        wp_die( $data );
    }

    /**
     * send summary stats to client
     *
     * @ since 1.0.0
     */
    public function get_stats_summary () {
        $valid_req = check_ajax_referer( 'sos-get-stats-summary', false, false );
        if ( false == $valid_req ) {
            wp_die( '-1' );
        }
        $data = $this->get_stats_summary_json();
        wp_die( $data );
    }
    
    // @formatter:off
    /**
     * constructs data array as required by google charts api and 
     * returns it as json
     *
     * Array(
     *      [cols] => Array(
     *              [0] => Array([label] => Date,[type] => date)
     *              [1] => Array([label] => FB,[type] => number)
     *              .... 
     *      [rows] => Array(
     *              [0] => Array([c] =>Array(
     *                                  [0] => Array([v] => Date(2014,9,29))
     *                                  [1] => Array([v] => 2)
     *                                  [2] => Array([v] => 1)
     *                                  )                
     *              ....
     *
     * @ since 1.0.0
     *
     * @return string - encoded as json
     */
    // @formatter:on
    function get_stats_json () {
        // create col 0 - date
        $data = array();
        $data[ 'cols' ][] = array(
                'label' => __( 'Date', 'sos-domain' ),
                'type' => 'date'
        );
        
        // get stats and if no stats then return cols as json
        $stats = get_option( 'sos_stats' );
        if ( false == $stats ) {
            // add default cols
            $list = array(
                    'Facebook',
                    'GPlus',
                    'Twitter'
            );
            foreach ( $list as $value ) {
                $data[ 'cols' ][] = array(
                        'label' => $value,
                        'type' => 'number'
                );
            }
            return json_encode( $data );
        }
        
        // from stats get list of labels and add col 1 to n to data
        $labels = $this->get_labels( $stats );
        foreach ( $labels as $key => $value ) {
            $data[ 'cols' ][] = array(
                    'label' => $value,
                    'type' => 'number'
            );
        }
        
        $summarized_stats = $this->summarize_stats_by_date( $stats );
        
        /*
         * from day totals create and add rows to data
         */
        foreach ( $summarized_stats as $key => $value ) {
            $row = array();
            foreach ( $value as $key1 => $value1 ) {
                if ( 'date' == $key1 ) {
                    $year = date( 'Y', $value1 );
                    // JSON months zero indexed, so -1
                    $month = date( 'm', $value1 ) - 1;
                    $day = date( 'd', $value1 );
                    $row[][ 'v' ] = "Date($year,$month,$day)";
                } else {
                    $row[][ 'v' ] = $value1;
                }
            }
            $data[ 'rows' ][][ 'c' ] = $row;
        }
        return json_encode( $data );
    }

    /**
     * summarize the stats by date
     *
     * @ since 1.0.0
     *
     * @param array $stats            
     * @return array - by date summarized stats
     */
    function summarize_stats_by_date ( $stats ) {
        // from stats construct array of day totals
        $all_types = $this->get_labels( $stats );
        $summarized_stats = array();
        foreach ( $stats as $key => $value ) {
            $date = date( 'M d', $value[ 'date' ] );
            $type = $value[ 'type' ];
            if ( ! isset( $summarized_stats[ $date ] ) ) {
                $summarized_stats[ $date ] = array(
                        'date' => $value[ 'date' ]
                );
                // init cells for all types using list of labels
                foreach ( $all_types as $types_key => $label ) {
                    $summarized_stats[ $date ][ $types_key ] = 0;
                }
            }
            $summarized_stats[ $date ][ $type ] += 1;
        }
        return $summarized_stats;
    }

    /**
     * summary stats as json
     *
     * @ since 1.0.0
     *
     * @return string - encoded as json
     */
    function get_stats_summary_json () {
        // add cols to data
        $data = array();
        $data[ 'cols' ][] = array(
                'label' => __( 'Share On', 'sos-domain' ),
                'type' => 'string'
        );
        $data[ 'cols' ][] = array(
                'label' => __( 'Total', 'sos-domain' ),
                'type' => 'number'
        );
        
        // get summary stats and if no found then return just cols
        $summary = get_option( 'sos_stats_summary' );
        if ( false == $summary ) {
            return json_encode( $data );
        }
        
        // from summary create and add rows to data
        foreach ( $summary as $key => $value ) {
            $row = array();
            $row[][ 'v' ] = $this->get_label( $key );
            $row[][ 'v' ] = $value;
            $data[ 'rows' ][][ 'c' ] = $row;
        }
        
        return json_encode( $data );
    }

    /**
     * purges stats from array where stat is older than retention period
     *
     * @ since 1.0.0
     *
     * @param int $data_retention_period
     *            - how many days stats has to be retained in options table
     */
    function purge_data ( $data_retention_period ) {
        $purge_date = $this->get_purge_date( $data_retention_period );
        $stats = get_option( 'sos_stats' );
        if ( false == $stats ) {
            return;
        }
        $purged_stats = array();
        foreach ( $stats as $key => $stat ) {
            if ( $stat[ 'date' ] > $purge_date ) {
                $purged_stats[] = $stat;
            }
        }
        update_option( 'sos_stats', $purged_stats );
    }

    /**
     * get purge date
     *
     * @ since 1.0.0
     *
     * @param int $data_retention_period
     *            - number of days
     * @return number - timestamp of purge date
     */
    function get_purge_date ( $data_retention_period ) {
        // UTC timestamp round off to hour zero
        $today = strtotime( 'today UTC' );
        // substract number of days
        $purge_date = $today - ($data_retention_period * 86400);
        return $purge_date;
    }

    /**
     * get list of unique social networks from the saved stats
     *
     * @ since 1.0.0
     *
     * @param array $stats            
     * @return array - list of unique social networks
     */
    function get_labels ( $stats ) {
        $labels = array();
        foreach ( $stats as $key => $value ) {
            $labels[ $value[ 'type' ] ] = $this->get_label( $value[ 'type' ] );
        }
        return $labels;
    }

    /**
     * get readable label for socail networks
     *
     * @ since 1.0.0
     *
     * @param string $type
     *            - social network type like fb, gplus etc.,
     * @return string - label like Facebook, GPlus etc.,
     */
    function get_label ( $type ) {
        if ( 'fb' == $type ) {
            return 'FB';
        }
        if ( 'gplus' == $type ) {
            return 'GPlus';
        }
        if ( 'twitter' == $type ) {
            return 'Twitter';
        }
        return 'undefined';
    }
}