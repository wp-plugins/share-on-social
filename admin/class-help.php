<?php
defined( 'ABSPATH' ) or die( "Access denied !" );

/**
 * context help to manage the plugin
 *
 * @package share-on-social
 * @subpackage admin
 * @since 1.0.0
 * @author Maithilish
 *        
 */
class Sos_Help {

    /**
     *
     * @var string - custom post type
     */
    var $post_type = 'sos';

    /**
     * hooks locker context help tabs to admin_head
     *
     * @since 1.0.0
     */
    public function setup () {
        add_action( 'admin_head', 
                array(
                        $this,
                        'codex_sos_locker_help_tab'
                ) );
    }

    /**
     * attaches help tabs to the sos locker admin screen
     * tabs provides the help to create and manage lockers
     *
     * @since 1.0.0
     */
    function codex_sos_locker_help_tab () {
        $screen = get_current_screen();
        if ( $this->post_type != $screen->post_type )
            return;
        
        $screen->add_help_tab( $this->locker_id_tab() );
        $screen->add_help_tab( $this->locker_shareon_tab() );
        $screen->add_help_tab( $this->locker_share_tab() );
        $screen->add_help_tab( $this->locker_text_tab() );
    }

    /**
     * context help tab for locker id field
     *
     * @since 1.0.0
     * @return array
     */
    function locker_id_tab () {
        $label = __( 'Locker ID', 'sos-domain' );
        $help1 = __( 'Enter a uniquie ID for the locker. ', 'sos-domain' );
        $help1 .= __( 'Both name and number are allowed as the id. ', 
                'sos-domain' );
        $help2 = __( 
                'Use some descriptive name as locker id which is easy to remember', 
                'sos-domain' );
        $html = <<<EOD
		<h3>$label</h3>
		<p>$help1</p>
		<p>$help2</p>
EOD;
        return $this->get_tab( 'locker_id_tab', $label, $html );
    }

    /**
     * context help tab for share on field
     *
     * @since 1.0.0
     * @return mixed:array
     */
    function locker_shareon_tab () {
        $label = __( 'Share On', 'sos-domain' );
        $help1 = __( 'Select the Share buttons to show in the locker', 
                'sos-domain' );
        $html = <<<EOD
		<h3>$label</h3>
		<p>$help1</p>
EOD;
        return $this->get_tab( 'locker_shareon_tab', $label, $html );
    }

    /**
     * context help tab for share target field
     *
     * @since 1.0.0
     * @return array
     */
    function locker_share_tab () {
        $label = __( 'Share [which URL]', 'sos-domain' );
        $page = __( 
                'Page - to share the Post/Page URL where locker shortcode is placed', 
                'sos-domain' );
        $parent = __( 
                'Parent Page - to share the Parent Page of the page where locker shortcode is placed', 
                'sos-domain' );
        $site = __( 'Site - to share the URL of the site', 'sos-domain' );
        $override1 = __( 'Overriding the share specified at locker level', 
                'sos-domain' );
        $override2 = __( 
                'You may also specifiy Share URL in the shortcode using link attribute. ', 
                'sos-domain' );
        $override2 .= __( 
                'For example shortcode [share-on-social name="my-locker" link="http://example.org"] ', 
                'sos-domain' );
        $override2 .= __( 
                ' overrides page,parent or site setting done at locker level and share example.org', 
                'sos-domain' );
        $override3 = __( 
                'Use this feature to share an internal or external URL that is not related to a page/post', 
                'sos-domain' );
        $html = <<<EOD
		<h3>$label</h3>
		<ul>
		<li>$page</li>
		<li>$parent</li>
		<li>$site</li>
		</ul>
		<h4>$override1</h4>
		<p>$override2</p>
		<p>$override3</p>
EOD;
        return $this->get_tab( 'locker_share_tab', $label, $html );
    }

    /**
     * context help tab for locker text field
     *
     * @since 1.0.0
     * @return array
     */
    function locker_text_tab () {
        $label = __( 'Display Text', 'sos-domain' );
        $help1 = __( 
                'Visual Editor allows you to design useful message for each locker. ', 
                'sos-domain' );
        $html = <<<EOD
		<h3>$label</h3>
		<p>$help1</p>
EOD;
        return $this->get_tab( 'locker_text_tab', $label, $html );
    }

    /**
     * creates and returns array which hold the tab values
     *
     * @since 1.0.0
     * @param string $id
     *            - tab id
     * @param string $title
     *            - tab title
     * @param string $help
     *            - tab's help text
     * @return array
     */
    function get_tab ( $id, $title, $help ) {
        $tab = array(
                'id' => $id,
                'title' => $title,
                'content' => $help
        );
        return $tab;
    }
}
