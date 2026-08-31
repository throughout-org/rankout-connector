<?php
namespace RankOut_Connector\History;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Change_Redactor {
    const MAX_BYTES = 262144; 

    private static $exact_keys = array(
        'user_pass', 'user_activation_key', 'session_tokens', 'user_email',
    );

    private static $substring_keys = array(
        '_secret', '_token', 'api_key', 'apikey', 'password', 'secret',
    );

    







    public static function redact( $value ) {
        if ( ! is_array( $value ) ) {
            return array( 'value' => $value, 'truncated' => false, 'size_bytes' => is_string( $value ) ? strlen( $value ) : 0 );
        }
        $truncated = false;
        $size      = 0;
        $out       = self::walk( $value, $truncated, $size );
        return array( 'value' => $out, 'truncated' => $truncated, 'size_bytes' => $size );
    }

    private static function walk( $value, &$truncated, &$size ) {
        if ( is_array( $value ) ) {
            $out = array();
            foreach ( $value as $k => $v ) {
                if ( self::is_sensitive( (string) $k ) ) {
                    $out[ $k ] = '[REDACTED]';
                    continue;
                }
                $out[ $k ] = self::walk( $v, $truncated, $size );
            }
            return $out;
        }
        if ( is_string( $value ) ) {
            $len   = strlen( $value );
            $size += $len;
            if ( $len > self::MAX_BYTES ) {
                $truncated = true;
                return substr( $value, 0, self::MAX_BYTES );
            }
        }
        return $value;
    }

    




    public static function key_is_sensitive( $key ) {
        return self::is_sensitive( (string) $key );
    }

    private static function is_sensitive( $key ) {
        $lc = strtolower( $key );
        if ( in_array( $lc, self::$exact_keys, true ) ) {
            return true;
        }
        foreach ( self::$substring_keys as $needle ) {
            if ( strpos( $lc, $needle ) !== false ) {
                return true;
            }
        }
        return false;
    }
}
