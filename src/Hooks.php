<?php namespace Tekod\ROD;

/**
 * Class Hooks
 * @package Tekod\ROD
 */
class Hooks {


    /**
     * Initialize monitoring.
     */
    public static function Init() {

        // catch image resizing events
        add_filter('image_downsize', [__CLASS__, 'OnImageDownsize'], 10, 3);
        add_filter('intermediate_image_sizes_advanced', [__CLASS__, 'RemoveSizesFromAutoResizing'], 10, 3);
        add_filter('image_size_names_choose', [__CLASS__, 'DisableNameChoose'], 10, 1);

        // adjust jpeg quality
        add_filter('jpeg_quality', [__CLASS__, 'OnJpegQuality'], 10, 1);
    }


    /**
     * Hook on filter "image_downsize" to manually resize image that are in our jurisdiction.
     *
     * @param bool $Out
     * @param int $Id
     * @param string $Size
     * @return array|false
     */
    public static function OnImageDownsize($Out, $Id, $Size) {

        // skip if already resolved by previous filter
        if ($Out !== false) {
            Services::Log('Image already resolved by previous filters. Skip.');
            return $Out;
        }

        // skip if requested size is array, it is not situation we want to handle
        if (is_array($Size)) {
            return false;
        }

        // log event
        Services::Log("OnImageDownsize: id:$Id -> '$Size' size.");

        // try to perform downsizing
        require_once __DIR__.'/Image.php';
        return Image::Downsize($Id, $Size);
    }


    /**
     * Remove specified images from list for automatic resizing.
     * This method is listener of "intermediate_image_sizes_advanced" filter hook.
     *
     * @param array $Sizes         An associative array of registered thumbnail image sizes.
     * @param array $Metadata      An associative array of fullsize image metadata: width, height, file.
     * @param int   $AttachmentId  Attachment ID. Only passed from WP 5.0+.
     * @return mixed
     */
    public static function RemoveSizesFromAutoResizing($Sizes, $Metadata, $AttachmentId=null) {

        foreach(Services::GetSizesToHandle() as $Size) {
            unset($Sizes[$Size]);
        }
        return $Sizes;
    }


    /**
     * Prevent function "wp_prepare_attachment_for_js" to create all sizes during uploading process.
     * This method is listener of "image_size_names_choose" filter hook.
     *
     * @param array $Names
     * @return array
     */
    public static function DisableNameChoose($Names) {

        return ['thumbnail' => __('Thumbnail')];

        return did_action('add_attachment')
            ? ['thumbnail' => __('Thumbnail')]
            : $Names;
    }


    /**
     * Apply custom jpeg quality.
     *
     * @param $Quality
     * @return void
     */
    public static function OnJpegQuality($Quality) {

        $CustomJpegQuality= Services::GetCustomJpegQuality();
        if ($CustomJpegQuality !== null) {
            $Quality= $CustomJpegQuality;
        }
        return $Quality;
    }

}

