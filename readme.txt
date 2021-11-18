=== FWS On-Demand-Resizer ===
Contributors: tekod
Tags: images, smart, resizing, resizer
Requires at least: 4.7
Tested up to: 5.8
Stable tag: 0.2
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Smart on-demand image resizer for WordPress.

== Description ==

This plugin is solution for well known flaw in WordPress artihecture:
automatic resizing uploaded images in each of registered media sizes.

Altrough price of hosting space is low and we have gigabytes available that can be issue if you have dozen
(or more) registered sizes and because of that your backup files became larger then 10Gb.
Sooner or later you will be in situation to download (or even worse - to upload) that backup file.

Our analysis shows that vast majority of resized thumbnails are not used anywhere in website.
For typical theme with WooCommerce and additional 3 custom media sizes that percentage can be over 90%,
with 6 custom media sizes it is over 96% !!!

Because of that developers are often unconformtable with registering more media sizes trying to find
some workarounds or violating design to accomodate existing similar sizes.

Purpose of this plugin is to eleminate that overhead and allow developer to register as much sizes as he need.

It will intercept uploading process to prevent creation of thumbnails and intercept getters<br>
(<i>wp_get_attachment_image_src(), get_the_post_thumbnail_url(), the_post_thumbnail(),...</i>) to create thumbnail if needed.
Additionally it will register in database each created thumbnail allowing WordPress to delete it automatically
when parent image is deleted.

==Usage==
    
At "Settings" tab of this tool you can pick individually for which size you want to enable or disable this functionality.
Typically you will enable it for all sizes.

Button "Delete" on "Utilities" tab will remove all thumbnails for sizes that are handled by this plugin.
That will significaly reduce size of your "uploads" directory allowing new thumbnails to be recreated on demand.

Video demonstration:

[youtube https://www.youtube.com/watch?v=hfbkbM-1dlY]

==Contact==
    
Please, send bug reports and feature requests to <a href="mailto:office@tekod.com">office@tekod.com</a>

