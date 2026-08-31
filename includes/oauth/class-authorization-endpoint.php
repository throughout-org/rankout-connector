<?php
namespace RankOut_Connector\OAuth;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}









class Authorization_Endpoint {

    




    const NAMESPACE_V1 = 'rankout-connector/v1';

    




    const CODE_LIFETIME = 60;

    





    public function handle_get( \WP_REST_Request $request ) {

        $tls_error = $this->enforce_transport_security();
        if ( $tls_error ) {
            return $tls_error;
        }

        
        
        
        
        
        $rate_error = Token_Endpoint::enforce_rate_limit();
        if ( null !== $rate_error ) {
            return $rate_error;
        }

        
        if ( ! is_user_logged_in() ) {
            $current_url = $this->build_current_url( $request );
            $login_url   = wp_login_url( $current_url );

            $response = new \WP_REST_Response( null, 302 );
            $response->header( 'Location', $login_url );
            $this->add_security_headers( $response );
            return $response;
        }

        
        
        
        $params         = $this->extract_params( $request );
        $client_or_error = $this->validate_authorize_params( $params );

        if ( is_wp_error( $client_or_error ) ) {
            return $this->error_response( $client_or_error, $params );
        }

        $client = $client_or_error;

        
        
        
        $min_cap = apply_filters( 'rankout_connector_oauth_min_capability', 'publish_posts' );
        if ( ! current_user_can( $min_cap ) ) {
            return $this->redirect_with_error(
                $params,
                'access_denied',
                __( 'Your account does not have sufficient permissions to authorize MCP access.', 'rankout-connector' )
            );
        }

        
        $requested_scope = ! empty( $params['scope'] )
            ? sanitize_text_field( $params['scope'] )
            : Scope_Map::get_default_scope();

        $scope_list = array_values( array_filter( array_map( 'trim', explode( ' ', $requested_scope ) ) ) );
        $scope_list = Scope_Map::apply_legacy_scope_upgrades( $scope_list );

        $valid_scopes = Scope_Map::get_all_scopes();
        foreach ( $scope_list as $s ) {
            if ( 'mcp' !== $s && ! in_array( $s, $valid_scopes, true ) ) {
                return $this->error_response(
                    new \WP_Error( 'invalid_scope', __( 'Unknown scope requested.', 'rankout-connector' ) ),
                    $params
                );
            }
        }

        
        $user    = wp_get_current_user();
        $consent = $this->get_existing_consent( $user->ID, $params['client_id'] );

        if ( $consent ) {
            $consented_scopes = array_filter( array_map( 'trim', explode( ' ', $consent->scope ) ) );
            $is_subset        = empty( array_diff( $scope_list, $consented_scopes ) )
                                || in_array( 'mcp', $consented_scopes, true );

            if ( $is_subset ) {
                
                
                $grant_scope = implode( ' ', $scope_list );
                $code        = $this->mint_authorization_code( $params, $user->ID, $grant_scope );
                if ( null === $code ) {
                    return $this->redirect_with_error( $params, 'server_error', __( 'Failed to issue authorization code.', 'rankout-connector' ) );
                }
                return $this->redirect_with_code( $params['redirect_uri'], $code, $params['state'] );
            }
        }

        
        
        
        
        
        
        
        
        
        $params['scope_sig'] = self::sign_scope( $params['client_id'], $params['scope'] );
        $script_nonce        = wp_create_nonce( 'rankout_connector_consent_script' );
        $html = Consent_Screen::render( $client, $user, $requested_scope, $params, $script_nonce );

        $response = new \WP_REST_Response( $html, 200 );
        $response->header( 'Content-Type', 'text/html; charset=utf-8' );
        $this->add_security_headers( $response, $script_nonce, $params['redirect_uri'] );
        return $response;
    }

    





    public function handle_post( \WP_REST_Request $request ) {

        $tls_error = $this->enforce_transport_security();
        if ( $tls_error ) {
            return $tls_error;
        }

        $rate_error = Token_Endpoint::enforce_rate_limit();
        if ( null !== $rate_error ) {
            return $rate_error;
        }

        
        if ( ! is_user_logged_in() ) {
            return new \WP_Error(
                'access_denied',
                __( 'You must be logged in to authorize this request.', 'rankout-connector' ),
                array( 'status' => 401 )
            );
        }

        
        $min_cap = apply_filters( 'rankout_connector_oauth_min_capability', 'publish_posts' );
        if ( ! current_user_can( $min_cap ) ) {
            return new \WP_Error(
                'access_denied',
                __( 'Your account does not have sufficient permissions to authorize MCP access.', 'rankout-connector' ),
                array( 'status' => 403 )
            );
        }

        $params = $this->extract_params( $request );

        
        $nonce = sanitize_text_field( $request->get_param( '_wpnonce' ) );
        if ( ! wp_verify_nonce( $nonce, 'rankout_connector_oauth_consent_' . $params['client_id'] ) ) {
            return new \WP_Error(
                'invalid_request',
                __( 'Security check failed. Please try again.', 'rankout-connector' ),
                array( 'status' => 403 )
            );
        }

        
        
        
        
        
        
        
        $scope_sig = sanitize_text_field( $request->get_param( 'scope_sig' ) );
        $expected  = self::sign_scope( $params['client_id'], $params['scope'] );
        if ( '' === $scope_sig || ! hash_equals( $expected, $scope_sig ) ) {
            return new \WP_Error(
                'invalid_request',
                __( 'Scope integrity check failed. Please restart authorization.', 'rankout-connector' ),
                array( 'status' => 403 )
            );
        }

        
        $validate_result = $this->validate_authorize_params( $params );
        if ( is_wp_error( $validate_result ) ) {
            return $this->error_response( $validate_result, $params );
        }

        $user   = wp_get_current_user();
        $action = sanitize_text_field( $request->get_param( 'consent_action' ) );

        
        if ( 'approve' !== $action ) {
            return $this->handle_deny_action( $params );
        }

        return $this->handle_approve_action( $request, $params, $user );
    }

    







    private function redirect_with_error( array $params, string $error, string $error_description ) {
        $args = array(
            'error'             => $error,
            'error_description' => $error_description,
            'iss'               => home_url(),
        );
        if ( ! empty( $params['state'] ) ) {
            $args['state'] = $params['state'];
        }
        $redirect = add_query_arg( $args, $params['redirect_uri'] );

        $response = new \WP_REST_Response( null, 302 );
        $response->header( 'Location', $redirect );
        $this->add_security_headers( $response );
        return $response;
    }

    










    private function handle_deny_action( array $params ) {
        return $this->redirect_with_error(
            $params,
            'access_denied',
            __( 'The user denied the authorization request.', 'rankout-connector' )
        );
    }

    







    private function handle_approve_action( \WP_REST_Request $request, array $params, \WP_User $user ) {
        $raw_scopes       = $request->get_param( 'scopes' );
        $all_scopes       = Scope_Map::get_all_scopes();
        $submitted_scopes = array();

        if ( is_array( $raw_scopes ) ) {
            foreach ( $raw_scopes as $s ) {
                $submitted_scopes[] = sanitize_text_field( $s );
            }
        }

        
        
        
        if ( ! empty( $params['scope'] ) ) {
            $requested_scope_list = array_filter( array_map( 'trim', explode( ' ', $params['scope'] ) ) );
        } else {
            $requested_scope_list = array_filter( array_map( 'trim', explode( ' ', Scope_Map::get_default_scope() ) ) );
        }
        $client_requested_mcp = in_array( 'mcp', $requested_scope_list, true );

        
        
        
        
        if ( $client_requested_mcp && in_array( 'mcp', $submitted_scopes, true ) ) {
            $this->store_consent( $user->ID, $params['client_id'], 'mcp' );
            $code = $this->mint_authorization_code( $params, $user->ID, 'mcp' );
            if ( null === $code ) {
                return $this->redirect_with_error( $params, 'server_error', __( 'Failed to issue authorization code.', 'rankout-connector' ) );
            }
            return $this->redirect_with_code( $params['redirect_uri'], $code, $params['state'] );
        }

        
        $valid_submitted = array_values( array_intersect( $submitted_scopes, $all_scopes ) );

        
        

        if ( ! empty( $requested_scope_list ) && ! $client_requested_mcp ) {
            $valid_submitted = array_values( array_intersect( $valid_submitted, $requested_scope_list ) );
        }

        
        if ( $client_requested_mcp && in_array( 'mcp', $submitted_scopes, true ) ) {
            $scope_string = 'mcp';
        } elseif ( ! empty( $valid_submitted ) && empty( array_diff( $all_scopes, $valid_submitted ) ) ) {
            $scope_string = 'mcp';
        } else {
            $scope_string = implode( ' ', $valid_submitted );
        }

        
        
        
        if ( '' === $scope_string ) {
            return $this->handle_deny_action( $params );
        }

        $this->store_consent( $user->ID, $params['client_id'], $scope_string );
        $code = $this->mint_authorization_code( $params, $user->ID, $scope_string );
        if ( null === $code ) {
            return $this->redirect_with_error( $params, 'server_error', __( 'Failed to issue authorization code.', 'rankout-connector' ) );
        }

        return $this->redirect_with_code( $params['redirect_uri'], $code, $params['state'] );
    }

    









    public static function sign_scope( string $client_id, string $scope ): string {
        
        
        
        return hash_hmac( 'sha256', $client_id . '|' . $scope, wp_salt( 'auth' ) );
    }

    
    
    

    





    private function extract_params( \WP_REST_Request $request ) {
        
        
        
        $resource_raw = $request->get_param( 'resource' );
        $resource     = is_string( $resource_raw ) && '' !== $resource_raw
            ? esc_url_raw( $resource_raw )
            : rest_url( self::NAMESPACE_V1 . '/mcp' );

        return array(
            'response_type'         => sanitize_text_field( $request->get_param( 'response_type' ) ),
            'client_id'             => sanitize_text_field( $request->get_param( 'client_id' ) ),
            'redirect_uri'          => esc_url_raw( $request->get_param( 'redirect_uri' ) ),
            'code_challenge'        => sanitize_text_field( $request->get_param( 'code_challenge' ) ),
            'code_challenge_method' => sanitize_text_field( $request->get_param( 'code_challenge_method' ) ),
            'state'                 => sanitize_text_field( $request->get_param( 'state' ) ),
            'resource'              => $resource,
            'scope'                 => sanitize_text_field( $request->get_param( 'scope' ) ),
        );
    }

    








    private function validate_authorize_params( array $params ) {

        
        
        
        
        

        if ( empty( $params['client_id'] ) ) {
            return new \WP_Error(
                'invalid_request',
                __( 'Missing required parameter: client_id', 'rankout-connector' ),
                array( 'status' => 400, 'no_redirect' => true )
            );
        }
        if ( empty( $params['redirect_uri'] ) ) {
            return new \WP_Error(
                'invalid_request',
                __( 'Missing required parameter: redirect_uri', 'rankout-connector' ),
                array( 'status' => 400, 'no_redirect' => true )
            );
        }

        
        $client = ( new Client_Registry() )->get_client( $params['client_id'] );
        if ( ! $client ) {
            return new \WP_Error(
                'invalid_client',
                __( 'Unknown or inactive client.', 'rankout-connector' ),
                array( 'status' => 400, 'no_redirect' => true )
            );
        }

        
        $registered_uris = json_decode( $client->redirect_uris, true );
        if ( ! is_array( $registered_uris ) || ! in_array( $params['redirect_uri'], $registered_uris, true ) ) {
            
            return new \WP_Error(
                'invalid_redirect_uri',
                __( 'The redirect_uri does not match any registered URI for this client.', 'rankout-connector' ),
                array( 'status' => 400, 'no_redirect' => true )
            );
        }

        
        

        
        if ( 'code' !== $params['response_type'] ) {
            return new \WP_Error(
                'unsupported_response_type',
                __( 'Only response_type=code is supported.', 'rankout-connector' )
            );
        }

        
        
        
        
        $required = array( 'code_challenge', 'code_challenge_method' );
        foreach ( $required as $field ) {
            if ( empty( $params[ $field ] ) ) {
                return new \WP_Error(
                    'invalid_request',
                    /* translators: %s: parameter name */
                    sprintf( __( 'Missing required parameter: %s', 'rankout-connector' ), $field )
                );
            }
        }

        
        if ( 'S256' !== $params['code_challenge_method'] ) {
            return new \WP_Error(
                'invalid_request',
                __( 'Only code_challenge_method=S256 is supported.', 'rankout-connector' )
            );
        }

        
        
        if ( ! preg_match( '/^[A-Za-z0-9\-._~]{43,128}$/', $params['code_challenge'] ) ) {
            return new \WP_Error(
                'invalid_request',
                __( 'Invalid code_challenge format.', 'rankout-connector' )
            );
        }

        
        
        
        
        
        $expected_resource = rest_url( self::NAMESPACE_V1 . '/mcp' );
        if ( ! Token_Endpoint::resource_matches( $params['resource'], $expected_resource ) ) {
            return new \WP_Error(
                'invalid_target',
                __( 'Resource parameter does not match this server.', 'rankout-connector' )
            );
        }

        
        
        return $client;
    }

    






    private function error_response( \WP_Error $error, array $params ) {
        $data = $error->get_error_data();

        
        if (
            empty( $params['redirect_uri'] ) ||
            ( is_array( $data ) && ! empty( $data['no_redirect'] ) ) ||
            'invalid_redirect_uri' === $error->get_error_code()
        ) {
            $html = $this->render_error_page( $error );

            $response = new \WP_REST_Response( $html, 400 );
            $response->header( 'Content-Type', 'text/html; charset=utf-8' );
            $this->add_security_headers( $response );
            return $response;
        }

        
        $error_args = array(
            'error'             => $error->get_error_code(),
            'error_description' => $error->get_error_message(),
            'iss'               => home_url(),
        );
        if ( ! empty( $params['state'] ) ) {
            $error_args['state'] = $params['state'];
        }
        $redirect = add_query_arg( $error_args, $params['redirect_uri'] );

        $response = new \WP_REST_Response( null, 302 );
        $response->header( 'Location', $redirect );
        $this->add_security_headers( $response );
        return $response;
    }

    





    private function render_error_page( \WP_Error $error ) {
        $code    = esc_html( $error->get_error_code() );
        $message = esc_html( $error->get_error_message() );
        $title   = esc_html__( 'Authorization Error', 'rankout-connector' );

        $html  = '<!DOCTYPE html>' . "\n";
        $html .= '<html lang="en">' . "\n";
        $html .= '<head>' . "\n";
        $html .= '<meta charset="utf-8">' . "\n";
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
        $html .= '<title>' . $title . '</title>' . "\n";
        $html .= '<style>' . "\n";
        $html .= 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, sans-serif; background: #f0f0f1; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }' . "\n";
        $html .= '.error-box { background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #d63638; padding: 24px 32px; max-width: 480px; border-radius: 4px; }' . "\n";
        $html .= 'h1 { font-size: 18px; margin: 0 0 12px; color: #1d2327; }' . "\n";
        $html .= 'p { color: #50575e; margin: 0; line-height: 1.6; }' . "\n";
        $html .= 'code { background: #f0f0f1; padding: 2px 6px; border-radius: 3px; font-size: 13px; }' . "\n";
        $html .= '</style>' . "\n";
        $html .= '</head>' . "\n";
        $html .= '<body>' . "\n";
        $html .= '<div class="error-box">' . "\n";
        $html .= '<h1>' . $title . '</h1>' . "\n";
        $html .= '<p><code>' . $code . '</code>: ' . $message . '</p>' . "\n";
        $html .= '</div>' . "\n";
        $html .= '</body>' . "\n";
        $html .= '</html>' . "\n";

        return $html;
    }

    






    private function get_existing_consent( $user_id, $client_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'rankout_connector_oauth_consents';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom plugin table; name is prefixed by $wpdb->prefix (trusted); admin-side single-row lookup does not warrant object cache.
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE wp_user_id = %d AND client_id = %s LIMIT 1", $user_id, $client_id ) );
    }

    







    private function store_consent( $user_id, $client_id, $scope ) {
        global $wpdb;
        $table = $wpdb->prefix . 'rankout_connector_oauth_consents';
        $now   = current_time( 'mysql', true );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom plugin table; name is prefixed by $wpdb->prefix (trusted); upsert cannot use $wpdb->insert().
        $wpdb->query( $wpdb->prepare( "INSERT INTO {$table} (wp_user_id, client_id, scope, granted_at, updated_at) VALUES (%d, %s, %s, %s, %s) ON DUPLICATE KEY UPDATE scope = VALUES(scope), updated_at = VALUES(updated_at)", $user_id, $client_id, $scope, $now, $now ) );
    }

    







    private function mint_authorization_code( array $params, $user_id, $scope ) {
        global $wpdb;

        $raw_code  = bin2hex( random_bytes( 32 ) );
        $code_hash = hash( 'sha256', $raw_code );
        $table     = $wpdb->prefix . 'rankout_connector_oauth_codes';
        $expires   = gmdate( 'Y-m-d H:i:s', time() + self::CODE_LIFETIME );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- $wpdb->insert() on plugin-owned table; writes don't need caching.
        $inserted = $wpdb->insert(
            $table,
            array(
                'code_hash'             => $code_hash,
                'client_id'             => $params['client_id'],
                'wp_user_id'            => $user_id,
                'redirect_uri'          => $params['redirect_uri'],
                'code_challenge'        => $params['code_challenge'],
                'code_challenge_method' => $params['code_challenge_method'],
                'resource'              => $params['resource'],
                'scope'                 => $scope,
                'expires_at'            => $expires,
            ),
            array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( false === $inserted ) {
            return null;
        }

        return $raw_code;
    }

    







    private function redirect_with_code( $redirect_uri, $code, $state ) {
        
        
        $args = array(
            'code' => $code,
            'iss'  => home_url(),
        );
        if ( '' !== (string) $state ) {
            $args['state'] = $state;
        }
        $redirect = add_query_arg( $args, $redirect_uri );

        $response = new \WP_REST_Response( null, 302 );
        $response->header( 'Location', $redirect );
        $this->add_security_headers( $response );
        return $response;
    }

    





    private function build_current_url( \WP_REST_Request $request ) {
        $base   = rest_url( self::NAMESPACE_V1 . '/oauth/authorize' );
        $params = $request->get_query_params();
        if ( ! empty( $params ) ) {
            
            
            
            $allowed = array(
                'response_type',
                'client_id',
                'redirect_uri',
                'scope',
                'state',
                'code_challenge',
                'code_challenge_method',
                'resource',
            );
            $filtered = array();
            foreach ( $allowed as $key ) {
                if ( isset( $params[ $key ] ) ) {
                    $filtered[ $key ] = $params[ $key ];
                }
            }
            if ( ! empty( $filtered ) ) {
                $base = add_query_arg( $filtered, $base );
            }
        }
        return $base;
    }

    





    private function enforce_transport_security() {
        
        
        
        
        
        $result = Token_Endpoint::enforce_transport_security();
        if ( null !== $result ) {
            $this->add_security_headers( $result );
        }
        return $result;
    }

    





    private function add_security_headers( \WP_REST_Response $response, $script_nonce = null, $form_action_uri = null ) {
        $response->header( 'Referrer-Policy', 'no-referrer' );
        $response->header( 'X-Frame-Options', 'DENY' );
        
        
        
        
        
        $form_action = "'self'";
        if ( $form_action_uri ) {
            $parts = wp_parse_url( $form_action_uri );
            if ( ! empty( $parts['scheme'] ) && ! empty( $parts['host'] ) ) {
                $origin = $parts['scheme'] . '://' . $parts['host'];
                if ( ! empty( $parts['port'] ) ) {
                    $origin .= ':' . $parts['port'];
                }
                $form_action .= ' ' . $origin;
            }
        }
        $csp = "default-src 'self'; style-src 'unsafe-inline' 'self'; frame-ancestors 'none'; form-action " . $form_action;
        if ( $script_nonce ) {
            $csp .= "; script-src 'nonce-" . $script_nonce . "'";
        }
        $response->header( 'Content-Security-Policy', $csp );
        $response->header( 'X-Content-Type-Options', 'nosniff' );

        if ( is_ssl() ) {
            $response->header( 'Strict-Transport-Security', 'max-age=31536000' );
        }
    }
}
