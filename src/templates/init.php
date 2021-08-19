<?php
    defined('ABSPATH') or die();
?>
<div>
    <h3>Description</h3>
    <p>
        This plugin is solution for well known flaw in WordPress artihecture:<br>
        automatic resizing uploaded images in each of registered media sizes.
    </p>
    <p>
        Altrough price of hosting space is low and we have gigabytes available that can be issue if you have dozen
        (or more) registered sizes and because of that your backup files became larger then 10Gb.<br>
        Sooner or later you will be in situation to download (or even worse - to upload) that backup file.
    </p>
    <p>
        Our analysis shows that vast majority of resized thumbnails are not used anywhere in website.
        For typical theme with WooCommerce and additional 3 custom media sizes that percentage can be over 90%,
        with 6 custom media sizes it is over 96% !!!
    </p>
    <p>
        Because of that developers are often unconformtable with registering more media sizes trying to find
        some workarounds or violating design to accomodate existing similar sizes.
    </p>
    <p>
        Purpose of this plugin is to eleminate that overhead and allow developer to register as much sizes as he need.
    </p>
    <p>
        It will intercept uploading process to prevent creation of thumbnails and intercept getters<br>
        (<i>wp_get_attachment_image_src(), get_the_post_thumbnail_url(), the_post_thumbnail(),...</i>) to create thumbnail if needed.<br>
        Additionally it will register in database each created thumbnail allowing WordPress to delete it automatically
        when parent image is deleted.
    </p>
    <h3>Usage</h3>
    <p>
        At "Settings" tab of this tool you can pick individually for which size you want to enable or disable this functionality.<br>
        Typically you will enable it for all sizes.
    </p>
    <p>
        Button "Delete" on "Utilities" tab will remove all thumbnails for sizes that are handled by this plugin. <br>
        That will significaly reduce size of your "uploads" directory allowing new thumbnails to be recreated on demand.
    </p>
    <p>
        Video demonstration:<br>
        <iframe class="youtube-player" src="https://www.youtube.com/embed/hfbkbM-1dlY?version=3&amp;rel=1&amp;fs=1&amp;autohide=2&amp;showsearch=0&amp;showinfo=1&amp;iv_load_policy=1&amp;wmode=transparent" allowfullscreen="true" style="border:0;" width="480" height="330"></iframe>
    </p>
    <h3>Contact</h3>
    <p>
        Please, send bug reports and feature requests to <a href="mailto:office@tekod.com">office@tekod.com</a>
    </p>
</div>
