<?php
defined( 'ABSPATH' ) or die( "Access denied !" );

/**
 * Class to manage Sos Common Settings through WP Options API
 *
 * @package share-on-social
 * @subpackage admin
 * @since 1.0.0
 * @author Maithilish
 *        
 */
class Sos_Options {

    /**
     * setup actions for options menu and options page
     *
     * @since 1.0.0
     */
    public function setup () {
        add_action( 'admin_init', 
                array(
                        $this,
                        'init_common_options'
                ) );
        add_action( 'admin_menu', 
                array(
                        $this,
                        'register_settings_page'
                ) );
    }

    /**
     * add Settings submenu to Sos menu and attach options page to it
     *
     * @since 1.0.0
     */
    public function register_settings_page () {
        $page_title = __( 'Common Options', 'sos-domain' );
        $menu_label = __( 'Settings', 'sos-domain' );
        $setting_page = 'sos_settings_page';
        add_submenu_page( 'edit.php?post_type=sos', $page_title, $menu_label, 
                'administrator', $setting_page, 
                array(
                        &$this,
                        'render_settings_page'
                ) );
    }

    /**
     * render form to accept options.
     * uses wp functions to add options fields stored in sos_common_option
     *
     * @since 1.0.0
     */
    public function render_settings_page () {
        $heading = __( 'Common Options', 'sos-domain' );
        $desc = __( 'These options are applied to all lockers.', 'sos-domain' );
        $form = <<<EOD
		<div class="wrap">
		<div id="icon-tools" class="icon32"></div>
		<h2>$heading</h2>
		</div>
		<div>$desc</div>		
		<form method="post" action="options.php" style="width: 80%;">
EOD;
        echo $form;
        settings_fields( 'sos_common_options' );
        do_settings_sections( 'sos_common_options' );
        submit_button();
        echo "</form>";
    }

    /**
     * init wp options group and section and add options fields to it
     *
     * @since 1.0.0
     */
    public function init_common_options () {
        $option_group = 'sos_common_options';
        $section = 'sos_common_options_section';
        
        // no options - create them.
        if ( false == get_option( $option_group ) ) {
            add_option( $option_group );
        }
        
        $options = get_option( $option_group );
        
        add_settings_section( $section, '', '', $option_group );
        
        $label = __( 'Facebook App ID', 'sos-domain' );
        $id = 'fbappid';
        if ( false == isset( $options[ $id ] ) ) {
            $options[ $id ] = '';
        }
        add_settings_field( $id, $label, 
                array(
                        &$this,
                        'textinput'
                ), $option_group, $section, 
                array(
                        'id' => $id,
                        'name' => $option_group . '[' . $id . ']',
                        'value' => $options[ $id ]
                ) );
        
        $label = __( 'Google Plus Client ID', 'sos-domain' );
        $id = 'gplusclientid';
        if ( false == isset( $options[ $id ] ) ) {
            $options[ $id ] = '';
        }
        add_settings_field( $id, $label, 
                array(
                        &$this,
                        'textinput'
                ), $option_group, $section, 
                array(
                        'id' => $id,
                        'name' => $option_group . '[' . $id . ']',
                        'value' => $options[ $id ]
                ) );
        
        $label = __( 'Debug', 'sos-domain' );
        $id = 'sos_debug';
        if ( false == isset( $options[ $id ] ) ) {
            $options[ $id ] = '';
        }
        add_settings_field( $id, $label, 
                array(
                        &$this,
                        'checkbox'
                ), $option_group, $section, 
                array(
                        'id' => $id,
                        'name' => $option_group . '[' . $id . ']',
                        'value' => $options[ $id ]
                ) );
        
        register_setting( $option_group, $option_group, 
                array(
                        &$this,
                        'sanitize_options'
                ) );
    }

    /**
     * helper function.
     * returns html input element
     *
     * @since 1.0.0
     * @param array $args            
     * @param boolean $echo
     *            - whether to echo or return
     * @return string - html string for text input field
     */
    function textinput ( $args, $echo = true ) {
        $html = '<input class="text" type="text" id="' . $args[ 'id' ] .
                 '" name="' . $args[ 'name' ] . '" size="30" value="' .
                 $args[ 'value' ] . '"/>';
        if ( $echo ) {
            echo $html;
        } else {
            return $html;
        }
    }

    /**
     * helper function.
     * returns html checkbox element
     *
     * @since 1.0.0
     * @param array $args            
     * @param boolean $echo
     *            - echo or return
     * @return string - html string for checkbox
     *        
     */
    function checkbox ( $args, $echo = true ) {
        $checked = false;
        if ( isset( $args[ 'value' ] ) && 1 == $args[ 'value' ] ) {
            $checked = true;
        }
        $html = '<input type="checkbox" id="' . $args[ 'id' ] . '" name="' .
                 $args[ 'name' ] . '" value="1" ' .
                 checked( $checked, true, false ) . '/>';
        if ( $echo ) {
            echo $html;
        } else {
            return $html;
        }
    }

    /**
     * sanitizes the option values before they are saved to db
     * input holds options as key/value pairs
     *
     * @ since 1.0.0
     *
     * @param unknown $input            
     * @return array - sanitized copy of input array
     */
    function sanitize_options ( $input ) {
        $output = array();
        foreach ( $input as $key => $val ) {
            if ( isset( $input[ $key ] ) ) {
                // $output[$key] = strip_tags(stripslashes($input[$key]));
                $output[ $key ] = wp_filter_nohtml_kses( $input[ $key ] );
            }
        }
        return apply_filters( 'sanitize_options', $output, $input );
    }
}
