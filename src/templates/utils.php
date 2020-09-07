<?php
    defined('ABSPATH') or die();

    $ActionDel= \FWS\ROD\Dashboard::$ActionDeleteThumbs;
    $OptionName= \FWS\ROD\Dashboard::$OptionName;
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
        <input type="hidden" name="action" value="<?php echo $ActionDel; ?>">
        <?php wp_nonce_field($ActionDel, $OptionName.'_nonce', false); ?>
        <input type="hidden" name="_wp_http_referer" value="<?php echo $RedirectURL; ?>">
    </form>
</div>
