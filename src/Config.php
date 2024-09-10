<?php namespace Tekod\ROD;


/**
 * Class Config
 * @package Tekod\ROD
 */
class Config {

    // buffer for loaded configuration
    protected static $Settings= [];

    // default structure
    protected static $DefaultSettings= [
        'HandleSizes'=> [], // array of size names
        'EnableLogging'=> false,
        'JpegCompression' => [],
    ];

    // "options" table identifier
    public static $OptionName= 'fws_resize_on_demand';


    /**
     * Load configuration.
     *
     * @param array $Settings
     */
    public static function Init() {

        // load from "options" table
        $Settings= unserialize(get_option(self::$OptionName)) ?: [];

        // set settings
        self::Set($Settings);
    }


    /**
     * Return loaded configuration.
     *
     * @return array
     */
    public static function Get() {

        return self::$Settings;
    }


    /**
     * Set configuration.
     */
    public static function Set($Settings) {

        // merge with current settings
        $Settings= $Settings + self::$Settings;

        // resolve and store in cache
        self::$Settings= self::ResolveSettings($Settings);
    }


    /**
     * Store configuration in database.
     */
    public static function Save() {

        update_option(self::$OptionName, serialize(self::$Settings));
    }


    /**
     * Validate each parts of settings.
     *
     * @param $Settings
     * @return array
     */
    protected static function ResolveSettings($Settings) {

        // ensure var type
        if (!is_array($Settings)) {
            $Settings = [];
        }

        // add missing keys and store in property
        $Settings += self::$DefaultSettings;

        // perform deeper resolving if needed

        // return structure
        return $Settings;
    }

}