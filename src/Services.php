<?php namespace FWS\ROD;

/**
 * Class Services
 * @package FWS\ROD
 */
class Services {


    // cached configuration
    protected static $SizesToHandle= null;

    protected static $LogPath= '';


    /**
     * Initialize services.
     */
    public static function Init() {

        self::InitLogFile();
    }


    /**
     * Get list of image sizes to handle.
     *
     * @return array
     */
    public static function GetSizesToHandle() {

        if (self::$SizesToHandle === null) {
            // get from configuration
            $Settings= Config::Get();
            self::$SizesToHandle= $Settings['HandleSizes'];
            // apply filters
            self::$SizesToHandle= self::ApplyFiltersOnSizesToHandle(self::$SizesToHandle);
            // if there is anything changed store new configuration
            self::MaybeSaveSettings(self::$SizesToHandle, $Settings['HandleSizes']);
        }
        return self::$SizesToHandle;
    }


    /**
     * Apply custom filter hooks to alter list of handled image sizes.
     *
     * @param array $HandleSizes
     * @return array
     */
    public static function ApplyFiltersOnSizesToHandle($HandleSizes) {

        // handle more sizes via filter
        $ForceHandleSizes= apply_filters('fws_rod_enable_sizes', []);
        $HandleSizes= array_unique(array_merge($HandleSizes, $ForceHandleSizes));

        // disable sizes via filter
        $ForceDisableSizes= apply_filters('fws_rod_disable_sizes', []);
        $HandleSizes= array_diff($HandleSizes, $ForceDisableSizes);

        // return list of image sizes to handle
        return array_filter($HandleSizes);
    }


    /**
     * Check is anything changed in list of handled sizes and update configuration.
     *
     * @param array $Current
     * @param array $Loaded
     */
    protected static function MaybeSaveSettings($Current, $Loaded) {

        if (count($Current) === count($Loaded) && array_diff($Current, $Loaded) === array_diff($Loaded, $Current)) {
            return;
        }
        $NewSettings= [
            'HandleSizes' => $Current,
        ];
        Config::Set($NewSettings);
        Config::Save();
    }


    /**
     * Store log message.
     *
     * @param string $Message
     * @param bool $ShowCallStack
     */
    public static function Log($Message, $ShowCallStack=false) {

        // skip if not enabled
        if (!Config::Get()['EnableLogging']) {
            return;
        }

        // prepare header
        $Header= "\n\n".date('H:i:s');

        // prepare call stack info
        if ($ShowCallStack) {
            $CallStack= array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 4);
            $List= [];
            foreach($CallStack as $Step) {
                $List[]= (isset($Step['file']) ? basename($Step['file']) : "unknown file")
                        .(isset($Step['line']) ? "[$Step[line]]" : "[?]");
            }
            $Message.= "\n  Call stack: " . implode(' > ', array_reverse($List));
        }

        // append log content
        file_put_contents(self::$LogPath, "$Header  $Message", FILE_APPEND);
    }


    /**
     * Initialize log storage.
     */
    protected static function InitLogFile() {

        // calc path to log storage
        self::$LogPath= wp_upload_dir()['basedir'].'/logs/fws-rod.log';

        // skip if not enabled
        if (!Config::Get()['EnableLogging']) {
            return;
        }

        // create directory if missing, protect it from direct access
        $Dir= dirname(self::$LogPath);
        if (!is_dir($Dir)) {
            mkdir($Dir);
            file_put_contents("$Dir/.htaccess", 'deny from all');
            file_put_contents("$Dir/index.html", '');
        }

        // rotate log file if larger then 256kb
        $SizeLimit= 2 << 18;
        if (@filesize(self::$LogPath) > $SizeLimit) {
            $Header= '<log-rotate>     .   .   .  . . . .......';
            $NewDump= file_get_contents(self::$LogPath, false, null, -($SizeLimit * 0.75));
            file_put_contents(self::$LogPath, $Header.$NewDump);
        }
    }


    /**
     * Expose path to logger file.
     *
     * @return string
     */
    public static function GetLogFilePath() {

        return self::$LogPath;
    }

}

