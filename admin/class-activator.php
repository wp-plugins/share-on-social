<?php
defined( 'ABSPATH' ) or die( "Access denied !" );

/**
 * Class for tasks to do during plugin activation and deactivation phases
 *
 * @package share-on-social
 * @subpackage admin
 * @since 1.0.0
 * @author Maithilish
 *        
 */
class Sos_Activator {

    /**
     * registers activate and deactivate hooks
     *
     * @since 1.0.0
     */
    public function setup () {
        register_activation_hook( SOS_PLUGIN_FILE, 
                array(
                        $this,
                        'activate'
                ) );
        register_deactivation_hook( SOS_PLUGIN_FILE, 
                array(
                        $this,
                        'deactivate'
                ) );
        add_filter( 'plugin_action_links', 
                array(
                        $this,
                        'action_links'
                ), 10, 2 );
    }

    /**
     * activate hook calls activate_for_blog
     * for networkwide activation, calls method for each blog of the site
     * for blog level, calls method for the current blog
     *
     * @since 1.0.0
     */
    public function activate ( $networkwide ) {
        if ( function_exists( 'is_multisite' ) && is_multisite() ) {
            if ( $networkwide ) {
                if ( false == is_super_admin() ) {
                    return;
                }
                $blogs = wp_get_sites();
                foreach ( $blogs as $blog ) {
                    switch_to_blog( $blog[ 'blog_id' ] );
                    $this->activate_for_blog();
                    restore_current_blog();
                }
            } else {
                if ( false == current_user_can( 'activate_plugins' ) ) {
                    return;
                }
                $this->activate_for_blog();
            }
        } else {
            if ( false == current_user_can( 'activate_plugins' ) ) {
                return;
            }
            $this->activate_for_blog();
        }
    }

    /**
     * deletes and creates basic locker and add default settings
     *
     * @ since 1.0.0
     */
    public function activate_for_blog () {
        // if exists remove it
        $this->delete_basic_locker();
        $this->add_settings();
    }

    /**
     * deactivate hook - calls deactivate_for_blog
     * for networkwide deactivation, do nothing
     * for blog level, calls method for the current blog
     *
     * @since 1.0.0
     */
    public function deactivate ( $networkwide ) {
        if ( function_exists( 'is_multisite' ) && is_multisite() ) {
            if ( ! $networkwide ) {
                if ( false == current_user_can( 'activate_plugins' ) ) {
                    return;
                }
                $this->deactivate_for_blog();
            }
        } else {
            if ( false == current_user_can( 'activate_plugins' ) ) {
                return;
            }
            $this->deactivate_for_blog();
        }
    }

    public function deactivate_for_blog () {
        $this->delete_basic_locker();
    }

    /**
     * add Settings link to plugin action links.
     * Settings link is shown under the plugin in plugins page
     *
     * @since 1.0.0
     *       
     * @param array $links
     *            - list of links to show
     * @param string $file
     *            - basefile for the plugin such as
     *            share-on-social/share-on-social.php
     * @return array $links - modified list of links
     */
    public function action_links ( $links, $file ) {
        // add Settings link only to this plugin
        if ( SOS_PLUGIN_BASENAME == $file ) {
            $settings_link = '<a href="' . get_bloginfo( 'wpurl' ) .
                     '/wp-admin/edit.php?post_type=sos&page=sos_settings_page">Settings</a>';
            array_unshift( $links, $settings_link );
        }
        return $links;
    }

    /**
     * deletes the basic locker
     *
     * @since 1.0.0
     */
    public function delete_basic_locker () {
        $post_id = Sos_Helper::get_locker_post_id( 'basic' );
        if ( $post_id ) {
            wp_delete_post( $post_id );
        }
    }

    /**
     * adds default options
     *
     * @ since 1.0.0
     */
    public function add_settings () {
        $option_group = 'sos_common_options';
        $options = get_option( $option_group );
        if ( false == $options ) {
            $options = array(
                    'version' => SOS_VERSION
            );
            add_option( $option_group, $options );
        } else {
            $options[ 'version' ] = SOS_VERSION;
            update_option( $option_group, $options );
        }
    }
}