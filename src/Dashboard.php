<?php namespace Tekod\ROD;

/**
 * Class Dashboard
 * @package Tekod\ROD
 */
class Dashboard {


    // form actions
    public static $ActionSettingsSizes= 'fws_rod_settings_sizes';
    public static $ActionSettingsJpeg= 'fws_rod_settings_jpeg';
    public static $ActionDeleteThumbs= 'fws_rod_delete_thumbs';
    public static $ActionRegenThumbs= 'fws_rod_regen_thumbs';
    public static $ActionEnableLogging= 'fws_rod_enable_logging';

    // transient key
    protected static $AdminMsgTransient= 'fws_resize_on_demand_admin';


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
            add_action( "load-$PageId", [__CLASS__, 'OnLoad']);
        });

        // register handlers
        add_action('admin_post_'.static::$ActionSettingsSizes, [__CLASS__, 'OnActionSaveSettingsSizes']);
        add_action('admin_post_'.static::$ActionSettingsJpeg, [__CLASS__, 'OnActionSaveSettingsJpeg']);
        add_action('admin_post_'.static::$ActionEnableLogging, [__CLASS__, 'OnActionEnableLogging']);

        // catch other admin hooks
        add_filter("plugin_action_links_".TEKOD_ROD_PLUGINBASENAME, [$Dashboard, 'SettingsLinks']);

        // setup hooks
        add_action('init', [$Dashboard, 'OnInit']);
    }



    /**
     * Insert custom content in area under plugin description.
     *
     * @param array $Links
     * @return array
     */
    public function SettingsLinks($Links) {

        $Content= '<a href="options-general.php?page=fws-resize-on-demand">'.__('Settings', 'fws-resize-on-demand').'</a>';
        array_push($Links, $Content);
        return $Links;
    }


    /**
     * Setup hook on "init" action.
     */
    public function OnInit() {

        // register ajax handlers
        add_action('wp_ajax_'.self::$ActionDeleteThumbs, [$this, 'onAjaxDeleteThumbs']);
        add_action('wp_ajax_'.self::$ActionRegenThumbs, [$this, 'onAjaxRegenThumbs']);
    }


    /**
     * Enqueue CSS and javascript files.
     */
    public static function OnLoad() {

        // enqueue
        $PluginURL= plugin_dir_url(TEKOD_ROD_DIR.'/.');
        wp_enqueue_style('jqAjaxProgressRunnerCSS', $PluginURL.'assets/jqAjaxProgressRunner/jqAjaxProgressRunner.css', [], TEKOD_ROD_VERSION);
        wp_enqueue_script('jqAjaxProgressRunnerJS', $PluginURL.'assets/jqAjaxProgressRunner/jqAjaxProgressRunner.js', [], TEKOD_ROD_VERSION);
        wp_enqueue_style('RODStyle', $PluginURL.'assets/admin-style.css', [], TEKOD_ROD_VERSION);
        wp_enqueue_script('RODScript', $PluginURL.'assets/admin-scripts.js', [], TEKOD_ROD_VERSION);

        // capture notices
        self::PrepareAdminNotices();
    }


    /**
     * Echo dashboard content.
     */
    public function RenderDashboard() {

        // validate access
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'fws-resize-on-demand'));
        }

        // prepare template vars
        $Tabs = $this->GetAvailableTabs();
        $CurrentTab = $this->GetCurrentTab($Tabs);

        // render page template
        include __DIR__ . '/templates/main.php';
    }

    /**
     * Return list of available tabs.
     */
    protected function GetAvailableTabs() {

        $Pages = [
            'init'    => __('Introduction', 'fws-resize-on-demand'),
            'settings'=> __('Settings', 'fws-resize-on-demand'),
            'utils'   => __('Utilities', 'fws-resize-on-demand'),
        ];
        return apply_filters('fws_rod_admin_pages', $Pages);
    }


    /**
     * Return slug of current tab.
     *
     * @param $Tabs array
     * @return string
     */
    protected function GetCurrentTab($Tabs) {

        $Tab = $_REQUEST['tab'] ?? $_COOKIE['fws_rod_tab'] ?? '';
        return isset($Tabs[$Tab]) ? $Tab : array_keys($Tabs)[0];
    }


    /**
     * Handle saving settings for sizes.
     */
    public static function OnActionSaveSettingsSizes() {

        // validation
        if (self::ValidateSubmit(static::$ActionSettingsSizes)) {

            // update settings
            $Sizes= array_map('sanitize_text_field', (array)$_POST['fws_ROD_Sizes'] ?? []);
            Config::Set([
                'HandleSizes' => Services::ApplyFiltersOnSizesToHandle($Sizes),
            ]);
            Config::Save();

            // prepare confirmation message
            set_transient(self::$AdminMsgTransient.'_msg', 'updated-' . __('Settings saved.', 'fws-resize-on-demand'));
        }

        // redirect to viewing context
        wp_safe_redirect(wp_get_referer());
        die();
    }


    /**
     * Handle saving settings for JPEG compression.
     */
    public static function OnActionSaveSettingsJpeg() {

        // validation
        if (self::ValidateSubmit(static::$ActionSettingsJpeg)) {

            // update settings
            Config::Set([
                'JpegCompression' => [
                    'Enabled' => intval($_POST['fws_ROD_JpegCompEnabled']) === 1,
                    'Quality' => max(min(intval($_POST['fws_ROD_JpegCompQuality']), 100), 0),
                ],
            ]);
            Config::Save();

            // prepare confirmation message
            set_transient(self::$AdminMsgTransient.'_msg', 'updated-' . __('Settings saved.', 'fws-resize-on-demand'));
        }

        // redirect to viewing context
        wp_safe_redirect(wp_get_referer());
        die();
    }


    /**
     * Handle saving logging settings.
     */
    public static function OnActionEnableLogging() {

        // validation
        if (self::ValidateSubmit(static::$ActionEnableLogging)) {

            // update settings
            Config::Set([
                'EnableLogging' => intval($_POST['fws_ROD_Logging'] ?? ''),
            ]);
            Config::Save();

            // prepare confirmation message
            set_transient(self::$AdminMsgTransient.'_msg', 'updated-' . __('Settings saved.', 'fws-resize-on-demand'));
        }

        // redirect to viewing context
        wp_safe_redirect(wp_get_referer());
        die();
    }


    /**
     * Analyze attachment record, delete and unregister thumbnails.
     *
     * @param $Attachment
     * @param $UploadsDir
     * @param $HandleSizes
     * @param $AvoidMimeTypes
     * @return int
     */
    protected static function RemoveHandledSizesFromAttachment($Attachment, $UploadsDir, $HandleSizes, $AvoidMimeTypes) {

        $NeedUpdateMeta = false;
        $CountRemoved= 0;

        // unpack meta value
        $Meta = unserialize($Attachment['meta_value']) ?? [];
        if (!$Meta || !isset($Meta['sizes'])) {
            return 0;   // probably not an image
        }

        // check each size
        $ExistingSizes = array_keys($Meta['sizes']);
        foreach ($ExistingSizes as $SizeName) {

            // prepare pack
            $SizePack= $Meta['sizes'][$SizeName] ?? null;
            if (!$SizePack) {
                continue;  // already removed
            }

            // should we keep that thumbnail?
            if (!in_array($SizeName, $HandleSizes) || in_array($SizePack['mime-type'], $AvoidMimeTypes)) {
                continue;
            }

            // delete file if exist
            $ThumbPath = $UploadsDir . '/' . dirname($Meta['file']) . '/' . $SizePack['file'];
            if (is_file($ThumbPath)) {
                unlink($ThumbPath);
                $CountRemoved++ ;
            }

            // remove from record
            $NeedUpdateMeta = true;
            $Meta= Image::RemoveSizeFromMetaPack($Meta, $SizeName);
        }

        // update meta field
        if ($NeedUpdateMeta) {
            wp_update_attachment_metadata($Attachment['post_id'], $Meta);
            $Removed = implode(',', array_diff($ExistingSizes, array_keys($Meta['sizes'])));
            $Left = implode(',', array_keys($Meta['sizes']));
            Services::Log("Deleted thumbnails of post #$Attachment[post_id]: [$Removed], Left: [$Left]");
        }

        // return count
        return $CountRemoved;
    }


    /**
     * Check is submit request valid.
     *
     * @param string $Action
     * @return bool
     */
    protected static function ValidateSubmit($Action) {

        if (!wp_verify_nonce($_POST[Config::$OptionName.'_nonce'], $Action)) {
            set_transient(self::$AdminMsgTransient.'_msg', 'error-' . __('Session expired, please try again.', 'fws-resize-on-demand'));
            return false;
        }
        if (!isset($_POST['_wp_http_referer'])) {
            set_transient(self::$AdminMsgTransient.'_msg', 'error-' . __('Missing target.', 'fws-resize-on-demand'));
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

        $Message= get_transient(self::$AdminMsgTransient.'_msg');
        delete_transient(self::$AdminMsgTransient.'_msg');

        $Parts= explode('-', $Message, 2);
        if (count($Parts) === 2) {
            echo '<div class="'.$Parts[0].'"><p>'.$Parts[1].'</p></div>';
        }
    }


    /**
     * Ajax listener for "fws_rod_delete_thumbs" action.
     */
    public function onAjaxDeleteThumbs() {

        // validate nonce
        check_ajax_referer('fws_rod_delete_thumbs', 'nonce');

        // get params
        $IsInitial = intval($_REQUEST['step'] ?? '') === 0;

        // log
        if ($IsInitial) {
            Services::Log("Delete thumbnails: manually triggered ...");
        }

        // do job
        $Result = $this->DeleteThumbs($IsInitial);

        // log
        if ($Result['done']) {
            Services::Log("Delete thumbnails: finished.");
        }

        // send response
        wp_send_json_success([
            'success' => true,
            'data' => $Result,
        ]);
    }


    /**
     * Handle clearing thumbnails.
     */
    public function DeleteThumbs($IsInitial) {

        global $wpdb;
        require_once __DIR__.'/Image.php';
        $ProgressTransient = self::$AdminMsgTransient.'_deleteThumbs_progress';
        $StoredProgress = explode(':', strval(get_transient($ProgressTransient)));

        $UploadsDir= wp_get_upload_dir()['basedir'];
        $HandleSizes= Config::Get()['HandleSizes'];
        $AvoidMimeTypes= apply_filters('fws_rod_avoid_mime_types', ['image/svg', 'image/svg+xml']);

        // get count of attachment records
        $RecordsCount= $wpdb->get_var("SELECT count(*) FROM `{$wpdb->prefix}postmeta` WHERE `meta_key`='_wp_attachment_metadata'");

        // force initial step if no transient found
        if (count($StoredProgress) !== 2) {
            $IsInitial= 1;
        }

        // store total count in initial step
        if ($IsInitial) {
             $TotalNumOfRemovedThumbs= 0;
             $StoredProgress= [$RecordsCount, 0]; // total-num-of-records : total-num-of-removed-thumbs
             set_transient($ProgressTransient, implode(':', $StoredProgress), 86400);
        } else {
            $TotalNumOfRemovedThumbs= $StoredProgress[1];
        }

        // fetch records in chunks of 100
        $Timer= time();
        $RecordsRemains= $RecordsCount;
        for ($i=0; $i<$RecordsCount/100; $i++){
            $RecordsRemains -= 100;
            $SQL= "SELECT * FROM `{$wpdb->prefix}postmeta` WHERE `meta_key`='_wp_attachment_metadata' LIMIT 100 OFFSET ".($i*100);
            // process all records in chunk
            foreach($wpdb->get_results($SQL, ARRAY_A) as $Row) {
                $TotalNumOfRemovedThumbs += self::RemoveHandledSizesFromAttachment($Row, $UploadsDir, $HandleSizes, $AvoidMimeTypes);
            }
            // break after 10 seconds
            if (time() - $Timer > 10) {
                break;
            }
        }
        $StoredProgress[1] = $TotalNumOfRemovedThumbs;

        // store transient for next step
        if ($RecordsRemains <= 0) {
            delete_transient($ProgressTransient);
        } else {
            set_transient($ProgressTransient, implode(':', $StoredProgress), 86400);
        }

        // report
        return [
            'done' => $RecordsRemains <= 0,
            'progress' => round((1 - max(0, $RecordsRemains) / $StoredProgress[0]) * 100),
            'report' => sprintf(__('Removed %d thumbnails.', 'fws-resize-on-demand'), $TotalNumOfRemovedThumbs),
        ];
    }


    /**
     * Ajax listener for "fws_rod_regen_thumbs" action.
     */
    public function onAjaxRegenThumbs() {

        // validate nonce
        check_ajax_referer('fws_rod_regen_thumbs', 'nonce');

        // get params
        $IsInitial = intval($_REQUEST['step'] ?? '') === 0;
        $SkipHandled = intval($_REQUEST['RegenSkipHandled'] ?? '') === 1;
        $MissingOnly = intval($_REQUEST['RegenMissingOnly'] ?? '') === 1;

        // log
        if ($IsInitial) {
            Services::Log("Regenerate thumbnails: manually triggered ...");
        }

        // do job
        $Result = $this->RegenerateThumbs($IsInitial, $SkipHandled, $MissingOnly);

        // log
        if ($Result['done']) {
            Services::Log("Regenerate thumbnails: finished.");
        }

        // send response
        wp_send_json_success([
            'success' => true,
            'data' => $Result,
        ]);
    }


    /**
     * Handle regenerating thumbnails.
     *
     * @param bool $IsInitial
     * @param bool $SkipHandled
     * @param bool $MissingOnly
     * @return array
     */
    public function RegenerateThumbs($IsInitial, $SkipHandled, $MissingOnly) {

        global $wpdb;
        require_once __DIR__.'/Image.php';
        $ProgressTransient = self::$AdminMsgTransient.'_regenThumbs_progress';
        $StoredProgress = explode(':', strval(get_transient($ProgressTransient)));

        $UploadsDir= wp_get_upload_dir()['basedir'];
        $HandleSizes= Config::Get()['HandleSizes'];
        $AvoidSizes= $SkipHandled ? $HandleSizes : [];
        $AvoidMimeTypes= apply_filters('fws_rod_avoid_mime_types', ['image/svg', 'image/svg+xml']);

        // force initial step if no transient found
        if (count($StoredProgress) !== 3) {
            $IsInitial= 1;
        }

        // store total count in initial step
        if ($IsInitial) {
            // get attachment records
            $Ids= $wpdb->get_col("SELECT post_id FROM `{$wpdb->prefix}postmeta` WHERE `meta_key`='_wp_attachment_metadata'", 0);
            $TotalNumOfRegeneratedThumbs= 0;
            $StoredProgress= [count($Ids), 0, implode(',', $Ids)]; // total-num-of-records : total-num-of-regenerated-thumbs : csv-ids
            set_transient($ProgressTransient, implode(':', $StoredProgress), 86400);
        } else {
            $TotalNumOfRegeneratedThumbs= $StoredProgress[1];
            $Ids= array_filter(explode(',', $StoredProgress[2]));
        }

        // loop
        $Timer= time();
        foreach($Ids as $Key=>$Id) {
            $TotalNumOfRegeneratedThumbs += Image::RegenerateAttachmentThumbs($Id, $UploadsDir, $AvoidSizes, $AvoidMimeTypes, $MissingOnly);
            unset($Ids[$Key]);
            // break after 10 seconds
            if (time() - $Timer > 10) {
                break;
            }
        }

        // store transient for next step
        if (empty($Ids)) {
            delete_transient($ProgressTransient);
        } else {
            $StoredProgress[1] = $TotalNumOfRegeneratedThumbs;
            $StoredProgress[2] = implode(',', array_values($Ids));
            set_transient($ProgressTransient, implode(':', $StoredProgress), 86400);
        }

        // report
        return [
            'done' => empty($Ids),
            'progress' => round((1 - max(0, count($Ids)) / $StoredProgress[0]) * 100),
            'report' => sprintf(__('Regenerated %d thumbnails.', 'fws-resize-on-demand'), $TotalNumOfRegeneratedThumbs),
        ];
    }



}