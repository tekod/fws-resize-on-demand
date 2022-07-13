<?php
    defined('ABSPATH') or die();

    $Sizes= wp_get_registered_image_subsizes();
    $Action= \FWS\ROD\Dashboard::$ActionSettings;
    $OptionName= \FWS\ROD\Config::$OptionName;
    $RedirectURL= urlencode($_SERVER['REQUEST_URI'] ?? '');
    $CurrSettings= \FWS\ROD\Config::Get();

    $HandleSizes= $CurrSettings['HandleSizes'] ?? [];
    $ForceHandleSizes= apply_filters('fws_rod_enable_sizes', []);
    $ForceDisableSizes= apply_filters('fws_rod_disable_sizes', []);

?>
<style>
    .fws_rod h1 {
        padding-top: 1em;
    }
    .fws_rod h3 {
        padding-top: 2em;
    }
    .fws_rod form {
        border: 1px solid #ddd;
        background-color: #f8f8f8;
        padding: 1em 2em;
        width: 80%;
        max-width: 60em;
        margin-left: 3em;
    }
    .fws_rod form table {
        width: 100%;
        line-height: 1em;
        border-collapse: collapse;
    }
    .fws_rod form table th, .fws_rod form table td {
        padding: 0.7em 1em;
        border-bottom: 1px solid #ddd;
    }
    .fws_rod form table th {
        text-align: right;
        vertical-align: top;
        width: 3em;
    }
    .fws_rod form table th input[type="checkbox"] {
        margin-top: 1px;
    }
    .fws_rod form table th input[type="checkbox"]:disabled {
        cursor: not-allowed;
    }
    .fws_rod form table label {
        margin-right: 4em;
    }
    .fws_rod form table span {
        color: gray;
        padding-left: 1em;
        font-size: 90%;
    }
    .fws_rod form table p {
        display: inline-block;
        margin: 0;
        vertical-align: middle;
        color: gray;
        font-style: italic;
    }
</style>
<h3><?=esc_html__('Apply on-demand resizing on these image sizes:', 'fws-resize-on-demand')?></h3>
<form id="RodSettingsForm" action="admin-post.php" method="post">
    <table>
        <tr>
            <td colspan="2">
                <a href="javascript:FwsRodCheckAll();"><?=esc_html__('Check all', 'fws-resize-on-demand')?></a>
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
    <input type="hidden" name="action" value="<?php echo $Action; ?>">
    <?php wp_nonce_field($Action, $OptionName.'_nonce', false); ?>
    <input type="hidden" name="_wp_http_referer" value="<?php echo $RedirectURL; ?>">
</form>
