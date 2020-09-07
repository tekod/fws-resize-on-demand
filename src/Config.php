<?php namespace FWS\ROD;


/**
 * Class Config
 * @package FWS\ROD
 */
class Config {

    // buffer for loaded configuration
    protected static $Settings;

    // default structure
    protected static $DefaultSettings= [
        'HandleSizes'=> [], // array of size names
    ];


    /**
     * Load configuration.
     *
     * @param array $Settings
     */
    public static function Init() {

        // load from "options" table
        $Settings= unserialize(get_option('fws_resize_on_demand')) ?? [];

        // resolve and set settings
        self::$Settings= self::ResolveSettings($Settings);
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