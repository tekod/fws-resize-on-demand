<?php
    defined('ABSPATH') or die();

    $ActionDel= \Tekod\ROD\Dashboard::$ActionDeleteThumbs;
    $ActionRegen = \Tekod\ROD\Dashboard::$ActionRegenThumbs;
    $ActionLog= \Tekod\ROD\Dashboard::$ActionEnableLogging;
    $LogFilePath= \Tekod\ROD\Services::GetLogFilePath();
    $IsLoggingEnabled= \Tekod\ROD\Config::Get()['EnableLogging'];
    $OptionName= \Tekod\ROD\Config::$OptionName;
    $RedirectURL= urlencode($_SERVER['REQUEST_URI']);

?>

<div>
    <form action="admin-post.php" method="post" class="utils-form">
        <?=esc_html__('Delete all thumbnails at sizes that we handle so they can be re-created on demand.', 'fws-resize-on-demand')?>
        <div class="button-run-wrap">
            <?php submit_button(__('Delete', 'fws-resize-on-demand'), 'primary large', 'submit', true, ['id'=>'fws_rod_utl_delete']); ?>
            <input type="hidden" name="action" value="<?php echo esc_attr($ActionDel); ?>">
            <?php wp_nonce_field($ActionDel, '_wpnonce', false); ?>
            <?php wp_referer_field(); ?>
        </div>
    </form>

    <form action="admin-post.php" method="post" class="utils-form">
        <?=esc_html__('Regenerate all thumbnails of all images.', 'fws-resize-on-demand')?>
        <div style="padding:2em 0 0 0">
            <input type="checkbox" name="RegenSkipHandled" id="ROD_RegenSkipHandled" value="1">
            <label for="ROD_RegenSkipHandled"><?=esc_html__('Skip sizes that we handle', 'fws-resize-on-demand')?></label>
        </div>
        <div style="padding:1em 0 0 0">
            <input type="checkbox" name="RegenMissingOnly" id="ROD_RegenMissingOnly" value="1">
            <label for="ROD_RegenMissingOnly"><?=esc_html__('Create missing thumbs only', 'fws-resize-on-demand')?></label>
        </div>
        <div class="button-run-wrap">
            <?php submit_button(__('Regenerate', 'fws-resize-on-demand'), 'primary large', 'submit', true, ['id'=>'fws_rod_utl_regenerate']); ?>
            <input type="hidden" name="action" value="<?php echo esc_attr($ActionRegen); ?>">
            <?php wp_nonce_field($ActionRegen, '_wpnonce', false); ?>
            <?php wp_referer_field(); ?>
        </div>
    </form>

    <form action="admin-post.php" method="post" class="utils-form">
        <?=esc_html__('Enable debug logging to trace sources of image resizing.', 'fws-resize-on-demand')?>
        <div style="padding:2em 0 0 0">
            <input type="checkbox" name="fws_ROD_Logging" id="fws_ROD_Logging" value="1"<?php checked($IsLoggingEnabled); ?>>
            <label for="fws_ROD_Logging"><?=esc_html__('Enable debug logging', 'fws-resize-on-demand')?></label>
        </div>
        <?php submit_button(__('Save Changes', 'fws-resize-on-demand'), 'primary large', 'submit'); ?>
        <input type="hidden" name="action" value="<?php echo esc_attr($ActionLog); ?>">
        <?php wp_nonce_field($ActionLog, $OptionName.'_nonce', false); ?>
        <?php wp_referer_field(); ?>
        <div style="padding: 1em 0 2em 0">
            <?=esc_html__('Log file is located at:', 'fws-resize-on-demand')?>
            <br>
            <?=esc_html($LogFilePath)?>
        </div>
    </form>
</div>
