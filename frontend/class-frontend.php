<?php
defined( 'ABSPATH' ) or die( "Access denied !" );

/**
 * Plugins frontend
 *
 * @package share-on-social
 * @subpackage frontend
 *            
 * @since 1.0.0
 * @author Maithilish
 *        
 */
class Sos_Frontend {

    /**
     *
     * @var shortcode name
     */
    var $sos_shortcode = 'share-on-social';

    /**
     * add actions, filters and shortcode
     *
     * @ since 1.0.0
     */
    public function setup () {
        // add scripts - added to the header
        add_action( 'wp_enqueue_scripts', 
                array(
                        $this,
                        'add_sos_scripts'
                ) );
        
        // enable shortcode
        add_shortcode( $this->sos_shortcode, 
                array(
                        $this,
                        'enable_sos_shortcode'
                ) );
    }

    /**
     * callback to process shortcode.
     * locker and css style is added to contents of the shortcode to hide it
     *
     * @ since 1.0.0
     *
     * @param array $atts
     *            properties set in shortcode by user - id,share name and share
     *            target (link)
     * @param string $content
     *            content of the shortcode
     * @return string - contents with the locker and css
     */
    function enable_sos_shortcode ( $atts, $content ) {
        extract( 
                shortcode_atts( 
                        array(
                                'id' => 'basic',
                                'name' => false,
                                'link' => false
                        ), $atts ) );
        
        global $post;
        $locker_id = $id;
        
        // get share properties for locker overridden with userdefined name,link
        $share_props = $this->get_share_props( $locker_id, $name, $link );
        
        $locker = $this->get_locker( $locker_id );
        
        $this->collect_debug_data( $id, $name, $link, $post, $share_props, 
                $locker );
        
        // process shortcodes in the content
        $processed_content = do_shortcode( $content );
        $share_locker = $this->construct_share_lockers_html( $locker, 
                $share_props );
        
        $locked_content = $this->get_locked_content( 
                $share_props[ 'share_name' ], $processed_content, $share_locker );
        return $locked_content;
    }

    /**
     * get share properties for a locker id.
     * incase name or link are defined by user then override properties with
     * userdefined name or link
     *
     * @ since 1.0.0
     *
     * @param string $locker_id            
     * @return array - share properties
     */
    public function get_share_props ( $locker_id, $name, $link ) {
        // sanitization in done in sanitized_share_props
        $locker_share_target = $this->get_meta_data( $locker_id, 
                'share_target' );
        // get the post id of share target
        $share_target_id = $this->get_share_target_id( $locker_share_target );
        // set share target
        if ( 0 == $share_target_id ) {
            $share_target = get_site_url();
        } else {
            $share_target = get_permalink( $share_target_id );
        }
        
        // share name defaults to name of the post that holds the locker
        $share_name = get_post()->post_name;
        
        // override share name and share target with userdefined values
        if ( ! empty( $name ) ) {
            $share_name = $name;
        }
        if ( ! empty( $link ) ) {
            $share_target = $link;
            $share_target_id = 'na';
        }
        
        return $this->sanitize_share_props( $share_name, $share_target, 
                $share_target_id );
    }

    /**
     *
     *
     * @ since 1.0.0
     *
     * @param string $share_name            
     * @param string $share_target            
     * @param int $share_target_id
     *            post/page id of the share target
     * @return array - share properties array
     */
    public function sanitize_share_props ( $share_name, $share_target, 
            $share_target_id ) {
        $share_name = sanitize_key( $share_name );
        $share_target = esc_url( $share_target, 
                array(
                        'http',
                        'https'
                ) );
        $share_props = array(
                'share_name' => $share_name,
                'share_target' => $share_target,
                'share_target_id' => $share_target_id
        );
        return $share_props;
    }

    /**
     * get post/page id depending on the locker share target (page/parent/site)
     *
     * @ since 1.0.0
     *
     * @param string $locker_share_target
     *            page|parent|site
     * @return number - page/post id
     */
    public function get_share_target_id ( $locker_share_target ) {
        global $post;
        
        $share_target_id = $post->ID; // defaults to page
        
        if ( 'parent' == $locker_share_target ) {
            $post_id = $post->ID;
            $parent_id = $post->post_parent;
            if ( $parent_id == $post_id ) { // top level page
                $share_target_id = 0; // site
            } else {
                $share_target_id = $parent_id; // series top page
            }
        }
        if ( 'site' == $locker_share_target ) {
            $share_target_id = 0; // site
        }
        return $share_target_id;
    }

    /**
     * get locker properties
     *
     * @ since 1.0.0
     *
     * @param string $locker_id            
     * @return array - locker properties as an array
     */
    function get_locker ( $locker_id ) {
        $locker_text = $this->get_meta_data( $locker_id, 'locker_text' );
        $share_target = $this->get_meta_data( $locker_id, 'share_target' );
        $shareon_fb = $this->get_meta_data( $locker_id, 'shareon_fb' );
        $shareon_gplus = $this->get_meta_data( $locker_id, 'shareon_gplus' );
        $shareon_twitter = $this->get_meta_data( $locker_id, 'shareon_twitter' );
        $locker = array(
                'locker_id' => $locker_id,
                'share_target' => $share_target,
                'shareon_fb' => $shareon_fb,
                'shareon_gplus' => $shareon_gplus,
                'shareon_twitter' => $shareon_twitter,
                'locker_text' => $locker_text
        );
        return $locker;
    }

    /**
     * construct share locker html snippet
     *
     * @ since 1.0.0
     *
     * @param array $locker
     *            - locker properties
     * @param unknown $share_props
     *            - share properties
     * @return string - locker as html string
     */
    function construct_share_lockers_html ( $locker, $share_props ) {
        $prefix = rand( 1, 99 );
        if ( get_option( 'sos_test' ) ) {
            $prefix = 'test-prefix';
        }
        
        $share_name = $share_props[ 'share_name' ];
        $share_target = $share_props[ 'share_target' ];
        $options = get_option( 'sos_common_options' );
        
        $shareon = 'fb';
        $icon = SOS_URL . '/images/fb-48.png';
        $enabled = $locker[ 'shareon_fb' ];
        empty( $options[ 'fbappid' ] ) ? $appid_set = false : $appid_set = true;
        $fb_share_button = $this->get_share_button( $shareon, $share_name, 
                $icon, $enabled, $appid_set, $prefix );
        
        $shareon = 'gplus';
        $icon = SOS_URL . '/images/gplus-48.png';
        $enabled = $locker[ 'shareon_gplus' ];
        empty( $options[ 'gplusclientid' ] ) ? $appid_set = false : $appid_set = true;
        $gplus_share_button = $this->get_share_button( $shareon, $share_name, 
                $icon, $enabled, $appid_set, $prefix );
        
        $shareon = 'twitter';
        $icon = null; // no icon for twitter
        $enabled = $locker[ 'shareon_twitter' ];
        $appid_set = true; // no appid for twitter
        $twitter_share_button = $this->get_share_button( $shareon, $share_name, 
                $icon, $enabled, $appid_set, $prefix );
        
        $locker = <<<EOD
<div>{$locker['locker_text']}</div>
<div id="fb-root"></div>                	
<div class="share-lockers">
    {$fb_share_button}
    <span class="spacer"></span>
    {$gplus_share_button}
    <span class="spacer"></span>
    {$twitter_share_button}
</div>
<div class="clear"></div>        
<input type="hidden" class="share_name" value="{$share_name}">
<input type="hidden" id="{$share_name}_{$prefix}_share_target" 
                        value="{$share_target}">
EOD;
        return trim( $locker );
    }

    /**
     * construct share button
     *
     * @ since 1.0.0
     *
     * @param string $shareon
     *            - social network name
     * @param string $share_name
     *            - share name
     * @param string $icon
     *            - icon for the button
     * @param boolean $enabled
     *            - whether enabled in the locker
     * @param boolean $appid_set
     *            - whether app id is set
     * @param int $prefix
     *            - random prefix to distinguish multiple buttons
     * @return string - button html snippet
     */
    function get_share_button ( $shareon, $share_name, $icon, $enabled, 
            $appid_set, $prefix ) {
        $share_button = "";
        if ( $enabled && $appid_set ) {
            $share_button = $this->get_share_span( $share_name, $icon, $prefix, 
                    $shareon );
        }
        return $share_button;
    }

    /**
     * construct span element for the share button
     *
     * @ since 1.0.0
     *
     * @param string $share_name
     *            - share name
     * @param string $icon
     *            - icon for the button
     * @param int $prefix
     *            - random prefix to distinguish multiple buttons
     * @param string $type
     *            - social network name like fb/gplus etc.,
     * @return string - span html snippet
     */
    public function get_share_span ( $share_name, $icon, $prefix, $type ) {
        /*
         * sos supports multiple share buttons in a page and to support this we
         * do : span id is set to {$type}_{$prefix}-$share_name and class to
         * {$type}-{$share_name}
         */
        (null == $icon) ? $img_element = "" : $img_element = '<img src="' . $icon .
                 '" style="display: inline-block;" >';
        $share_span = <<<EOD
<span id="{$type}_{$prefix}-$share_name" class="share-locker {$type}-{$share_name}">
    {$img_element}
</span>
EOD;
        return trim( $share_span );
    }

    /**
     * wrap content in css style to hide the content in browser
     *
     * @ since 1.0.0
     *
     * @param string $share_name            
     * @param string $content            
     * @param array $share_locker            
     * @return string - wrapped content
     */
    public function get_locked_content ( $share_name, $content, $share_locker ) {
        $prefix = rand( 1, 99 ); // for multiple lockers of a name
        if ( get_option( 'sos_test' ) ) {
            $prefix = 'test-prefix'; // constant for unit test
        }
        // content and the locker
        $locked_content = <<<EOD
        <div id="{$share_name}-sos-content-$prefix"
             class="sos-hide {$share_name}-sos-content" >
            $content
        </div>
        <div id="{$share_name}-sos-locker-$prefix"
             class="{$share_name}-sos-locker" >
            $share_locker
        </div>
EOD;
        return trim( $locked_content );
    }

    /**
     * get locker metadata for a key
     *
     * @ since 1.0.0
     *
     * @param string $locker_id            
     * @param string $key            
     * @return string - metadata
     */
    function get_meta_data ( $locker_id, $key ) {
        $post_id = Sos_Helper::get_locker_post_id( $locker_id );
        if ( isset( $post_id ) ) {
            return get_post_meta( $post_id, $key, true );
        } else {
            return '';
        }
    }

    /**
     * register and enqueue scripts
     *
     * @ since 1.0.0
     */
    function add_sos_scripts () {
        global $post;
        
        if ( false == isset( $post ) ) {
            return;
        }
        
        wp_register_style( 'sos_style', SOS_URL . '/css/style.css' );
        wp_register_script( 'sos_script', SOS_URL . '/js/share.js', 
                array(
                        'jquery'
                ), '', true );
        
        $options = get_option( 'sos_common_options' );
        
        // enqueue script only if shortcode is present in post/page
        if ( has_shortcode( $post->post_content, $this->sos_shortcode ) ) {
            
            if ( defined( 'SOS_DEV' ) ) {
                if ( false == get_option( 'sos_test' ) ) {
                    define( 'DONOTCACHEPAGE', true );
                    $random = rand();
                    defined( 'DONOTCACHEPAGE' ) ? $page_cache = 'disabled' : $page_cache = 'enabled';
                    echo 'SOS Dev DEBUG : [ page cache :' . $page_cache .
                             '] [ random :' . $random . ']';
                }
            }
            
            // enqueue script, css
            wp_enqueue_style( 'sos_style' );
            wp_enqueue_script( 'sos_script' );
            
            // enqueue social sdks
            if ( false == empty( $options[ 'fbappid' ] ) ) {
                $fb_app_id = $options[ 'fbappid' ];
                $fb_sdk = "http://connect.facebook.net/en_US/all.js#xfbml=1&appId=$fb_app_id&version=v2.0";
                wp_enqueue_script( 'sos_script_fb', $fb_sdk, 
                        array(
                                'jquery'
                        ), '', FALSE );
            }
            
            $gplus_client_id = "";
            if ( false == empty( $options[ 'gplusclientid' ] ) ) {
                $gplus_client_id = $options[ 'gplusclientid' ];
                $gplus_sdk = 'https://apis.google.com/js/plusone.js';
                wp_enqueue_script( 'sos_script_gplus', $gplus_sdk, 
                        array(
                                'jquery'
                        ), '', FALSE );
            }
            
            $twitter_sdk = 'https://platform.twitter.com/widgets.js';
            wp_enqueue_script( 'twitter', $twitter_sdk, '', '', FALSE );
            
            // localize to pass some
            wp_localize_script( 'sos_script', 'sos_data', 
                    array(
                            'ajax_url' => admin_url( 'admin-ajax.php' ),
                            'nonce' => wp_create_nonce( 'sos-save-stat' ),
                            'gplus_client_id' => $gplus_client_id
                    ) );
        }
    }

    /**
     * if debug is enabled in settings, then collect debug info
     *
     * @ since 1.0.0
     *
     * @param string $id
     *            - locker id
     * @param string $name
     *            - share name
     * @param string $link
     *            - share target
     * @param object $post
     *            - WP_POST object for the current post
     * @param array $share_props
     *            - share properties
     * @param array $locker
     *            - locker properties
     */
    function collect_debug_data ( $id, $name, $link, $post, $share_props, 
            $locker ) {
        if ( false == Sos_Helper::is_debug_enabled() ) {
            return;
        }
        $user_defined = array(
                'locker id' => $id,
                'name' => $name,
                'link' => $link
        );
        $post_props = array(
                'id' => $post->ID,
                'name' => $post->post_name,
                'type' => $post->post_type,
                'permalink' => get_permalink( $post->ID ),
                'parent' => $post->post_parent
        );
        // add all to debug_data array
        $debug_data = array(
                'user defined' => $user_defined,
                'locker' => $locker,
                'post attributes' => $post_props,
                'share attributes' => $share_props
        );
        Sos_Helper::collect_debug_data( $debug_data );
    }
}
