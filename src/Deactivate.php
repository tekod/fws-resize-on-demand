<?php namespace FWS\ROD;


class Deactivate {

    public static function Deactivate() {

        flush_rewrite_rules();
    }

}