<?php namespace FWS\ROD;

/**
 * Class Dashboard
 * @package FWS\ROD
 */
class Dashboard {


    // form actions
    public static $ActionSettings= 'fws_rod_settings';
    public static $ActionDeleteThumbs= 'fws_rod_delete_thumbs';

    // "options" table identifier
    public static $OptionName= 'fws_resize_on_demand';

    // transient key
    protected static $AdminMsgTransient= 'fws_resize_on_demand_admin_msg';

    // settings page slug
    protected static $AdminPageSlug= 'fws-resize-on-demand';

    // tabs
    protected static $AdminPages= [
        'init'    => 'Introduction',
        'settings'=> 'Settings',
        'utils'   => 'Utilities',
    ];


    /**
     * Initialization.
     */
    public static function Init() {

        // instantiate object
        $Dashboard= new static;

        // register page in admin menu
        add_action('admin_menu', function () use ($Dashboard) {
            $PageId= add_options_page(
                'Resize-On-Demand settings',
                'ResizeOnDemand',
                'manage_options',
                'fws-resize-on-demand',
                [$Dashboard,'RenderDashboard']
            );
            add_action( "load-$PageId", [__CLASS__, 'PrepareAdminNotices']);
        });

        // register handlers
        add_action('admin_post_'.static::$ActionSettings, [__CLASS__, 'OnPostSettings']);
        add_action('admin_post_'.static::$ActionDeleteThumbs, [__CLASS__, 'OnPostDeleteThumbs']);

        // catch other admin hooks
        add_filter("plugin_action_links_".FWS_ROD_PLUGINBASENAME, [$Dashboard, 'SettingsLinks']);
        add_action('init', [$Dashboard, 'OnInit']);
    }


    /**
     * Insert custom content in area under plugin description.
     *
     * @param array $Links
     * @return array
     */
    public function SettingsLinks($Links) {

        $Content= '<a href="options-general.php?page=fws-resize-on-demand">Settings</a>';
        array_push($Links, $Content);
        return $Links;
    }


    /**
     * Enqueue CSS and javascript files.
     */
    public function OnInit() {

        $PluginURL= plugin_dir_url(FWS_ROD_DIR.'/.');
        //wp_enqueue_style( 'FWSStyle', $PluginURL.'assets/style.css' );
        wp_enqueue_script( 'FWSScript', $PluginURL.'assets/scripts.js');
    }


    /**
     * Echo dashboard content.
     */
    public function RenderDashboard() {

        // validate access
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        // find current tab
        $CurrentTab= sanitize_key($_REQUEST['tab'] ?? '');
        if (!isset(self::$AdminPages[$CurrentTab])) {
            $CurrentTab= array_keys(self::$AdminPages)[0];
        }

        // wrapper and <h1>
        echo '<div class="fws_rod">';
        echo '<h1>Resize on demand</h1>';
        echo '<hr>';

        // render tabs
        self::RenderTabs($CurrentTab);

        // render tab contents
        self::RenderTabContents($CurrentTab);

        // close wrapper
        echo '</div>';
    }

    /**
     * Partial - echo tabs section.
     *
     * @param string $CurrentTab
     */
    protected static function RenderTabs($CurrentTab) {

        $Tabs= '';
        foreach(self::$AdminPages as $Slug => $Label) {
            //$URL= admin_url( 'options-general.php?page='.self::$AdminPageSlug.'&tab='.esc_attr($Slug));
            $Class= $CurrentTab === $Slug ? 'nav-tab nav-tab-active' : 'nav-tab';
            $Tabs .= '<a href="javascript:FwsRodTab(\''.$Slug.'\')" id="fws_rod_tab_'.$Slug.'" class="'.$Class.'">'.esc_html($Label).'</a>';
        }

        echo '<nav class="nav-tab-wrapper woo-nav-tab-wrapper">'.$Tabs.'</nav>';
        echo '<div style="clear:both"></div>';
    }


    protected static function RenderTabContents($CurrentTab) {

        foreach(array_keys(self::$AdminPages) as $Slug) {
            echo '<div id="fws_rod_tabc_'.$Slug.'" class="fws_rod_tab" style="display:'.($Slug === $CurrentTab ? 'block' : 'none').'">';
            include "templates/$Slug.php";  // whitelisted slug
            echo '</div>';
        }
    }


    /**
     * Handle saving settings.
     */
    public static function OnPostSettings() {

        // validation
        if (self::ValidateSubmit(static::$ActionSettings)) {

            // update settings
            $Sizes= array_map('sanitize_text_field', (array)$_POST['fws_ROD_Sizes'] ?? []);
            $Settings = [
                'HandleSizes' => $Sizes,
            ];
            update_option(static::$OptionName, serialize($Settings));

            // prepare confirmation message
            set_transient(self::$AdminMsgTransient, 'updated-Settings saved.');
        }

        // redirect to viewing context
        wp_safe_redirect(urldecode($_POST['_wp_http_referer']));
        die();
    }


    /**
     * Handle clearing thumbnails.
     */
    public static function OnPostDeleteThumbs() {

        // validation
        if (self::ValidateSubmit(static::$ActionDeleteThumbs)) {

            $UploadsDir= wp_get_upload_dir()['basedir'];
            $HandleSizes= Config::Get()['HandleSizes'];

            // execute clearing
            global $wpdb;
            $SQL= "SELECT * FROM `{$wpdb->prefix}postmeta` WHERE `meta_key`='_wp_attachment_metadata'";

            // loop
            foreach($wpdb->get_results($SQL, ARRAY_A) as $Row) {
                $Meta= unserialize($Row['meta_value']) ?? [];
                if (!$Meta || !isset($Meta['sizes'])) {
                    continue;   // possibly not image
                }
                $NeedUpdateMeta= false;
                foreach($Meta['sizes'] as $Key => $SizePack) {
                    if (in_array($Key, $HandleSizes)) {
                        $NeedUpdateMeta = true;
                        $ThumbPath= $UploadsDir . '/' . dirname($Meta['file']) . '/' . $SizePack['file'];
                        unlink($ThumbPath);
                        unset($Meta['sizes'][$Key]);
                    }
                }
                if ($NeedUpdateMeta) {
                    wp_update_attachment_metadata($Row['post_id'], $Meta);
                }
            }

            // prepare confirmation message
            set_transient(self::$AdminMsgTransient, 'updated-Cleared.');
        }

        // redirect to viewing context
        wp_safe_redirect(urldecode($_POST['_wp_http_referer']));
        die();
    }


    /**
     * Check is submit request valid.
     *
     * @param string $Action
     * @return bool
     */
    protected static function ValidateSubmit($Action) {

        if (!wp_verify_nonce($_POST[static::$OptionName.'_nonce'], $Action)) {
            set_transient(self::$AdminMsgTransient, 'error-Invalid nonce.<br>'.var_export($_POST, true));
            return false;
        }
        if (!isset($_POST['_wp_http_referer'])) {
            set_transient(self::$AdminMsgTransient, 'error-Missing target.');
            return false;
        }
        return true;
    }


    /**
     * Schedule rendering admin notices.
     */
    public static function PrepareAdminNotices() {

        add_action('admin_notices', [__CLASS__, 'RenderAdminNotice']);
    }


    /**
     * Display notices on top of admin page.
     */
    public static function RenderAdminNotice() {

        $Message= get_transient(self::$AdminMsgTransient);
        delete_transient(self::$AdminMsgTransient);

        $Parts= explode('-', $Message, 2);
        if (count($Parts) === 2) {
            echo '<div class="'.$Parts[0].'"><p>'.$Parts[1].'</p></div>';
        }
    }

}