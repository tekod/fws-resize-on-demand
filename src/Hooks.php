<?php namespace FWS\ROD;

/**
 * Class Hooks
 * @package FWS\ROD
 */
class Hooks {


    // cached configuration
    protected static $SizesToHandle= null;


    /**
     * Initialize monitoring.
     */
    public static function Init() {

        add_filter('image_downsize', [__CLASS__, 'OnImageDownsize'], 10, 3);
        add_filter('intermediate_image_sizes_advanced', [__CLASS__, 'DisableSizes'], 10, 3);
        add_filter('image_size_names_choose', [__CLASS__, 'DisableNameChoose'], 10, 1);
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
            return $Out;
        }

        // skip if requested size is array, it is not situation we want to handle
        if (is_array($Size)) {
            return false;
        }

        // skip if requested size is not registered
        if (!isset(self::GetRegisteredSizes()[$Size])) {
            return false;
        }
        // skip if thumbnail already exists
        $ImageData = wp_get_attachment_metadata($Id);
        if (is_array($ImageData) && isset($ImageData['sizes'][$Size])) {
            return false;
        }

        // resize now
        return self::Resize($ImageData, $Id, $Size);
    }


    /**
     * Return list of registered image sizes.
     *
     * @return array
     */
    protected static function GetRegisteredSizes() {

        return wp_get_registered_image_subsizes();

        //global $_wp_additional_image_sizes;
        //return $_wp_additional_image_sizes;
    }


    /**
     * Resize image.
     *
     * @param array $ImageData
     * @param int $Id
     * @param string $Size
     * @return array|false
     */
    protected static function Resize($ImageData, $Id, $Size) {

        $RegisteredSizes = self::GetRegisteredSizes();

        // the image sizes to handle
        $SizesToGenerate = [];

        // add high-resolution variants of the requested size
        $HighResolutionPattern = '/^' . preg_quote($Size, '/') . '@[1-9]+(\\.[0-9]+)?x$/';
        foreach ($RegisteredSizes as $RegisteredSizeName => $_RegisteredSizeData) {
            if (
                preg_match($HighResolutionPattern, $RegisteredSizeName) &&
                !isset($ImageData['sizes'][$RegisteredSizeName])
            ) {
                $SizesToGenerate[] = $RegisteredSizeName;
            }
        }

        // add requested size last to return the correct $Resized data
        $SizesToGenerate[] = $Size;

        foreach ($SizesToGenerate as $SizeToGenerate) {
            $SizeData = $RegisteredSizes[$SizeToGenerate];

            // make the new thumb
            $Resized = image_make_intermediate_size(
                get_attached_file($Id),
                $SizeData['width'],
                $SizeData['height'],
                $SizeData['crop']
            );

            if ($Resized) {
                // save image meta, or WP can't see that the thumb exists now
                $ImageData['sizes'][$SizeToGenerate]= $Resized;
                wp_update_attachment_metadata($Id, $ImageData);
            } elseif ($SizeToGenerate === $Size) {
                // resizing of requested size failed
                return false;
            }
        }

        // return the array for displaying the resized image
        return array(
            dirname(wp_get_attachment_url($Id)) . '/' . $Resized['file'],
            $Resized['width'],
            $Resized['height'],
            true
        );
    }


    /**
     * Remove specified images from list for automatic resizing.
     *
     * @param array $Sizes         An associative array of registered thumbnail image sizes.
     * @param array $Metadata      An associative array of fullsize image metadata: width, height, file.
     * @param int   $AttachmentId  Attachment ID. Only passed from WP 5.0+.
     * @return mixed
     */
    public static function DisableSizes($Sizes, $Metadata, $AttachmentId=null) {

        foreach(self::GetSizesToHandle() as $Size) {
            unset($Sizes[$Size]);
        }
        return $Sizes;
    }


    /**
     * Get list of image sizes to handle.
     *
     * @return array
     */
    protected static function GetSizesToHandle() {

        if (self::$SizesToHandle === null) {
            $Options = Config::Get();
            self::$SizesToHandle= $Options['HandleSizes'];
        }
        return self::$SizesToHandle;
    }


    /**
     * Prevent function "wp_prepare_attachment_for_js" to create all sizes during uploading process.
     *
     * @param array $Names
     * @return array
     */
    public static function DisableNameChoose($Names) {

        return did_action('add_attachment')
            ? ['thumbnail' => __('Thumbnail')]
            : $Names;
    }

}

