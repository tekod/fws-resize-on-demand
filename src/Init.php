<?php namespace FWS\ROD;

/**
 * Class Init
 * @package FWS\ROD
 */
class Init {


    public static function InitServices() : void {

        // load configuration
        require 'Config.php';
        Config::Init();

        // run compatibility patches
        require 'Compatibility.php';
        Compatibility::Init();

        // register dashboard
        if (is_admin()) {
            require 'Dashboard.php';
            Dashboard::Init();
        }

        // register background service
        if (empty(static::RequirementsReport())) {
            require 'Hooks.php';
            Hooks::Init();
        }
    }


    /**
     * Check are all plugin requirements are meet.
     *
     * @return array
     */
    protected static function RequirementsReport() : array {

        $Report= [];

        // some custom logic to detect requirements

        return $Report;
    }

}

