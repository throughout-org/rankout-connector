<?php
namespace RankOut_Connector\History;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Change_Context {
    private static $data   = array();
    private static $active = false;

    public static function set( array $fields ) {
        self::$data   = array_merge( self::$data, $fields );
        self::$active = true;
    }

    public static function get( $key, $default = null ) {
        return isset( self::$data[ $key ] ) ? self::$data[ $key ] : $default;
    }

    public static function all() {
        return self::$data;
    }

    public static function is_active() {
        return self::$active;
    }

    public static function clear() {
        self::$data   = array();
        self::$active = false;
    }
}
