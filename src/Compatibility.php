<?php namespace FWS\ROD;

/**
 * Class Compatibility
 * @package FWS\ROD
 */
class Compatibility {


    // list of methods with compatibility patches
    protected static $Patches= [
        'wpGraphQL',
        'ACF',
        'RegenerateThumbnails',
    ];


    /**
     * Initialize.
     */
    public static function Init() {

        // execute all patchers
        foreach(self::$Patches as $Patch) {
            call_user_func([__CLASS__, $Patch]);
        }
    }


    /**
     * Compatibility solution for WPGraphQL.
     * Media resolver for "sourceUrl" firstly collect URLs of all registered sizes and then pick only (requested) one,
     * unfortunately that rebuilds all thumbnails.
     * This filter will override that resolver to touch only size that is requested.
     */
    protected static function WPGraphQL() {

        // override graphql image resolver
        add_filter( 'graphql_field_definition', function($field, $type_name) {
            if($field->name === 'sourceUrl' && $type_name === 'MediaItem') {
                $field->resolveFn = function ($image, $args, $context, $info) {
                    $size = isset($args['size']) ? ('full' === $args['size'] ? 'large' : $args['size']) : null;
                    $image->setup();
                    return !$size
                        ? $image->sourceUrl
                        : wp_get_attachment_image_src($GLOBALS['post']->ID, $size)[0];
                };
            }
            return $field;
        }, 10, 2);
    }


    /**
     * Compatibility solution for ACF plugin.
     * Field "Gallery" calling function "wp_prepare_attachment_for_js" which will regenerate all sizes
     * but uses only "thumbnail" result.
     * This filter will allow only "thumbnail" image generation.
     */
    protected static function ACF() {

        // filter "image_downsize" before our main filter
        add_filter('image_downsize', function($Out, $Id, $Size) {
            global $wp_current_filter;
            if (!in_array('acf/render_field/type=gallery', $wp_current_filter)) {
                return $Out;
            }
            return $Size === 'thumbnail'
                ? false
                : [];
        }, 9, 3);
    }


    /**
     * Compatibility solution for Regenerate Thumbnails plugin.
     * Prevents missing thumbnails from being generated when handled by this plugin.
     */
    protected static function RegenerateThumbnails() {

        add_filter('regenerate_thumbnails_missing_thumbnails', function($sizes, $fullsize_metadata = [], $_instance = null) {
            return Hooks::RemoveSizesFromAutoResizing($sizes, $fullsize_metadata);
        });
    }

}