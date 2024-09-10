<?php namespace Tekod\ROD;

/**
 * Class Image
 * @package Tekod\ROD
 */
class Image {


    // current image meta-data
    protected static $ImageMetaData= [];

    // buffer for preloaded meta-data of all images
    protected static $MetaCache;



    /**
     * Downsize image.
     *
     * @param int $Id
     * @param string $Size
     * @return array|false
     */
    public static function Downsize($Id, $Size) {

        // skip if requested size is not registered
        $SizeData= self::GetRegisteredSizes()[$Size] ?? null;
        if (!$SizeData) {
            return false;
        }

        // load data
        self::LoadImageMetaData($Id);

        // skip if $Id does not refer to a valid attachment
        if (!is_array(self::$ImageMetaData)) {
            Services::Log('Attachment data not found. Skip.');
            return false;
        }

        // skip if thumbnail already exists
        if (isset(self::$ImageMetaData['sizes'][$Size])) {
            Services::Log('Thumb already exist. Skip.');
            //return false;
            return [
                str_replace(wp_basename(self::$ImageMetaData['file']), self::$ImageMetaData['sizes'][$Size]['file'], wp_get_attachment_url($Id)),
                self::$ImageMetaData['sizes'][$Size]['width'],
                self::$ImageMetaData['sizes'][$Size]['height'],
                true,
            ];
        }

        // skip on upscale
        $Dim= image_resize_dimensions(
            intval(self::$ImageMetaData['width']),
            intval(self::$ImageMetaData['height']),
            $SizeData['width'],
            $SizeData['height'],
            $SizeData['crop']
        );
        if (!$Dim || $Dim[6] < $Dim[4] || $Dim[7] < $Dim[5]) {
            Services::Log("Upscale attempt. Skip.");
            return false;
        }

        // resize now
        Services::Log('resizing: ' . json_encode($Dim));
        return self::Resize($Id, $Size, false);
    }


    /**
     * Initialize current image meta-data.
     *
     * @param int $Id
     */
    public static function LoadImageMetaData($Id) {

        self::$ImageMetaData = self::GetMetaData($Id);
    }


    /**
     * Return list of registered image sizes.
     *
     * @return array
     */
    public static function GetRegisteredSizes() {

        static $Cached;
        if (!$Cached) {
            $Cached= wp_get_registered_image_subsizes();
        }
        return $Cached;
    }


    /**
     * Load and return meta data, pre-load all meta entries to speed up.
     *
     * @param int $AttId
     * @return array|false
     */
    public static function GetMetaData($AttId) {

        global $wpdb;

        if (self::$MetaCache === null) {
            $Rows= $wpdb->get_results("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key='_wp_attachment_metadata'", ARRAY_A);
            foreach ($Rows as $Row) {
                self::$MetaCache[$Row['post_id']] = @unserialize($Row['meta_value']);
            }
        }
        return self::$MetaCache[$AttId] ?? false;
    }


    /**
     * Resize image.
     *
     * @param int $Id
     * @param string $Size
     * @param bool $ReturnMeta
     * @return array|false
     */
    public static function Resize($Id, $Size, $ReturnMeta) {

        $RegisteredSizes= self::GetRegisteredSizes();
        $SizeData= $RegisteredSizes[$Size];

        // log event
        Services::Log("Resize '".self::$ImageMetaData['file']."' to '$Size' size ($SizeData[width]x$SizeData[height])", true);

        // first search for higher-resolution sizes to properly create "srcset"
        $HighResolutionPattern = '/^' . preg_quote($Size, '/') . '@[1-9]+(\\.[0-9]+)?x$/';
        foreach ($RegisteredSizes as $SubName => $SubData) {
            if (!isset(self::$ImageMetaData['sizes'][$SubName]) && preg_match($HighResolutionPattern, $SubName)) {
                Services::Log("Resize to higher-resolution size '$SubName'.");
                self::ResizeSingleImage($Id, $SubName, $SubData, $ReturnMeta); // resize and ignore result
            }
        }

        // now resize image to requested image-size
        return self::ResizeSingleImage($Id, $Size, $SizeData, $ReturnMeta);
    }


    /**
     * Perform actual image resizing.
     *
     * @param int $Id
     * @param string $SizeName
     * @param array $SizeData
     * @param bool $ReturnMeta
     * @return array|false
     */
    protected static function ResizeSingleImage($Id, $SizeName, $SizeData, $ReturnMeta) {

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
        self::$ImageMetaData['sizes'][$SizeName]= $Resized;
        self::$MetaCache[$Id]= self::$ImageMetaData;
        if (!$ReturnMeta) {  // improving performances, metadata will be stored by caller
            wp_update_attachment_metadata($Id, self::$ImageMetaData);
        }

        // log event
        Services::Log("Successfully created '$Resized[file]'.");
        Services::Log('ImageMetaData: '.json_encode(self::$ImageMetaData['sizes']));

        // return the array for displaying the resized image (or only meta values)
        return $ReturnMeta
            ? $Resized
            : [
                dirname(wp_get_attachment_url($Id)) . '/' . $Resized['file'],
                $Resized['width'],
                $Resized['height'],
                true
            ];
    }


    /**
     * Regenerate thumbnails for specified post (attachment).
     *
     * @param int $Id
     * @param string $UploadsDir
     * @param array $AvoidSizes
     * @param array $AvoidMimeTypes
     * @param bool $MissingOnly
     * @return int
     */
    public static function RegenerateAttachmentThumbs($Id, $UploadsDir, $AvoidSizes, $AvoidMimeTypes, $MissingOnly) {

        global $wpdb;
        $SQL= "SELECT * FROM `{$wpdb->prefix}postmeta` WHERE `post_id`=".intval($Id)." AND `meta_key`='_wp_attachment_metadata'";
        $Rows= $wpdb->get_results($SQL, ARRAY_A);
        if (!$Rows) {
            return 0;
        }

        // unpack meta value
        $Meta = unserialize($Rows[0]['meta_value']) ?? [];
        if (!$Meta || !isset($Meta['sizes'])) {
            return 0;   // probably not an image
        }

        // log
        Services::Log('Regenerating attachment #'.$Id.' (' . json_encode($Meta) . ')');

        // remove existing thumbs
        foreach ($Meta['sizes'] as $Key => $SizeData) {

            // should we avoid this thumbnail?
            if (in_array($Key, $AvoidSizes) || in_array($SizeData['mime-type'], $AvoidMimeTypes, true) || $MissingOnly) {
                continue;
            }

            // delete file if exist
            $ThumbPath = $UploadsDir . '/' . dirname($Meta['file']) . '/' . $SizeData['file'];
            if (is_file($ThumbPath)) {
                unlink($ThumbPath);
            }

            // remove from record
            $Meta= self::RemoveSizeFromMetaPack($Meta, $Key);
        }

        // add new thumbs
        $CountOfRegeneratedThumbs= 0;
        Image::LoadImageMetaData($Id);
        foreach (self::GetRegisteredSizes() as $SizeName => $SizeData) {
            if (isset($Meta['sizes'][$SizeName]) || in_array($SizeName, $AvoidSizes) ) {
                continue;
            }
            Services::Log('regenerating thumb to "'.$SizeName.'"');
            // skip on upscale
            $Dim= image_resize_dimensions(
                intval($Meta['width']),
                intval($Meta['height']),
                $SizeData['width'],
                $SizeData['height'],
                $SizeData['crop']
            );
            if (!$Dim || $Dim[6] < $Dim[4] || $Dim[7] < $Dim[5]) {
                Services::Log("Upscale attempt. Skip.");
                continue;
            }
            // resize now
            Services::Log('resizing: ' . json_encode($Dim));
            $Result= self::Resize($Id, $SizeName, true);
            if (!$Result) {
                continue;
            }
            // update meta
            $Meta['sizes'][$SizeName]= $Result;
            $CountOfRegeneratedThumbs++;
        }

        // update meta field
        if (serialize($Meta) !== $Rows[0]['meta_value']) {
            wp_update_attachment_metadata($Id, $Meta);
        }

        // return count
        return $CountOfRegeneratedThumbs;
    }


    /**
     * Remove specified size from meta-data and return new meta-data.
     * Also, remove all other sizes that points to the same thumbnail file.
     *
     * @param array $MetaPack
     * @param string $SizeName
     * @return array
     */
    public static function RemoveSizeFromMetaPack($MetaPack, $SizeName) {

        $FileName = $MetaPack['sizes'][$SizeName]['file'];
        foreach ($MetaPack['sizes'] as $Key => $Data) {
            if ($Data['file'] === $FileName) {
                unset($MetaPack['sizes'][$Key]);
            }
        }
        return $MetaPack;
    }

}

