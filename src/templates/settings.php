<?php
    defined('ABSPATH') or die();

    $Sizes= wp_get_registered_image_subsizes();
    $Action= \FWS\ROD\Dashboard::$ActionSettings;
    $OptionName= \FWS\ROD\Dashboard::$OptionName;
    $RedirectURL= urlencode($_SERVER['REQUEST_URI']);
    $CurrSettings= unserialize(get_option($OptionName)) ?? [];
    $CurrSettings['HandleSizes']= $CurrSettings['HandleSizes'] ?? [];
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
        width: 3em;
    }
    .fws_rod form table span {
        color: gray;
        padding-left: 1em;
        font-size: 90%;
    }
</style>
<h3>Apply on-demand resizing on these image sizes:</h3>
<form id="RodSettingsForm" action="admin-post.php" method="post">
    <table>
        <tr>
            <td colspan="2">
                <a href="javascript:FwsRodCheckAll();">Check all</a>
            </td>
        </tr>
        <?php foreach($Sizes as $Key => $Size) { ?>
        <tr>
            <?php $Description= $Size['width'].' x '.$Size['height'].($Size['crop'] ? ', crop' : ''); ?>
            <?php $Checked= in_array($Key, $CurrSettings['HandleSizes']) ? ' checked' : ''; ?>
            <th><input type="checkbox" name="fws_ROD_Sizes[]" id="<?php echo esc_attr($Key);?>" value="<?php echo esc_attr($Key);?>"<?php echo $Checked; ?>></th>
            <td><label for="<?php echo esc_attr($Key);?>"><b><?php echo esc_html($Key);?></b><span>(<?php echo esc_html($Description);?>)</span></label></td>
        </tr>
        <?php } ?>
    </table>
    <div style="padding:1em 0 0 1em; color:gray">
        Total: <?php echo count($Sizes); ?> registered sizes
    </div>
    <?php submit_button(); ?>
    <input type="hidden" name="action" value="<?php echo $Action; ?>">
    <?php wp_nonce_field($Action, $OptionName.'_nonce', false); ?>
    <input type="hidden" name="_wp_http_referer" value="<?php echo $RedirectURL; ?>">
</form>
