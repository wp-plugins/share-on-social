<?php
defined( 'ABSPATH' ) or die( "Access denied !" );

/**
 * adds custom post type sos to wordpress
 *
 * @package share-on-social
 * @subpackage admin
 * @since 1.0.0
 * @author Maithilish
 *        
 */
class Sos {

    /**
     *
     * @var string - name of custom type
     */
    var $post_type = 'sos';

    /**
     *
     * @var array
     */
    var $messages;

    /**
     * add actions and filters
     *
     * @ since 1.0.0
     */
    public function setup () {
        add_action( 'admin_notices', 
                array(
                        $this,
                        'sos_admin_notice'
                ) );
        add_action( 'init', 
                array(
                        $this,
                        'create_post_type'
                ) );
        add_action( 'save_post', 
                array(
                        $this,
                        'save_post'
                ) );
        add_action( 'admin_head', 
                array(
                        $this,
                        'hide_minor_publish'
                ) );
        add_filter( 'post_updated_messages', 
                array(
                        $this,
                        'filter_published_message'
                ) );
        add_filter( 'manage_edit-' . $this->post_type . '_columns', 
                array(
                        $this,
                        'set_post_columns'
                ) );
        add_action( 'manage_posts_custom_column', 
                array(
                        $this,
                        'render_post_columns'
                ) );
    }

    /**
     * display invalid/duplicate locker message when locked added/updated
     *
     * @ since 1.0.0
     */
    function sos_admin_notice () {
        $duplicate_id = get_transient( 'sos_duplicate_id' );
        delete_transient( 'sos_duplicate_id' );
        if ( $duplicate_id ) {
            $message1 = __( 
                    'Duplicate Locker ID! Save the locker with an unique Locker ID.', 
                    'sos-domain' );
        }
        $invalid_id = get_transient( 'sos_invalid_id' );
        delete_transient( 'sos_invalid_id' );
        if ( $invalid_id ) {
            $message1 = __( 'Invalid Locker ID!', 'sos-domain' );
        }
        $message2 = __( 'Unable to activate locker.', 'sos-domain' );
        if ( $duplicate_id || $invalid_id ) {
            $html = <<<EOD
   <div class="error"><p>{$message1} {$message2}</p></div>
EOD;
        } else {
            $html = '';
        }
        echo $html;
    }

    /**
     * register custom post type
     *
     * @ since 1.0.0
     */
    public function create_post_type () {
        register_post_type( $this->post_type, 
                array(
                        'labels' => array(
                                'name' => _x( 'Sos Lockers', 
                                        'post type general name', 'sos-domain' ),
                                'singular_name' => _x( 'Sos Locker', 
                                        'post type singular name', 'sos-domain' ),
                                'menu_name' => _x( 'Share on Social', 
                                        'admin menu', 'sos-domain' ),
                                'add_new' => _x( 'Add New', 'sos', 
                                        'sos-domain' ),
                                'add_new_item' => __( 'Add New Locker', 
                                        'sos-domain' ),
                                'edit' => __( 'Edit', 'sos-domain' ),
                                'edit_item' => __( 'Edit Locker', 'sos-domain' ),
                                'new_item' => __( 'New Locker', 'sos-domain' ),
                                'all_items' => __( 'Lockers', 'sos-domain' ),
                                'view' => '',
                                'view_item' => ''
                        ),
                        
                        'public' => false,
                        'show_ui' => true,
                        'menu_position' => 15,
                        'supports' => array(
                                'title'
                        ),
                        'taxonomies' => array(
                                ''
                        ),
                        'menu_icon' => SOS_URL . '/images/sos-icon.png',
                        'has_archive' => true,
                        'register_meta_box_cb' => array(
                                $this,
                                'setup_meta_boxes'
                        )
                ) );
        if ( null == Sos_Helper::get_locker_post_id( 'basic' ) ) {
            $this->create_basic_locker();
        }
    }

    /**
     * set columns used to list lockers
     *
     * @ since 1.0.0
     *
     * @param array $columns            
     * @return array $columns - modified columns for locker listing
     */
    function set_post_columns ( $columns ) {
        $columns = array(
                'cb' => '<input type="checkbox" />',
                'title' => __( 'Locker Title', 'sos-domain' ),
                'locker_id' => __( 'Locker ID', 'sos-domain' ),
                'author' => __( 'Created by', 'sos-domain' ),
                'date' => __( 'Date', 'sos-domain' )
        );
        return $columns;
    }

    /**
     * for locker id column show locker id of the current post
     *
     * @ since 1.0.0
     *
     * @param string $column            
     */
    function render_post_columns ( $column ) {
        if ( 'locker_id' == $column ) {
            echo esc_html( get_post_meta( get_the_ID(), 'locker_id', true ) );
        }
    }

    /**
     * setup meta boxes to show in various slots in add/modify locker page
     *
     * @ since 1.0.0
     *
     * @param object $post
     *            - WP_POST object for the current post
     */
    function setup_meta_boxes ( $post ) {
        add_meta_box( 'locker_meta_box', __( 'Share Locker', 'sos-domain' ), 
                array(
                        $this,
                        'render_locker_meta_box'
                ), $this->post_type, 'normal', 'high' );
        add_meta_box( 'codedrops_meta_box', 'CodeDrops', 
                array(
                        $this,
                        'render_codedrops_meta_box'
                ), $this->post_type, 'side' );
        remove_meta_box( 'slider_sectionid', $this->post_type, 'normal' );
        remove_meta_box( 'layout_sectionid', $this->post_type, 'normal' );
    }

    /**
     * render locker metabox with fields and Tiny Editor to add/modify a locker
     *
     * @ since 1.0.0
     */
    function render_locker_meta_box () {
        wp_nonce_field( 'save_locker', 'locker_nonce' );
        
        echo $this->get_table();
        
        $this->add_editor();
    }

    /**
     * html table with input fields for locker metabox
     *
     * @ since 1.0.0
     *
     * @return string - html string
     */
    function get_table () {
        global $post;
        
        $locker_id = get_post_meta( $post->ID, 'locker_id', true );
        $shareon_fb = get_post_meta( $post->ID, 'shareon_fb', true );
        $shareon_gplus = get_post_meta( $post->ID, 'shareon_gplus', true );
        $shareon_twitter = get_post_meta( $post->ID, 'shareon_twitter', true );
        $share_target = get_post_meta( $post->ID, 'share_target', true );
        $locker_text = get_post_meta( $post->ID, 'locker_text', true );
        if ( empty( $share_target ) ) {
            $share_target = 'page';
        }
        
        $locker_id_label = __( 'Locker ID', 'sos-domain' );
        $shareon_label = __( 'Share on', 'sos-domain' );
        $fb_label = __( 'Facebook', 'sos-domain' );
        $gplus_label = __( 'Google+', 'sos-domain' );
        $twitter_label = __( 'Twitter', 'sos-domain' );
        $share_target_label = __( 'Share [Which URL]', 'sos-domain' );
        $page_label = __( 'Page', 'sos-domain' );
        $parent_label = __( 'Parent Page', 'sos-domain' );
        $site_label = __( 'Site', 'sos-domain' );
        $display_text_label = __( 'Display Text', 'sos-domain' );
        $see_help_message = 'Use Context Help (top right corner) for field level help.';
        
        $table = <<<EOD
		<div>$see_help_message</div>
		<table class="form-table">
		<tr>
		<th scope="row"><label for="locker_id">$locker_id_label</label></th>
    	<td><input type="text" name="locker_id" id="locker_id" value="$locker_id" /></td>
    	</tr><tr>
    	<th scope="row"><label for="locker_shareon">$shareon_label</label></th>
    	<td>
    	   <input type="checkbox" id="shareon_fb" name="shareon_fb" value="1"
    				{$this->checked($shareon_fb,'1')} >$fb_label</input>
			<input type="checkbox" id="shareon_gplus" name="shareon_gplus" value="1"
    				{$this->checked($shareon_gplus,'1')} >$gplus_label</input>
    		<input type="checkbox" id="shareon_twitter" name="shareon_twitter" value="1"
    				{$this->checked($shareon_twitter,'1')} >$twitter_label</input>
    	</td>
    	</tr><tr>
    	<th scope="row"><label for="share_target">$share_target_label</label></th>
		<td><input type="radio" id="share_target" name="share_target" value="page"
		           	{$this->checked($share_target,'page')} > $page_label </input>
		<input type="radio" id="share_target" name="share_target" value="parent"
					{$this->checked($share_target,'parent')} > $parent_label</input>
		<input type="radio" id="share_target" name="share_target" value="site"
					{$this->checked($share_target,'site')} > $site_label</input></td>
    	</tr><tr>
    	<th scope="row"><label for="locker_text">$display_text_label</label></th>
		<td>&nbsp;</td>
    	</tr>
        </table>
EOD;
        return $table;
    }

    /**
     * render metabox to tell about codedrops tutorials
     *
     * @ since 1.0.0
     */
    function render_codedrops_meta_box () {
        $text1 = __( 
                'Discover how this plugin is developed ! Learn WordPress Plugins Development with our companion tutorial.', 
                'sos-domain' );
        $text2 = __( 'CodeDrops WordPress Tutorial', 'sos-domain' );
        $html = <<<EOD
		<p>&nbsp;</p>
		<p>$text1</p>		
		<p><h3><a href="http://www.codedrops.in/wordpress-tutorial" target="_blank">$text2</a></h3></p>					 	
EOD;
        echo $html;
    }

    /**
     * return checked if two values match else empty string
     *
     * @ since 1.0.0
     *
     * @param string $value1            
     * @param string $value2            
     * @return string - checked|empty string
     */
    function checked ( $value1, $value2 ) {
        if ( $value1 == $value2 ) {
            return 'checked';
        } else {
            return '';
        }
    }

    /**
     * setup Tiny Editor for locker text editor
     *
     * @ since 1.0.0
     */
    function add_editor () {
        global $post;
        
        $id = 'locker_text';
        $name = 'locker_text';
        $value = wp_kses_post( get_post_meta( $post->ID, 'locker_text', true ) );
        
        $editor_options = array(
                'textarea_name' => $name,
                // 'media_buttons' => true,
                'textarea_rows' => 10,
                // 'quicktags' => true,
                'tinymce' => array(
                        'toolbar1' => 'bold italic underline | alignleft aligncenter alignright | outdent indent | undo redo |  fullscreen',
                        'toolbar2' => 'formatselect fontselect fontsizeselect | forecolor backcolor | removeformat'
                )
        );
        wp_editor( $value, $id, $editor_options );
    }

    /**
     * save metadata on post save
     *
     * @ since 1.0.0
     *
     * @param int $post_id
     *            - post id
     */
    function save_post ( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return;
        
        if ( ! isset( $_POST[ 'locker_nonce' ] ) ||
                 ! wp_verify_nonce( $_POST[ 'locker_nonce' ], 'save_locker' ) )
            return;
        
        if ( ! current_user_can( 'edit_posts' ) )
            return;
        
        $id = 'locker_id';
        $data = $this->sanitized_text( $id );
        if ( '' == $data ) {
            set_transient( 'sos_invalid_id', true, 180 );
            update_post_meta( $post_id, $id, $data );
        } else {
            if ( $this->is_duplicate_locker( $post_id ) ) {
                $data = '';
                update_post_meta( $post_id, $id, $data );
                set_transient( 'sos_duplicate_id', true, 180 );
            } else {
                update_post_meta( $post_id, $id, $data );
            }
        }
        
        $id = 'share_target';
        $data = $this->sanitized_text( $id );
        update_post_meta( $post_id, $id, $data );
        
        $id = 'shareon_fb';
        $data = $this->sanitized_int( $id );
        update_post_meta( $post_id, $id, $data );
        
        $id = 'shareon_gplus';
        $data = $this->sanitized_int( $id );
        update_post_meta( $post_id, $id, $data );
        
        $id = 'shareon_twitter';
        $data = $this->sanitized_int( $id );
        update_post_meta( $post_id, $id, $data );
        
        $id = 'locker_text';
        $data = $this->sanitized_html( $id );
        update_post_meta( $post_id, $id, $data );
    }

    /**
     * check whether locker with same id already exists
     *
     * @ since 1.0.0
     *
     * @param init $post_id            
     * @return boolean
     */
    function is_duplicate_locker ( $post_id ) {
        $locker_id = null;
        if ( isset( $_POST[ 'locker_id' ] ) ) {
            $locker_id = $_POST[ 'locker_id' ];
        }
        $existing_post_id = Sos_Helper::get_locker_post_id( $locker_id );
        if ( is_null( $existing_post_id ) ) {
            return false;
        } else {
            
            if ( $existing_post_id == $post_id ) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * sanitize int data
     *
     * @ since 1.0.0
     *
     * @param string $id
     *            - assoc index of $_POST
     */
    function sanitized_int ( $id ) {
        $data = 0;
        if ( isset( $_POST[ $id ] ) ) {
            $data = $_POST[ $id ];
        }
        return absint( $data );
    }

    /**
     * sanitize text data
     *
     * @ since 1.0.0
     *
     * @param string $id
     *            - assoc index of $_POST
     */
    function sanitized_text ( $id ) {
        $data = '';
        if ( isset( $_POST[ $id ] ) ) {
            $data = $_POST[ $id ];
        }
        return sanitize_text_field( $data );
    }

    /**
     * sanitize html data
     *
     * @ since 1.0.0
     *
     * @param string $id
     *            - assoc index of $_POST
     */
    function sanitized_html ( $id ) {
        $data = '';
        if ( isset( $_POST[ $id ] ) ) {
            $data = $_POST[ $id ];
        }
        return wp_kses_post( $data );
    }

    /**
     * toggle style of minor-publishing element to hide it to show only save
     * and trash buttons in publish metabox for sos custom post type
     *
     * @ since 1.0.0
     */
    function hide_minor_publish () {
        $screen = get_current_screen();
        if ( in_array( $screen->id, 
                array(
                        $this->post_type
                ) ) ) {
            echo '<style>#minor-publishing { display: none; }</style>';
        } else {
            echo ''; // for unittest
        }
    }

    /**
     * modify publish messages for custom post type
     *
     * @ since 1.0.0
     *
     * @param array $messages            
     * @return array - modified messages array
     */
    function filter_published_message ( $messages ) {
        global $post;
        if ( false == isset( $post ) ) {
            return $messages;
        }
        if ( $post->post_type != $this->post_type ) {
            return $messages;
        }
        $index = 1;
        $message = __( 'Share Locker updated', 'sos-domain' );
        $messages[ 'post' ][ $index ] = $message;
        
        $index = 6;
        $message = __( 'Share Locker saved', 'sos-domain' );
        $messages[ 'post' ][ $index ] = $message;
        
        return $messages;
    }

    /**
     * creates basic locker by inserting post of custom type sos
     * and adds required meta to the post
     *
     * @since 1.0.0
     */
    public function create_basic_locker () {
        if ( false == current_user_can( 'activate_plugins' ) ) {
            return;
        }
        $post = array(
                'post_name' => 'basic',
                'post_title' => 'Basic Locker',
                'post_status' => 'publish',
                'post_type' => 'sos'
        );
        $post_id = wp_insert_post( $post );
        if ( $post_id != 0 ) {
            add_post_meta( $post_id, 'locker_id', 'basic' );
            add_post_meta( $post_id, 'shareon_fb', '1' );
            add_post_meta( $post_id, 'shareon_gplus', '1' );
            add_post_meta( $post_id, 'shareon_twitter', '1' );
            add_post_meta( $post_id, 'share_target', 'page' );
            add_post_meta( $post_id, 'locker_text', $this->basic_text() );
        }
    }

    /**
     * returns the html used as display text for the basic locker
     *
     * @since 1.0.0
     *       
     * @return string - html text
     */
    public function basic_text () {
        $text1 = __( 'Content is Locked', 'sos-domain' );
        $text2 = __( 'Share to unlock the content', 'sos-domain' );
        $html = <<<EOD
&nbsp;
<p style="text-align: center;"><strong>
<span style="font-size: 16pt; color: #993300; font-family: impact,chicago;">
{$text1} !</span></strong></p>
&nbsp;
<p style="text-align: center;">
<span style="font-size: 14pt; color: #993300; font-family: impact,chicago;">
{$text2}.</span></p>
&nbsp;
EOD;
        return $html;
    }
}


