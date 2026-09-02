<?php
namespace RankOut_Connector\OAuth;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}









class Token_Endpoint {

    




    const NAMESPACE_V1 = 'rankout-connector/v1';

    





    public function handle( \WP_REST_Request $request ) {

        
        
        $transport_error = self::enforce_transport_security();
        if ( null !== $transport_error ) {
            return $transport_error;
        }

        
        $rate_error = self::enforce_rate_limit();
        if ( null !== $rate_error ) {
            return $rate_error;
        }

        
        
        
        

        $grant_type = sanitize_text_field( $request->get_param( 'grant_type' ) );

        switch ( $grant_type ) {
            case 'authorization_code':
                return $this->handle_authorization_code( $request );

            case 'refresh_token':
                return $this->handle_refresh_token( $request );

            default:
                return new \WP_REST_Response(
                    array(
                        'error'             => 'unsupported_grant_type',
                        'error_description' => __( 'Only authorization_code and refresh_token grant types are supported.', 'rankout-connector' ),
                    ),
                    400
                );
        }
    }

    





    private function handle_authorization_code( \WP_REST_Request $request ) {
        global $wpdb;

        
        
        
        
        
        
        $code          = is_string( $request->get_param( 'code' ) ) ? $request->get_param( 'code' ) : '';
        $code_verifier = is_string( $request->get_param( 'code_verifier' ) ) ? $request->get_param( 'code_verifier' ) : '';
        $client_id     = sanitize_text_field( $request->get_param( 'client_id' ) );
        $redirect_uri  = is_string( $request->get_param( 'redirect_uri' ) ) ? esc_url_raw( $request->get_param( 'redirect_uri' ) ) : '';
        
        
        
        $resource      = is_string( $request->get_param( 'resource' ) ) ? esc_url_raw( $request->get_param( 'resource' ) ) : '';

        
        if ( '' === $code || '' === $code_verifier || '' === $client_id || '' === $redirect_uri ) {
            return $this->token_error( 'invalid_request', __( 'Missing required parameter.', 'rankout-connector' ) );
        }

        
        if ( ! preg_match( '/^[A-Za-z0-9\-._~]{43,128}$/', $code_verifier ) ) {
            return $this->token_error( 'invalid_request', __( 'Invalid code_verifier format.', 'rankout-connector' ) );
        }

        
        $code_hash   = hash( 'sha256', $code );
        $codes_table = $wpdb->prefix . 'rankout_connector_oauth_codes';

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table prefixed by $wpdb->prefix; auth code lookup must be fresh.
        $code_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$codes_table} WHERE code_hash = %s LIMIT 1",
                $code_hash
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        if ( ! $code_row ) {
            return $this->token_error( 'invalid_grant', __( 'Authorization code not found.', 'rankout-connector' ) );
        }

        
        if ( null !== $code_row->used_at ) {
            $this->revoke_tokens_for_code( $code_row );
            return $this->token_error( 'invalid_grant', __( 'Authorization code has already been used. All associated tokens have been revoked.', 'rankout-connector' ) );
        }

        
        $now_utc = gmdate( 'Y-m-d H:i:s' );
        if ( $code_row->expires_at < $now_utc ) {
            return $this->token_error( 'invalid_grant', __( 'Authorization code has expired.', 'rankout-connector' ) );
        }

        
        if ( $code_row->client_id !== $client_id ) {
            return $this->token_error( 'invalid_grant', __( 'Client ID mismatch.', 'rankout-connector' ) );
        }

        
        
        $registry = new Client_Registry();
        $client   = $registry->get_client( $client_id );
        if ( null === $client ) {
            return $this->token_error( 'invalid_client', __( 'Client not found or inactive.', 'rankout-connector' ), 401 );
        }

        if ( $code_row->redirect_uri !== $redirect_uri ) {
            return $this->token_error( 'invalid_grant', __( 'Redirect URI mismatch.', 'rankout-connector' ) );
        }

        
        
        
        
        
        
        if ( '' === $resource ) {
            $resource = rest_url( self::NAMESPACE_V1 . '/mcp' );
        }
        if ( ! empty( $code_row->resource ) && ! self::resource_matches( $resource, $code_row->resource ) ) {
            return $this->token_error( 'invalid_target', __( 'Resource parameter does not match the authorized resource.', 'rankout-connector' ) );
        }

        
        $computed_challenge = self::base64url_encode( hash( 'sha256', $code_verifier, true ) );
        if ( ! hash_equals( $code_row->code_challenge, $computed_challenge ) ) {
            return $this->token_error( 'invalid_grant', __( 'PKCE verification failed.', 'rankout-connector' ) );
        }

        
        
        
        
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table prefixed by $wpdb->prefix; atomic redemption must hit DB.
        $affected = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$codes_table} SET used_at = %s WHERE code_hash = %s AND used_at IS NULL AND expires_at > %s",
                $now_utc,
                $code_hash,
                $now_utc
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        if ( 1 !== (int) $affected ) {
            
            
            $this->revoke_tokens_for_code( $code_row );
            return $this->token_error( 'invalid_grant', __( 'Authorization code already redeemed or expired. Tokens revoked.', 'rankout-connector' ) );
        }

        
        $token_manager = new OAuth_Token_Manager();
        $tokens = $token_manager->mint_access_token(
            $code_row->client_id,
            (int) $code_row->wp_user_id,
            $code_row->resource,
            $code_row->scope
        );

        if ( null === $tokens ) {
            return $this->token_error( 'server_error', __( 'Failed to issue access token.', 'rankout-connector' ), 500 );
        }

        
        
        $minted_token_id = isset( $tokens['token_id'] ) ? (int) $tokens['token_id'] : 0;
        if ( $minted_token_id > 0 ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table write.
            $wpdb->update(
                $codes_table,
                array( 'minted_token_id' => $minted_token_id ),
                array( 'code_hash' => $code_hash ),
                array( '%d' ),
                array( '%s' )
            );
        }

        $response = new \WP_REST_Response(
            array(
                'access_token'  => $tokens['access_token'],
                'token_type'    => 'Bearer',
                'expires_in'    => $tokens['expires_in'],
                'refresh_token' => $tokens['refresh_token'],
                'scope'         => $tokens['scope'],
                'wp_user_id'    => (int) $code_row->wp_user_id,
            ),
            200
        );
        $this->add_token_headers( $response );
        return $response;
    }

    





    private function handle_refresh_token( \WP_REST_Request $request ) {

        
        
        $refresh_token = is_string( $request->get_param( 'refresh_token' ) ) ? $request->get_param( 'refresh_token' ) : '';
        $client_id     = sanitize_text_field( $request->get_param( 'client_id' ) );
        $resource      = is_string( $request->get_param( 'resource' ) ) ? $request->get_param( 'resource' ) : '';

        if ( '' === $refresh_token || '' === $client_id ) {
            return $this->token_error( 'invalid_request', __( 'Missing required parameter.', 'rankout-connector' ) );
        }

        
        
        $client = ( new Client_Registry() )->get_client( $client_id );
        if ( ! $client ) {
            return $this->token_error( 'invalid_client', __( 'Client not found or inactive.', 'rankout-connector' ), 401 );
        }

        if ( empty( $resource ) ) {
            $resource = rest_url( self::NAMESPACE_V1 . '/mcp' );
        }

        
        
        
        
        
        
        $expected_resource = rest_url( self::NAMESPACE_V1 . '/mcp' );
        if ( ! self::resource_matches( $resource, $expected_resource ) ) {
            return $this->token_error( 'invalid_grant', __( 'Resource mismatch.', 'rankout-connector' ) );
        }

        $token_manager = new OAuth_Token_Manager();
        $result = $token_manager->refresh( $refresh_token, $client_id, $resource );

        if ( false === $result ) {
            return $this->token_error( 'invalid_grant', __( 'Refresh token is invalid, expired, or revoked.', 'rankout-connector' ) );
        }

        $response = new \WP_REST_Response(
            array(
                'access_token'  => $result['access_token'],
                'token_type'    => 'Bearer',
                'expires_in'    => $result['expires_in'],
                'refresh_token' => $result['refresh_token'],
                'scope'         => $result['scope'],
            ),
            200
        );
        $this->add_token_headers( $response );
        return $response;
    }

    













    private function revoke_tokens_for_code( $code_row ) {
        global $wpdb;
        $tokens_table = $wpdb->prefix . 'rankout_connector_oauth_access_tokens';
        $codes_table  = $wpdb->prefix . 'rankout_connector_oauth_codes';

        if ( empty( $code_row->minted_token_id ) && ! empty( $code_row->code_hash ) ) {
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table prefixed by $wpdb->prefix.
            $fresh_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT minted_token_id FROM {$codes_table} WHERE code_hash = %s LIMIT 1",
                    $code_row->code_hash
                )
            );
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
            if ( ! empty( $fresh_id ) ) {
                $code_row->minted_token_id = (int) $fresh_id;
            }
        }

        if ( ! empty( $code_row->minted_token_id ) ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table write.
            $wpdb->update(
                $tokens_table,
                array( 'is_active' => 0 ),
                array( 'id' => (int) $code_row->minted_token_id ),
                array( '%d' ),
                array( '%d' )
            );
        }
    }

    





    private function add_token_headers( \WP_REST_Response $response ) {
        $response->header( 'Content-Type', 'application/json' );
        $response->header( 'Cache-Control', 'no-store' );
        $response->header( 'Pragma', 'no-cache' );
        $response->header( 'Referrer-Policy', 'no-referrer' );
        $response->header( 'X-Content-Type-Options', 'nosniff' );
    }

    










    private function token_error( $error, $description, $status = 400 ) {
        $response = new \WP_REST_Response(
            array(
                'error'             => $error,
                'error_description' => $description,
            ),
            $status
        );
        
        
        
        if ( 401 === (int) $status ) {
            $response->header(
                'WWW-Authenticate',
                sprintf( 'Bearer realm="oauth", error="%s"', $error )
            );
        }
        return $response;
    }

    











    public static function resource_matches( string $candidate, string $expected ): bool {
        $a = self::canonicalize_resource_uri( $candidate );
        $b = self::canonicalize_resource_uri( $expected );
        return '' !== $a && $a === $b;
    }

    





    private static function canonicalize_resource_uri( string $uri ): string {
        $uri = trim( $uri );
        if ( '' === $uri ) {
            return '';
        }

        $parts = wp_parse_url( $uri );
        if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) ) {
            return '';
        }

        $scheme = strtolower( $parts['scheme'] );
        $host   = strtolower( $parts['host'] );

        $port = '';
        if ( isset( $parts['port'] ) ) {
            $default = ( 'https' === $scheme ) ? 443 : ( ( 'http' === $scheme ) ? 80 : 0 );
            if ( (int) $parts['port'] !== $default ) {
                $port = ':' . (int) $parts['port'];
            }
        }

        $path  = isset( $parts['path'] ) ? $parts['path'] : '';
        $query = isset( $parts['query'] ) ? $parts['query'] : '';

        
        if ( '' !== $query ) {
            parse_str( $query, $q );
            if ( isset( $q['rest_route'] ) && is_string( $q['rest_route'] ) && '' !== $q['rest_route'] ) {
                $path  = '/wp-json' . $q['rest_route'];
                $query = '';
            }
        }

        
        if ( strlen( $path ) > 1 && '/' === substr( $path, -1 ) ) {
            $path = rtrim( $path, '/' );
        }

        $canonical = $scheme . '://' . $host . $port . $path;
        if ( '' !== $query ) {
            $canonical .= '?' . $query;
        }

        return $canonical;
    }

    





    private static function base64url_encode( $data ) {
        return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
    }

    







    public static function enforce_transport_security() {
        if ( is_ssl() ) {
            return null;
        }

        if ( self::is_local_dev_request() ) {
            return null;
        }

        return new \WP_REST_Response(
            array(
                'error'             => 'insecure_transport',
                'error_description' => __( 'HTTPS is required.', 'rankout-connector' ),
            ),
            403
        );
    }

    












    private static function is_local_dev_request(): bool {
        if ( defined( 'RANKOUT_CONNECTOR_OAUTH_ALLOW_HTTP' ) && RANKOUT_CONNECTOR_OAUTH_ALLOW_HTTP ) {
            return true;
        }

        if ( ! isset( $_SERVER['REMOTE_ADDR'] ) ) {
            return false;
        }
        $remote = trim( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ), '[]' );
        return in_array( $remote, array( '127.0.0.1', '::1' ), true );
    }

    








    public static function enforce_rate_limit() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] )
            ? trim( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ), '[]' )
            : 'unknown';

        $per_ip_key   = 'rankout_connector_token_rl_' . md5( $ip );
        $global_key   = 'rankout_connector_token_rl_global';
        $per_ip_limit = 120;
        $global_limit = 1200;

        if ( \wp_using_ext_object_cache() ) {
            \wp_cache_add( $per_ip_key, 0, 'rankout_connector', HOUR_IN_SECONDS );
            $ip_count = \wp_cache_incr( $per_ip_key, 1, 'rankout_connector' );

            \wp_cache_add( $global_key, 0, 'rankout_connector', HOUR_IN_SECONDS );
            $global_count = \wp_cache_incr( $global_key, 1, 'rankout_connector' );
        } else {
            
            
            
            
            
            
            
            
            
            
            $ip_count     = self::rl_transient_increment( $per_ip_key );
            $global_count = self::rl_transient_increment( $global_key );
        }

        if ( $ip_count > $per_ip_limit || $global_count > $global_limit ) {
            return new \WP_REST_Response( null, 429 );
        }

        return null;
    }

    









    public static function rl_transient_increment( string $key ): int {
        $now    = time();
        $packed = get_transient( $key );
        if ( ! is_string( $packed ) || false === strpos( $packed, ':' ) ) {
            $count    = 1;
            $deadline = $now + HOUR_IN_SECONDS;
        } else {
            list( $c, $d ) = explode( ':', $packed, 2 );
            $deadline      = (int) $d;
            if ( $deadline <= $now ) {
                $count    = 1;
                $deadline = $now + HOUR_IN_SECONDS;
            } else {
                $count = (int) $c + 1;
            }
        }
        $ttl = max( 1, $deadline - $now );
        set_transient( $key, $count . ':' . $deadline, $ttl );
        return $count;
    }
}
