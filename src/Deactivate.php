<?php namespace Tekod\ROD;


class Deactivate {

    public static function Deactivate() {

        flush_rewrite_rules();
    }

}