<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

sos_uninstall_sos_plugin();

/**
 * tasks to do on plugin uninstall
 *
 * @ since 1.0.0
 */
function sos_uninstall_sos_plugin () {
    if ( function_exists( 'is_multisite' ) && is_multisite() ) {
        if ( false == is_super_admin() ) {
            return;
        }
        $blogs = wp_get_sites();
        foreach ( $blogs as $blog ) {
            switch_to_blog( $blog[ 'blog_id' ] );
            sos_delete_sos_posts();
            sos_delete_sos_options();
            restore_current_blog();
        }
    } else {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }
        sos_delete_sos_posts();
        sos_delete_sos_options();
    }
}

/**
 * delete all posts of custom type sos
 *
 * @ since 1.0.0
 */
function sos_delete_sos_posts () {
    // in multisite uninstall, wp_delete_post access post_type_object and to
    // avoid access on non object we register sos post type
    register_post_type( 'sos' );
    $args = array(
            'post_type' => 'sos'
    );
    
    $query = new WP_Query( $args );
    while ($query->have_posts()) {
        $query->the_post();
        if ( 'sos' == get_post_type() ) {
            $post_id = get_the_ID();
            wp_delete_post( $post_id, true );
        }
    }
    wp_reset_postdata();
}

/**
 * delete all sos related options
 *
 * @ since 1.0.0
 */
function sos_delete_sos_options () {
    $option_name = 'sos_common_options';
    delete_option( $option_name );
    
    $option_name = 'sos_stats';
    delete_option( $option_name );
    
    $option_name = 'sos_stats_summary';
    delete_option( $option_name );
}