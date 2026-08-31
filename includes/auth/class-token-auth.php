<?php
namespace RankOut_Connector\Auth;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Token_Auth {
    private $token_manager;

    public function __construct( Token_Manager $token_manager ) {
        $this->token_manager = $token_manager;
    }

    public function authenticate( \WP_REST_Request $request ) {
        $auth_header = $request->get_header( 'authorization' );
        if ( empty( $auth_header ) ) {
            return new \WP_Error( 'no_auth', __( 'Missing Authorization header.', 'rankout-connector' ) );
        }
        if ( 0 !== stripos( $auth_header, 'Bearer ' ) ) {
            return new \WP_Error( 'invalid_auth', __( 'Authorization header must use Bearer scheme.', 'rankout-connector' ) );
        }
        $raw_token = substr( $auth_header, 7 );
        if ( empty( $raw_token ) ) {
            return new \WP_Error( 'empty_token', __( 'Bearer token is empty.', 'rankout-connector' ) );
        }
        $token = $this->token_manager->validate_token( $raw_token );
        if ( false === $token ) {
            return new \WP_Error( 'invalid_token', __( 'Invalid or expired token.', 'rankout-connector' ) );
        }
        if ( ! $this->is_ip_allowed() ) {
            return new \WP_Error( 'ip_forbidden', __( 'Access denied: your IP address is not whitelisted.', 'rankout-connector' ) );
        }
        return array( 'token_id' => (int) $token['id'], 'wp_user_id' => (int) $token['wp_user_id'] );
    }

    public function check_ip_allowed() {
        return $this->is_ip_allowed();
    }

    private function is_ip_allowed() {
        $whitelist_raw = \get_option( 'rankout_connector_ip_whitelist', '' );
        if ( empty( trim( $whitelist_raw ) ) ) {
            return true; 
        }
        
        
        
        $cache_key = 'ip_wl_' . substr( md5( $whitelist_raw ), 0, 12 );
        $entries   = \wp_cache_get( $cache_key, 'rankout_connector' );
        if ( false === $entries ) {
            $entries = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $whitelist_raw ) ) );
            \wp_cache_set( $cache_key, $entries, 'rankout_connector', 300 );
        }
        $remote_ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? \sanitize_text_field( \wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $remote_ip  = $this->normalize_ip( $remote_ip );
        if ( ! filter_var( $remote_ip, FILTER_VALIDATE_IP ) ) {
            return false;
        }
        foreach ( $entries as $entry ) {
            if ( false !== strpos( $entry, '/' ) ) {
                
                
                $entry = $this->normalize_cidr_entry( $entry );
                if ( $this->ip_in_cidr( $remote_ip, $entry ) ) {
                    return true;
                }
            } else {
                $normalized_entry = $this->normalize_ip( $entry );
                if ( ! filter_var( $normalized_entry, FILTER_VALIDATE_IP ) ) {
                    continue;
                }
                
                
                if ( inet_pton( $remote_ip ) === inet_pton( $normalized_entry ) ) {
                    return true;
                }
            }
        }
        return false;
    }

    private function normalize_ip( $ip ) {
        
        
        $lower = strtolower( $ip );
        if ( false !== strpos( $lower, ':' ) ) {
            
            $bin = filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ? inet_pton( $ip ) : false;
            if ( false !== $bin && 16 === strlen( $bin ) ) {
                
                $prefix = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff";
                if ( 0 === strncmp( $bin, $prefix, 12 ) ) {
                    $ipv4 = inet_ntop( substr( $bin, 12 ) );
                    if ( false !== $ipv4 && filter_var( $ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
                        return $ipv4;
                    }
                }
            }
        }
        return $ip;
    }

    private function normalize_cidr_entry( $cidr ) {
        if ( substr_count( $cidr, '/' ) !== 1 ) {
            return $cidr;
        }
        list( $subnet, $prefix ) = explode( '/', $cidr, 2 );
        $normalized = $this->normalize_ip( $subnet );
        return $normalized . '/' . $prefix;
    }

    private function ip_in_cidr( $ip, $cidr ) {
        
        if ( substr_count( $cidr, '/' ) !== 1 ) {
            return false;
        }
        list( $subnet, $raw_prefix ) = explode( '/', $cidr, 2 );
        if ( ! ctype_digit( $raw_prefix ) ) {
            return false;
        }
        $prefix = (int) $raw_prefix;
        
        if ( false !== strpos( $ip, ':' ) ) {
            if ( $prefix > 128 ) {
                return false;
            }
            if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
                return false;
            }
            if ( ! filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
                return false;
            }
            $ip_bin     = inet_pton( $ip );
            $subnet_bin = inet_pton( $subnet );
            if ( false === $ip_bin || false === $subnet_bin ) {
                return false;
            }
            
            $mask_bin = $this->build_mask_bin( $prefix, 16 );
            
            return ( $ip_bin & $mask_bin ) === ( $subnet_bin & $mask_bin );
        }
        
        if ( $prefix > 32 ) {
            return false;
        }
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
            return false;
        }
        if ( ! filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
            return false;
        }
        $ip_bin     = inet_pton( $ip );
        $subnet_bin = inet_pton( $subnet );
        if ( false === $ip_bin || false === $subnet_bin ) {
            return false;
        }
        
        $mask_bin = $this->build_mask_bin( $prefix, 4 );
        
        return ( $ip_bin & $mask_bin ) === ( $subnet_bin & $mask_bin );
    }

    private function build_mask_bin( int $prefix, int $total_bytes ): string {
        $full_bytes   = (int) ( $prefix / 8 );
        $partial_bits = $prefix % 8;
        $mask_bin     = str_repeat( "\xFF", $full_bytes );
        if ( $partial_bits > 0 ) {
            $mask_bin .= chr( ( 0xFF << ( 8 - $partial_bits ) ) & 0xFF );
        }
        return str_pad( $mask_bin, $total_bytes, "\x00" );
    }
}
