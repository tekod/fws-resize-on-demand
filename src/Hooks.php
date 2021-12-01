<?php namespace FWS\ROD;

/**
 * Class Hooks
 * @package FWS\ROD
 */
class Hooks {


    /**
     * Initialize monitoring.
     */
    public static function Init() {

        add_filter('image_downsize', [__CLASS__, 'OnImageDownsize'], 10, 3);
        add_filter('intermediate_image_sizes_advanced', [__CLASS__, 'RemoveSizesFromAutoResizing'], 10, 3);
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

        Services::Log("OnImageDownsize: id:$Id -> '$Size' size.");

        // skip if already resolved by previous filter
        if ($Out !== false) {
            Services::Log('Image already resolved by previous filters. Skip.');
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
            Services::Log('Thumb already exist. Skip.');
            return false;
        }

        // Skip if $Id does not refer to a valid attachment
        if ($ImageData === false) {
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

        $RegisteredSizes= self::GetRegisteredSizes();
        $SizeData= $RegisteredSizes[$Size];
        Services::Log("Resize '$ImageData[file]' to '$Size' size ($SizeData[width]x$SizeData[height])", true);

        // first search for higher-resolution sizes to properly create "srcset"
        $HighResolutionPattern = '/^' . preg_quote($Size, '/') . '@[1-9]+(\\.[0-9]+)?x$/';
        foreach ($RegisteredSizes as $SubName => $SubData) {
            if (!isset($ImageData['sizes'][$SubName]) && preg_match($HighResolutionPattern, $SubName)) {
                Services::Log("Resize to higher-resolution size '$SubName'.");
                self::ResizeSingleImage($ImageData, $Id, $SubName, $SubData); // resize and ignore result
            }
        }

        // now resize image to requested image-size
        return self::ResizeSingleImage($ImageData, $Id, $Size, $SizeData);
    }


    /**
     * Perform actual image resizing.
     *
     * @param array $ImageData
     * @param int $Id
     * @param string $SizeName
     * @param array $SizeData
     * @return array|false
     */
    protected static function ResizeSingleImage($ImageData, $Id, $SizeName, $SizeData) {

        // make the new thumb
        $Resized = image_make_intermediate_size(
            get_attached_file($Id),
            $SizeData['width'],
            $SizeData['height'],
            $SizeData['crop']
        );
        if (!$Resized) {
            Services::Log('Failed to resize. Exit.');
            return false;   // resizing failed
        }

        // save image meta, or WP can't see that the thumb exists now
        $ImageData['sizes'][$SizeName]= $Resized;
        wp_update_attachment_metadata($Id, $ImageData);
        Services::Log("Successfully created '$Resized[file]'.");

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

        return did_action('add_attachment')
            ? ['thumbnail' => __('Thumbnail')]
            : $Names;
    }

}

