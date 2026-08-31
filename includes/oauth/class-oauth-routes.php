<?php
namespace RankOut_Connector\OAuth;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}









class OAuth_Routes {

    




    const NAMESPACE_V1 = 'rankout-connector/v1';

    




    public function register_routes() {
        

        register_rest_route(
            self::NAMESPACE_V1,
            '/.well-known/oauth-protected-resource',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( new Discovery(), 'get_protected_resource_metadata' ),
                'permission_callback' => '__return_true',
            )
        );

        
        
        
        

        

        register_rest_route(
            self::NAMESPACE_V1,
            '/oauth/register',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( new Client_Registry(), 'handle_register' ),
                'permission_callback' => '__return_true',
            )
        );

        
        

        

        register_rest_route(
            self::NAMESPACE_V1,
            '/oauth/token',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( new Token_Endpoint(), 'handle' ),
                'permission_callback' => '__return_true',
            )
        );

        

        register_rest_route(
            self::NAMESPACE_V1,
            '/oauth/revoke',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'handle_revoke' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    








    public function handle_revoke( \WP_REST_Request $request ) {
        
        
        $transport_error = Token_Endpoint::enforce_transport_security();
        if ( null !== $transport_error ) {
            return $transport_error;
        }

        
        
        
        
        $ip = isset( $_SERVER['REMOTE_ADDR'] )
            ? trim( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ), '[]' )
            : 'unknown';

        $per_ip_key    = 'rankout_connector_revoke_rl_' . md5( $ip );
        $global_key    = 'rankout_connector_revoke_rl_global';
        $per_ip_limit  = 60;
        $global_limit  = 600;

        if ( \wp_using_ext_object_cache() ) {
            \wp_cache_add( $per_ip_key, 0, 'rankout_connector', HOUR_IN_SECONDS );
            $ip_count = \wp_cache_incr( $per_ip_key, 1, 'rankout_connector' );

            \wp_cache_add( $global_key, 0, 'rankout_connector', HOUR_IN_SECONDS );
            $global_count = \wp_cache_incr( $global_key, 1, 'rankout_connector' );
        } else {
            
            $ip_count     = Token_Endpoint::rl_transient_increment( $per_ip_key );
            $global_count = Token_Endpoint::rl_transient_increment( $global_key );
        }

        if ( $ip_count > $per_ip_limit || $global_count > $global_limit ) {
            return new \WP_REST_Response( null, 429 );
        }

        
        
        
        $token     = is_string( $request->get_param( 'token' ) ) ? $request->get_param( 'token' ) : '';
        $hint      = sanitize_text_field( $request->get_param( 'token_type_hint' ) );
        
        $client_id = sanitize_text_field( $request->get_param( 'client_id' ) );

        
        
        
        
        
        if ( '' === $token || '' === $client_id ) {
            $response = new \WP_REST_Response( '', 200 );
            $response->header( 'Content-Length', '0' );
            $response->header( 'Referrer-Policy', 'no-referrer' );
            $response->header( 'X-Content-Type-Options', 'nosniff' );
            $response->header( 'Cache-Control', 'no-store' );
            return $response;
        }

        $token_hash = hash( 'sha256', $token );
        $manager    = new OAuth_Token_Manager();

        
        $looks_like_access = ( 0 === strpos( $token, 'wpmcp_oat_' ) );

        
        
        
        
        
        if ( 'refresh_token' === $hint || ! $looks_like_access ) {
            if ( ! $manager->revoke_by_refresh_hash( $token_hash, $client_id ) ) {
                $manager->revoke_token( $token_hash, $client_id );
            }
        } else {
            if ( ! $manager->revoke_token( $token_hash, $client_id ) ) {
                $manager->revoke_by_refresh_hash( $token_hash, $client_id );
            }
        }

        
        $response = new \WP_REST_Response( '', 200 );
        $response->header( 'Content-Length', '0' );
        $response->header( 'Referrer-Policy', 'no-referrer' );
        $response->header( 'X-Content-Type-Options', 'nosniff' );
        $response->header( 'Cache-Control', 'no-store' );

        return $response;
    }
}
