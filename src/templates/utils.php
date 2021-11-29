<?php
    defined('ABSPATH') or die();

    $ActionDel= \FWS\ROD\Dashboard::$ActionDeleteThumbs;
    $ActionLog= \FWS\ROD\Dashboard::$ActionEnableLogging;
    $LogFilePath= \FWS\ROD\Services::GetLogFilePath();
    $IsLoggingEnabled= \FWS\ROD\Config::Get()['EnableLogging'];
    $OptionName= \FWS\ROD\Config::$OptionName;
    $RedirectURL= urlencode($_SERVER['REQUEST_URI']);

?>
<style>
    .fws_rod .utils-form {
        margin: 3em 0 0 2em;
        width: 80%;
        max-width: 40em;
        border: 1px solid silver;
        background-color: #f8f8f8;
        padding: 3em 0 0 3em;
    }
</style>
<div>
    <form action="admin-post.php" method="post" class="utils-form">
        Delete all thumbnails at sizes that we handle so they can be re-created on demand.
        <?php submit_button('Delete', 'primary large', 'submit'); ?>
        <input type="hidden" name="action" value="<?php echo esc_attr($ActionDel); ?>">
        <?php wp_nonce_field($ActionDel, $OptionName.'_nonce', false); ?>
        <input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr($RedirectURL); ?>">
    </form>

    <form action="admin-post.php" method="post" class="utils-form">
        Enable debug logging to trace sources of image resizing.
        <div style="padding:2em 0 0 0">
            <input type="checkbox" name="fws_ROD_Logging" id="fws_ROD_Logging" value="1"<?php checked($IsLoggingEnabled); ?>>
            <label for="fws_ROD_Logging">Enable debug logging</label>
        </div>
        <?php submit_button('Save choice', 'primary large', 'submit'); ?>
        <input type="hidden" name="action" value="<?php echo esc_attr($ActionLog); ?>">
        <?php wp_nonce_field($ActionLog, $OptionName.'_nonce', false); ?>
        <input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr($RedirectURL); ?>">
        <div style="padding: 1em 0 2em 0">
            Log file is located at:<br><?php echo esc_html($LogFilePath); ?>
        </div>
    </form>
</div>
