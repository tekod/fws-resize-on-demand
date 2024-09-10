<?php
    defined('ABSPATH') or die();

    $Sizes= wp_get_registered_image_subsizes();
    $ActionSizes= \Tekod\ROD\Dashboard::$ActionSettingsSizes;
    $ActionJpgCom= \Tekod\ROD\Dashboard::$ActionSettingsJpeg;
    $OptionName= \Tekod\ROD\Config::$OptionName;
    $RedirectURL= urlencode($_SERVER['REQUEST_URI'] ?? '');
    $CurrSettings= \Tekod\ROD\Config::Get();

    $HandleSizes= $CurrSettings['HandleSizes'] ?? [];
    $ForceHandleSizes= apply_filters('fws_rod_enable_sizes', []);
    $ForceDisableSizes= apply_filters('fws_rod_disable_sizes', []);
    $JpegCompression= $CurrSettings['JpegCompression'] ?? [];

?>
<form id="RodSettingsForm" action="admin-post.php" method="post">
    <h4><?=esc_html__('Apply on-demand resizing on these image sizes:', 'fws-resize-on-demand')?></h4>
    <table>
        <tr>
            <td colspan="2">
                <a href="javascript:;" id="fws_rod_checkall"><?=esc_html__('Check all', 'fws-resize-on-demand')?></a>
            </td>
        </tr>
        <?php foreach($Sizes as $Key => $Size) { ?>
        <tr>
            <?php
                $Description= $Size['width'].' x '.$Size['height'].($Size['crop'] ? ', crop' : '');
                $Checked= in_array($Key, $HandleSizes) ? ' checked' : '';
                $Disabled= '';
                $Notice= '';
                if (in_array($Key, $ForceHandleSizes)) {
                    $Checked= ' checked';
                    $Disabled= ' disabled';
                    $Notice= esc_html__('This size has been enabled programmatically.', 'fws-resize-on-demand');
                }
                if (in_array($Key, $ForceDisableSizes)) {
                    $Checked= '';
                    $Disabled= ' disabled';
                    $Notice= esc_html__('This size has been disabled programmatically.', 'fws-resize-on-demand');
                }
            ?>
            <th>
                <input type="checkbox"
                       name="fws_ROD_Sizes[]"
                       id="<?php echo esc_attr($Key);?>"
                       value="<?php echo esc_attr($Key);?>"
                       <?php echo $Checked; ?><?php echo $Disabled; ?>>
            </th>
            <td>
                <label for="<?php echo esc_attr($Key);?>">
                    <b><?php echo esc_html($Key);?></b>
                    <span>(<?php echo esc_html($Description);?>)</span>
                </label>
                <?php if ($Notice) { ?>
                    <p><?php echo esc_html($Notice); ?></p>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
    </table>
    <div style="padding:1em 0 0 1em; color:gray">
        <?=esc_html(sprintf(__('Total: %d registered sizes', 'fws-resize-on-demand'), count($Sizes)))?>
    </div>
    <?php submit_button(); ?>
    <input type="hidden" name="action" value="<?php echo $ActionSizes; ?>">
    <?php wp_nonce_field($ActionSizes, $OptionName.'_nonce', false); ?>
    <?php wp_referer_field(); ?>
</form>


<form id="RodSettingsCompressionForm" action="admin-post.php" method="post">
    <h4><?=esc_html__('Adjust compression level for JPEG images', 'fws-resize-on-demand')?></h4>
    <div>
        <?=esc_html__('WordPress media manager typically compress JPEG images with quality 82%, use this tool to reduce it and generate smaller thumbnail files. This will affect all image sizes, not only sizes that we handle. You have to regenerate thumbnails to apply new quality to existing thumbnails.', 'fws-resize-on-demand')?>
    </div>
    <div style="padding:2em 0 0 0">
        <input type="checkbox" name="fws_ROD_JpegCompEnabled" id="fws_ROD_JpegCompEnabled" value="1"<?php checked($JpegCompression['Enabled'] ?? false); ?>>
        <label for="fws_ROD_JpegCompEnabled"><?=esc_html__('Enable custom JPEG quality', 'fws-resize-on-demand')?></label>
        &nbsp;
        <input type="number" name="fws_ROD_JpegCompQuality" value="<?=intval($JpegCompression['Quality'] ?? 82)?>" class="small-text" /> %
    </div>
    <?php submit_button(__('Save Changes', 'fws-resize-on-demand'), 'primary large', 'submit'); ?>
    <input type="hidden" name="action" value="<?php echo esc_attr($ActionJpgCom); ?>">
    <?php wp_nonce_field($ActionJpgCom, $OptionName.'_nonce', false); ?>
    <?php wp_referer_field(); ?>
</form>