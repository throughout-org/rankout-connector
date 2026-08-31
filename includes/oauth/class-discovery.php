<?php
namespace RankOut_Connector\OAuth;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}









class Discovery {

    




    const NAMESPACE_V1 = 'rankout-connector/v1';

    





    public function get_protected_resource_metadata( \WP_REST_Request $request ) {
        $error = $this->enforce_transport_security();
        if ( is_wp_error( $error ) ) {
            return $error;
        }

        $scopes = class_exists( __NAMESPACE__ . '\\Scope_Map' )
            ? Scope_Map::get_public_scopes()
            : array();

        $metadata = array(
            'resource'                 => rest_url( self::NAMESPACE_V1 . '/mcp' ),
            'authorization_servers'    => array( home_url() ),
            'bearer_methods_supported' => array( 'header' ),
            'scopes_supported'         => $scopes,
        );

        $response = new \WP_REST_Response( $metadata, 200 );
        $this->add_security_headers( $response );
        $this->add_cors_headers( $response );

        return $response;
    }

    





    public function get_authorization_server_metadata( \WP_REST_Request $request ) {
        $error = $this->enforce_transport_security();
        if ( is_wp_error( $error ) ) {
            return $error;
        }

        $rest_base = rest_url( self::NAMESPACE_V1 );

        $scopes = class_exists( __NAMESPACE__ . '\\Scope_Map' )
            ? Scope_Map::get_public_scopes()
            : array();

        $metadata = array(
            'issuer'                                => home_url(),
            'authorization_endpoint'                => home_url( '?rankout_connector_oauth=authorize' ),
            'token_endpoint'                        => $rest_base . '/oauth/token',
            'registration_endpoint'                 => $rest_base . '/oauth/register',
            'revocation_endpoint'                   => $rest_base . '/oauth/revoke',
            'response_types_supported'                     => array( 'code' ),
            'response_modes_supported'                     => array( 'query' ),
            'grant_types_supported'                        => array( 'authorization_code', 'refresh_token' ),
            'code_challenge_methods_supported'             => array( 'S256' ),
            'token_endpoint_auth_methods_supported'        => array( 'none' ),
            'revocation_endpoint_auth_methods_supported'   => array( 'none' ),
            'registration_endpoint_auth_methods_supported' => array( 'none' ),
            'scopes_supported'                             => $scopes,
        );

        $response = new \WP_REST_Response( $metadata, 200 );
        $this->add_security_headers( $response );
        $this->add_cors_headers( $response );

        return $response;
    }

    




    private function enforce_transport_security() {
        
        
        
        
        if ( null === Token_Endpoint::enforce_transport_security() ) {
            return null;
        }
        return new \WP_Error(
            'oauth_requires_https',
            __( 'OAuth metadata endpoints require HTTPS.', 'rankout-connector' ),
            array( 'status' => 403 )
        );
    }

    





    private function add_security_headers( \WP_REST_Response $response ) {
        $response->header( 'Referrer-Policy', 'no-referrer' );
        $response->header( 'X-Content-Type-Options', 'nosniff' );
        $response->header( 'Cache-Control', 'no-store' );
        $response->header( 'Pragma', 'no-cache' );
        $response->header( 'Strict-Transport-Security', 'max-age=31536000' );
    }

    





    private function add_cors_headers( \WP_REST_Response $response ) {
        $response->header( 'Access-Control-Allow-Origin', '*' );
        $response->header( 'Access-Control-Allow-Methods', 'GET, OPTIONS' );
        $response->header( 'Access-Control-Allow-Headers', 'Authorization, Content-Type' );
    }
}
