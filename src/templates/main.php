<?php

declare(strict_types=1);
defined('ABSPATH') || die();

// prepare variables
$action = 'fws_rod_save';
$redirectURL = urlencode(sanitize_text_field($_SERVER['REQUEST_URI'] ?? ''));


?>
<div class="fws_rod">
    <h1><?=esc_html__('Resize on demand', 'fws-resize-on-demand')?></h1>

    <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
        <?php foreach($Tabs as $slug => $label) { ?>
            <?php $class = $CurrentTab === $slug ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>
            <a id="fws_rod_tab_<?=esc_attr($slug)?>" class="<?=esc_attr($class)?>" href="javascript:;">
                <?=esc_html__($label, 'fws-resize-on-demand')?>
            </a>
        <?php } ?>
    </nav>

    <div style="clear:both"></div>

    <?php foreach(array_keys($Tabs) as $slug) { ?>
        <?php $style = $CurrentTab === $slug ? 'display:block' : 'display:none'; ?>
        <div id="fws_rod_tabc_<?=esc_attr($slug)?>" class="fws_rod_tab" style="<?=esc_attr($style)?>">
            <?php include __DIR__ . "/tab-$slug.php"; ?>
        </div>
    <?php } ?>

</div>
