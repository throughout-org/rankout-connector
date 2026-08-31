<?php
namespace RankOut_Connector\OAuth;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}









class Client_Registry {

    




    const MAX_CLIENT_NAME_LENGTH = 120;

    




    const DEFAULT_MAX_CLIENTS = 5000;

    




    const RATE_LIMIT_PER_HOUR = 10;

    





    const GLOBAL_RATE_LIMIT_PER_HOUR = 100;

    








    public function handle_register( \WP_REST_Request $request ) {
        
        
        $transport_error = Token_Endpoint::enforce_transport_security();
        if ( null !== $transport_error ) {
            return $transport_error;
        }

        
        $dcr_enabled = get_option( 'rankout_connector_oauth_dcr_enabled', true );
        if ( ! $dcr_enabled ) {
            return new \WP_REST_Response( null, 404 );
        }

        
        $rate_result = $this->check_rate_limit();
        if ( $rate_result instanceof \WP_REST_Response ) {
            return $rate_result;
        }

        
        $content_type = $request->get_content_type();
        $ct_value     = is_array( $content_type ) && isset( $content_type['value'] ) ? strtolower( $content_type['value'] ) : '';
        if ( 'application/json' !== $ct_value ) {
            return self::dcr_error( 'invalid_client_metadata', __( 'Request Content-Type must be application/json.', 'rankout-connector' ), 400 );
        }

        
        $body = $request->get_json_params();
        if ( empty( $body ) || ! is_array( $body ) ) {
            return self::dcr_error( 'invalid_client_metadata', __( 'Request body must be a JSON object.', 'rankout-connector' ), 400 );
        }

        
        $validated = $this->validate_request_body( $body );
        if ( $validated instanceof \WP_REST_Response ) {
            return $validated;
        }

        
        $cap_error = $this->check_client_cap();
        if ( $cap_error instanceof \WP_REST_Response ) {
            return $cap_error;
        }

        
        return $this->persist_client( $validated );
    }

    








    private function validate_request_body( array $body ) {
        
        if ( ! isset( $body['redirect_uris'] ) || ! is_array( $body['redirect_uris'] ) || empty( $body['redirect_uris'] ) ) {
            return self::dcr_error( 'invalid_redirect_uri', __( 'redirect_uris is required and must be a non-empty array of strings.', 'rankout-connector' ), 400 );
        }

        
        if ( count( $body['redirect_uris'] ) > 10 ) {
            return self::dcr_error( 'invalid_redirect_uri', __( 'Too many redirect_uris (maximum 10).', 'rankout-connector' ), 400 );
        }

        $redirect_uris = array();
        foreach ( $body['redirect_uris'] as $uri ) {
            if ( ! is_string( $uri ) ) {
                return self::dcr_error( 'invalid_redirect_uri', __( 'Each redirect_uri must be a string.', 'rankout-connector' ), 400 );
            }

            $validation_error = $this->validate_redirect_uri( $uri );
            if ( $validation_error instanceof \WP_REST_Response ) {
                return $validation_error;
            }

            $redirect_uris[] = sanitize_url( $uri );
        }

        
        $client_name = '';
        if ( isset( $body['client_name'] ) && is_string( $body['client_name'] ) ) {
            
            $client_name = str_replace( array( "\r", "\n" ), '', $body['client_name'] );
            $client_name = sanitize_text_field( $client_name );
            $client_name = mb_substr( $client_name, 0, self::MAX_CLIENT_NAME_LENGTH );
        }

        $grant_types    = array( 'authorization_code' );
        $response_types = array( 'code' );

        $supported_grant_types    = array( 'authorization_code', 'refresh_token' );
        $supported_response_types = array( 'code' );

        
        
        if ( isset( $body['grant_types'] ) && is_array( $body['grant_types'] ) && ! empty( $body['grant_types'] ) ) {
            $requested = array_values( array_unique( array_map( 'sanitize_text_field', $body['grant_types'] ) ) );
            foreach ( $requested as $gt ) {
                if ( ! in_array( $gt, $supported_grant_types, true ) ) {
                    return self::dcr_error(
                        'invalid_client_metadata',
                        /* translators: %s: unsupported grant type */
                        sprintf( __( 'Unsupported grant_type: %s', 'rankout-connector' ), esc_html( $gt ) ),
                        400
                    );
                }
            }
            $grant_types = $requested;
        }

        if ( isset( $body['response_types'] ) && is_array( $body['response_types'] ) && ! empty( $body['response_types'] ) ) {
            $requested = array_values( array_unique( array_map( 'sanitize_text_field', $body['response_types'] ) ) );
            foreach ( $requested as $rt ) {
                if ( ! in_array( $rt, $supported_response_types, true ) ) {
                    return self::dcr_error(
                        'invalid_client_metadata',
                        /* translators: %s: unsupported response type */
                        sprintf( __( 'Unsupported response_type: %s', 'rankout-connector' ), esc_html( $rt ) ),
                        400
                    );
                }
            }
            $response_types = $requested;
        }

        
        if ( isset( $body['token_endpoint_auth_method'] ) && 'none' !== $body['token_endpoint_auth_method'] ) {
            return self::dcr_error( 'invalid_client_metadata', __( 'Only token_endpoint_auth_method=none is supported.', 'rankout-connector' ), 400 );
        }

        $software_id      = isset( $body['software_id'] ) && is_string( $body['software_id'] )
            ? sanitize_text_field( $body['software_id'] )
            : null;
        $software_version = isset( $body['software_version'] ) && is_string( $body['software_version'] )
            ? sanitize_text_field( $body['software_version'] )
            : null;

        return array(
            'redirect_uris'    => $redirect_uris,
            'client_name'      => $client_name,
            'grant_types'      => $grant_types,
            'response_types'   => $response_types,
            'software_id'      => $software_id,
            'software_version' => $software_version,
        );
    }

    





    private function persist_client( array $fields ) {
        $redirect_uris    = $fields['redirect_uris'];
        $client_name      = $fields['client_name'];
        $grant_types      = $fields['grant_types'];
        $response_types   = $fields['response_types'];
        $software_id      = $fields['software_id'];
        $software_version = $fields['software_version'];

        global $wpdb;
        $table = $wpdb->prefix . 'rankout_connector_oauth_clients';

        
        $this->cleanup_stale_duplicates( $client_name, wp_json_encode( $redirect_uris ) );

        
        $client_id = bin2hex( random_bytes( 16 ) );

        $client_ip = isset( $_SERVER['REMOTE_ADDR'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
            : '';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned table write.
        $inserted = $wpdb->insert(
            $table,
            array(
                'client_id'        => $client_id,
                'client_name'      => $client_name,
                'redirect_uris'    => wp_json_encode( $redirect_uris ),
                'grant_types'      => wp_json_encode( $grant_types ),
                'response_types'   => wp_json_encode( $response_types ),
                'scope'            => '',
                'software_id'      => $software_id,
                'software_version' => $software_version,
                'created_at'       => current_time( 'mysql', true ),
                'created_by_ip'    => $client_ip,
                'is_active'        => 1,
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
        );

        if ( false === $inserted ) {
            return self::dcr_error( 'server_error', __( 'Client registration failed. Please try again.', 'rankout-connector' ), 500 );
        }

        $response_data = array(
            'client_id'                  => $client_id,
            'client_id_issued_at'        => time(),
            'client_name'                => $client_name,
            'redirect_uris'              => $redirect_uris,
            'grant_types'                => $grant_types,
            'response_types'             => $response_types,
            'token_endpoint_auth_method' => 'none',
        );
        if ( ! empty( $software_id ) ) {
            $response_data['software_id'] = $software_id;
        }
        if ( ! empty( $software_version ) ) {
            $response_data['software_version'] = $software_version;
        }

        $response = new \WP_REST_Response( $response_data, 201 );
        $response->header( 'Referrer-Policy', 'no-referrer' );
        $response->header( 'X-Content-Type-Options', 'nosniff' );
        $response->header( 'Cache-Control', 'no-store' );

        return $response;
    }

    





    private function validate_redirect_uri( $uri ) {
        
        
        if ( strlen( $uri ) > 2048 ) {
            return self::dcr_error( 'invalid_redirect_uri', __( 'Redirect URI is too long (maximum 2048 bytes).', 'rankout-connector' ), 400 );
        }

        
        if ( ! filter_var( $uri, FILTER_VALIDATE_URL ) ) {
            return self::dcr_error(
                'invalid_redirect_uri',
                /* translators: %s: the offending URI */
                sprintf( __( 'Invalid redirect URI: %s', 'rankout-connector' ), esc_url( $uri ) ),
                400
            );
        }

        $parsed = wp_parse_url( $uri );

        
        $scheme = isset( $parsed['scheme'] ) ? strtolower( $parsed['scheme'] ) : '';
        $forbidden_schemes = array( 'javascript', 'data', 'file' );
        if ( in_array( $scheme, $forbidden_schemes, true ) ) {
            return self::dcr_error(
                'invalid_redirect_uri',
                /* translators: %s: the offending scheme */
                sprintf( __( 'Forbidden scheme in redirect URI: %s', 'rankout-connector' ), esc_html( $scheme ) ),
                400
            );
        }

        
        if ( isset( $parsed['fragment'] ) ) {
            return self::dcr_error( 'invalid_redirect_uri', __( 'Redirect URIs must not contain a fragment (#).', 'rankout-connector' ), 400 );
        }

        
        if ( isset( $parsed['user'] ) || isset( $parsed['pass'] ) ) {
            return self::dcr_error( 'invalid_redirect_uri', __( 'Redirect URIs must not contain userinfo.', 'rankout-connector' ), 400 );
        }

        
        if ( false !== strpos( $uri, '*' ) ) {
            return self::dcr_error( 'invalid_redirect_uri', __( 'Redirect URIs must not contain wildcards.', 'rankout-connector' ), 400 );
        }

        
        if ( 'https' !== $scheme ) {
            $host       = isset( $parsed['host'] ) ? strtolower( $parsed['host'] ) : '';
            $local_hosts = array( 'localhost', '127.0.0.1', '[::1]' );
            if ( ! in_array( $host, $local_hosts, true ) ) {
                return self::dcr_error( 'invalid_redirect_uri', __( 'Redirect URIs must use HTTPS except for localhost.', 'rankout-connector' ), 400 );
            }
        }

        return true;
    }

    







    public static function dcr_error( $error, $description, $status = 400 ) {
        $response = new \WP_REST_Response(
            array(
                'error'             => $error,
                'error_description' => $description,
            ),
            $status
        );
        $response->header( 'Cache-Control', 'no-store' );
        $response->header( 'Pragma', 'no-cache' );
        $response->header( 'Content-Type', 'application/json' );
        return $response;
    }

    










    private function check_rate_limit() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
            : 'unknown';

        
        
        
        $ip = trim( $ip, '[]' );

        $per_ip_key  = 'rankout_connector_dcr_rl_' . md5( $ip );
        $global_key  = 'rankout_connector_dcr_rl_global';
        $global_cap  = self::GLOBAL_RATE_LIMIT_PER_HOUR;

        if ( \wp_using_ext_object_cache() ) {
            \wp_cache_add( $per_ip_key, 0, 'rankout_connector', HOUR_IN_SECONDS );
            $new_count = \wp_cache_incr( $per_ip_key, 1, 'rankout_connector' );

            \wp_cache_add( $global_key, 0, 'rankout_connector', HOUR_IN_SECONDS );
            $new_global = \wp_cache_incr( $global_key, 1, 'rankout_connector' );
        } else {
            
            $new_count  = Token_Endpoint::rl_transient_increment( $per_ip_key );
            $new_global = Token_Endpoint::rl_transient_increment( $global_key );
        }

        if ( $new_count > self::RATE_LIMIT_PER_HOUR || $new_global > $global_cap ) {
            return new \WP_REST_Response( null, 429 );
        }

        return null;
    }

    




    private function check_client_cap() {
        global $wpdb;

        $table     = $wpdb->prefix . 'rankout_connector_oauth_clients';
        $max       = (int) get_option( 'rankout_connector_oauth_max_clients', self::DEFAULT_MAX_CLIENTS );
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table prefixed by $wpdb->prefix; live count must be fresh for cap check.
        $count     = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE is_active = %d",
                1
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        if ( $count >= $max ) {
            return self::dcr_error( 'server_error', __( 'Maximum number of registered clients has been reached.', 'rankout-connector' ), 503 );
        }

        return null;
    }

    










    private function cleanup_stale_duplicates( $client_name, $redirect_uris_json ) {
        global $wpdb;

        $clients_table  = $wpdb->prefix . 'rankout_connector_oauth_clients';
        $tokens_table   = $wpdb->prefix . 'rankout_connector_oauth_access_tokens';
        $codes_table    = $wpdb->prefix . 'rankout_connector_oauth_codes';
        $consents_table = $wpdb->prefix . 'rankout_connector_oauth_consents';

        $now    = current_time( 'mysql', true );
        $cutoff = gmdate( 'Y-m-d H:i:s', time() - 60 );

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned tables prefixed by $wpdb->prefix.
        $stale_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT c.client_id FROM {$clients_table} c
                 WHERE c.client_name = %s
                   AND c.redirect_uris = %s
                   AND c.created_at < %s
                   AND NOT EXISTS (
                       SELECT 1 FROM {$tokens_table} t
                       WHERE t.client_id = c.client_id
                         AND t.is_active = 1
                         AND t.expires_at > %s
                   )",
                $client_name,
                $redirect_uris_json,
                $cutoff,
                $now
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        if ( empty( $stale_ids ) ) {
            return;
        }

        foreach ( $stale_ids as $stale_id ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->delete( $tokens_table, array( 'client_id' => $stale_id ), array( '%s' ) );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->delete( $codes_table, array( 'client_id' => $stale_id ), array( '%s' ) );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->delete( $consents_table, array( 'client_id' => $stale_id ), array( '%s' ) );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->delete( $clients_table, array( 'client_id' => $stale_id ), array( '%s' ) );
        }
    }

    





    public function get_client( $client_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'rankout_connector_oauth_clients';

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table prefixed by $wpdb->prefix; client lookup must be fresh.
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT client_id, client_name, redirect_uris, grant_types, response_types, scope, software_id, software_version, is_active FROM {$table} WHERE client_id = %s AND is_active = %d LIMIT 1",
                $client_id,
                1
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    }
}

