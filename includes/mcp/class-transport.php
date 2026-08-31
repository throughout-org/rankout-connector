<?php
namespace RankOut_Connector\MCP;

use RankOut_Connector\Auth\Token_Manager;
use RankOut_Connector\Auth\Token_Auth;
use RankOut_Connector\OAuth\OAuth_Token_Validator;
use RankOut_Connector\OAuth\OAuth_Token_Manager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Transport {
    const NAMESPACE_V1     = 'rankout-connector/v1';
    const ROUTE            = '/mcp';
    const ROUTE_WITH_KEY   = '/mcp/(?P<api_key>wpmcp_[a-f0-9]{64})';
    const MAX_BATCH_SIZE   = 20;

    private $server;
    private $token_manager;

    public function __construct( Server $server, Token_Manager $token_manager ) {
        $this->server        = $server;
        $this->token_manager = $token_manager;
    }

    public function register_routes() {
        $handlers = array(
            array( 'methods' => 'POST',    'callback' => array( $this, 'handle_post' ),    'permission_callback' => '__return_true' ),
            array( 'methods' => 'GET',     'callback' => array( $this, 'handle_get' ),     'permission_callback' => '__return_true' ),
            array( 'methods' => 'DELETE',  'callback' => array( $this, 'handle_delete' ),  'permission_callback' => '__return_true' ),
            array( 'methods' => 'OPTIONS', 'callback' => array( $this, 'handle_options' ), 'permission_callback' => '__return_true' ),
        );
        \register_rest_route( self::NAMESPACE_V1, self::ROUTE, $handlers );
        
        
        \register_rest_route( self::NAMESPACE_V1, self::ROUTE_WITH_KEY, $handlers );
    }

    



    private function inject_url_token( \WP_REST_Request $request ) {
        $api_key = $request->get_param( 'api_key' );
        if ( $api_key && ! $request->get_header( 'authorization' ) ) {
            $request->set_header( 'Authorization', 'Bearer ' . $api_key );
        }
    }

    public function handle_post( \WP_REST_Request $request ) {
        $this->inject_url_token( $request );

        $origin_error = $this->validate_origin( $request );
        if ( $origin_error ) {
            return $origin_error;
        }

        $content_type = $request->get_content_type();
        if ( ! $content_type || 'application/json' !== $content_type['value'] ) {
            return new \WP_REST_Response( JSON_RPC::error_response( null, Error_Codes::INVALID_REQUEST, 'Content-Type must be application/json' ), 415 );
        }

        $accept = $request->get_header( 'accept' );
        if ( $accept && false === strpos( $accept, 'application/json' ) && false === strpos( $accept, '*/*' ) && false === strpos( $accept, 'text/event-stream' ) ) {
            return new \WP_REST_Response( null, 406 );
        }

        $parsed = JSON_RPC::parse_request( $request->get_body() );
        if ( \is_wp_error( $parsed ) ) {
            return new \WP_REST_Response( JSON_RPC::error_response( null, Error_Codes::PARSE_ERROR, $parsed->get_error_message() ), 400 );
        }

        
        $token_id = $wp_user_id = $allowed_tools = null;
        $oauth_client_id = null;
        $result   = null;
        $is_oauth = false;
        $auth_source_for_request = null;
        if ( $this->is_oauth_available() ) {
            $oauth_tm       = new OAuth_Token_Manager();
            $oauth_validator = new OAuth_Token_Validator( $oauth_tm );
            $result = $oauth_validator->authenticate( $request );
            if ( ! \is_wp_error( $result ) ) {
                $is_oauth      = true;
                $token_id      = $result['token_id'];
                $wp_user_id    = $result['wp_user_id'];
                $allowed_tools = isset( $result['allowed_tools'] ) ? $result['allowed_tools'] : null;
                $oauth_client_id = isset( $result['client_id'] ) ? $result['client_id'] : null;
                $auth_source_for_request = 'oauth';
            }
        }
        if ( null === $token_id ) {
            $auth   = new Token_Auth( $this->token_manager );
            $result = $auth->authenticate( $request );
            if ( ! \is_wp_error( $result ) ) {
                $token_id   = $result['token_id'];
                $wp_user_id = $result['wp_user_id'];
                $auth_source_for_request = 'legacy';
            }
        }

        
        
        if ( null === $token_id && ! $request->get_header( 'authorization' ) ) {
            return $this->make_unauthorized_response( null );
        }

        if ( \is_wp_error( $result ) && $request->get_header( 'authorization' ) ) {
            $ip        = trim( explode( ',', isset( $_SERVER['REMOTE_ADDR'] ) ? \sanitize_text_field( \wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' )[0] );
            $cache_key = 'rankout_connector_auth_fail_' . md5( $ip );

            
            
            
            
            if ( \wp_using_ext_object_cache() ) {
                \wp_cache_add( $cache_key, 0, 'rankout_connector', 60 );
                $new_fails = \wp_cache_incr( $cache_key, 1, 'rankout_connector' );
            } else {
                
                $new_fails = (int) \get_transient( $cache_key ) + 1;
                \set_transient( $cache_key, $new_fails, 60 );
            }

            if ( $new_fails > 20 ) {
                return new \WP_REST_Response( array( 'error' => 'Too many failed authentication attempts. Try again later.' ), 429 );
            }
            $this->server->log_auth_failure( $ip, $result->get_error_message() );
        }

        
        
        
        
        
        if ( null === $token_id ) {
            return $this->make_unauthorized_response( null, 401, 'invalid_token' );
        }

        
        $is_initialize = isset( $parsed['method'] ) && 'initialize' === $parsed['method'];
        if ( ! $is_initialize ) {
            $header_error = $this->validate_protocol_version_header( $request );
            if ( $header_error ) {
                return $header_error;
            }
        }

        
        
        if ( isset( $parsed[0] ) && ( is_array( $parsed[0] ) || is_wp_error( $parsed[0] ) ) ) {
            
            $session_version = $this->get_session_protocol_version( $request );
            if ( $session_version && version_compare( $session_version, '2025-06-18', '>=' ) ) {
                return new \WP_REST_Response(
                    JSON_RPC::error_response( null, Error_Codes::INVALID_REQUEST, 'JSON-RPC batching was removed in MCP protocol 2025-06-18' ),
                    400
                );
            }
            return $this->handle_batch( $parsed, $token_id, $wp_user_id, $request, $allowed_tools, $auth_source_for_request, $oauth_client_id );
        }
        return $this->process_single_message( $parsed, $token_id, $wp_user_id, $request, null, $allowed_tools, $auth_source_for_request, $oauth_client_id );
    }

    






    private function call_handle_message_with_identity( $message, $token_id, $allowed_tools, $auth_source, $wp_user_id, $oauth_client_id, $omit_allowed_tools = false ) {
        $this->server->set_request_identity( $auth_source, $wp_user_id, $oauth_client_id );
        try {
            return $omit_allowed_tools
                ? $this->server->handle_message( $message, $token_id )
                : $this->server->handle_message( $message, $token_id, $allowed_tools );
        } finally {
            $this->server->clear_request_identity();
        }
    }

    private function process_single_message( $message, $token_id, $wp_user_id, $request, $batch_revalidated = null, $allowed_tools = null, $auth_source = null, $oauth_client_id = null ) {
        $method = isset( $message['method'] ) ? $message['method'] : '';

        if ( empty( $method ) ) {
            $id = isset( $message['id'] ) ? $message['id'] : null;
            return new \WP_REST_Response( JSON_RPC::error_response( $id, Error_Codes::INVALID_REQUEST, 'Missing method' ), 200 );
        }

        if ( 'initialize' === $method ) {
            if ( null === $token_id ) {
                $id = isset( $message['id'] ) ? $message['id'] : null;
                return new \WP_REST_Response( JSON_RPC::error_response( $id, Error_Codes::UNAUTHORIZED, 'Valid Bearer token required' ), 200 );
            }
            if ( ! $this->set_current_user( $wp_user_id ) ) {
                $id = isset( $message['id'] ) ? $message['id'] : null;
                return new \WP_REST_Response( JSON_RPC::error_response( $id, Error_Codes::UNAUTHORIZED, 'Token user no longer exists' ), 200 );
            }
            $response_data = $this->call_handle_message_with_identity( $message, $token_id, $allowed_tools, $auth_source, $wp_user_id, $oauth_client_id );
            $response = new \WP_REST_Response( $response_data, 200 );
            if ( ! isset( $response_data['error'] ) ) {
                $negotiated = $this->server->get_last_negotiated_version() ?? Server::PROTOCOL_VERSION;
                $session_id = $this->server->get_session_manager()->create( $token_id, $wp_user_id, $negotiated, $auth_source );
                $response->header( 'Mcp-Session-Id', $session_id );
            }
            $this->add_cors_headers( $response );
            return $response;
        }

        if ( 'notifications/initialized' === $method || JSON_RPC::is_notification( $message ) ) {
            
            
            if ( null === $token_id ) {
                return $this->make_unauthorized_response( null );
            }
            if ( ! $this->set_current_user( $wp_user_id ) ) {
                return new \WP_REST_Response( null, 401 );
            }

            $this->call_handle_message_with_identity( $message, $token_id, $allowed_tools, $auth_source, $wp_user_id, $oauth_client_id );
            
            $response = new \WP_REST_Response( null, 202 );
            $this->add_cors_headers( $response );
            return $response;
        }

        if ( 'ping' === $method ) {
            
            $session_id = $request->get_header( 'mcp-session-id' );
            $authenticated = null !== $token_id;

            if ( ! $authenticated && $session_id && self::is_valid_session_id_format( $session_id ) ) {
                $revalidated = $this->revalidate_session( $session_id );
                $authenticated = false !== $revalidated;
            }

            if ( ! $authenticated ) {
                $id = isset( $message['id'] ) ? $message['id'] : null;
                return new \WP_REST_Response( JSON_RPC::error_response( $id, Error_Codes::UNAUTHORIZED, 'Authentication required' ), 200 );
            }

            $response_data = $this->call_handle_message_with_identity( $message, $token_id, $allowed_tools, $auth_source, $wp_user_id, $oauth_client_id, true );
            $response = new \WP_REST_Response( $response_data, 200 );
            $this->add_cors_headers( $response );
            return $response;
        }

        
        $session_id  = $request->get_header( 'mcp-session-id' );
        
        $revalidated = $batch_revalidated;

        if ( null === $revalidated && $session_id ) {
            if ( ! self::is_valid_session_id_format( $session_id ) ) {
                
                return new \WP_REST_Response( null, 400 );
            }
            $revalidated = $this->revalidate_session( $session_id );
            if ( false === $revalidated ) {
                
                
                
                
                
                
                if ( null === $token_id ) {
                    return new \WP_REST_Response( null, 404 );
                }
                
                $revalidated = null;
            }
        }

        
        
        
        if ( null === $token_id ) {
            $id = isset( $message['id'] ) ? $message['id'] : null;
            return new \WP_REST_Response( JSON_RPC::error_response( $id, Error_Codes::UNAUTHORIZED, 'Valid Bearer token required' ), 200 );
        }

        if ( $revalidated ) {
            
            
            
            
            
            
            
            
            $session_source = isset( $revalidated['auth_source'] ) ? $revalidated['auth_source'] : 'legacy';
            $token_source   = null === $auth_source ? 'legacy' : $auth_source;
            if ( (int) $revalidated['token_id'] !== (int) $token_id
                || $session_source !== $token_source ) {
                $id = isset( $message['id'] ) ? $message['id'] : null;
                return new \WP_REST_Response( JSON_RPC::error_response( $id, Error_Codes::FORBIDDEN, 'Bearer token does not match session owner' ), 200 );
            }
        }

        
        
        
        if ( ! $this->set_current_user( $wp_user_id ) ) {
            $id = isset( $message['id'] ) ? $message['id'] : null;
            return new \WP_REST_Response( JSON_RPC::error_response( $id, Error_Codes::UNAUTHORIZED, 'Token user no longer exists' ), 200 );
        }

        $this->token_manager->update_last_used( $token_id );
        $response_data = $this->call_handle_message_with_identity( $message, $token_id, $allowed_tools, $auth_source, $wp_user_id, $oauth_client_id );
        $response = new \WP_REST_Response( $response_data, 200 );
        $this->add_cors_headers( $response );
        return $response;
    }

    private function handle_batch( $messages, $token_id, $wp_user_id, $request, $allowed_tools = null, $auth_source = null, $oauth_client_id = null ) {
        
        
        if ( count( $messages ) > self::MAX_BATCH_SIZE ) {
            return new \WP_REST_Response(
                array( JSON_RPC::error_response( null, Error_Codes::INVALID_REQUEST, 'Batch size exceeds maximum of ' . self::MAX_BATCH_SIZE ) ),
                200
            );
        }

        
        foreach ( $messages as $message ) {
            if ( ! is_wp_error( $message ) && isset( $message['method'] ) && 'initialize' === $message['method'] ) {
                $id = isset( $message['id'] ) ? $message['id'] : null;
                return new \WP_REST_Response(
                    array( JSON_RPC::error_response( $id, Error_Codes::INVALID_REQUEST, '"initialize" must not be included in a batch request' ) ),
                    200
                );
            }
        }

        
        $session_id  = $request->get_header( 'mcp-session-id' );
        $revalidated = null;
        if ( $session_id ) {
            if ( ! self::is_valid_session_id_format( $session_id ) ) {
                return new \WP_REST_Response( null, 400 );
            }
            $revalidated = $this->revalidate_session( $session_id );
            if ( false === $revalidated ) {
                
                
                if ( null === $token_id ) {
                    return new \WP_REST_Response( null, 404 );
                }
                
                $revalidated = null;
            }
            
            
            
            
        }

        $responses = array();
        foreach ( $messages as $message ) {
            
            if ( \is_wp_error( $message ) ) {
                $responses[] = JSON_RPC::error_response( null, Error_Codes::INVALID_REQUEST, $message->get_error_message() );
                continue;
            }
            $result = $this->process_single_message( $message, $token_id, $wp_user_id, $request, $revalidated, $allowed_tools, $auth_source, $oauth_client_id );
            if ( $result instanceof \WP_REST_Response && null !== $result->get_data() ) {
                $responses[] = $result->get_data();
            }
        }
        
        $response = empty( $responses ) ? new \WP_REST_Response( null, 202 ) : new \WP_REST_Response( $responses, 200 );
        $this->add_cors_headers( $response );
        return $response;
    }

    public function handle_get( \WP_REST_Request $request ) {
        $origin_error = $this->validate_origin( $request );
        if ( $origin_error ) {
            return $origin_error;
        }

        $response = new \WP_REST_Response( array( 'error' => 'SSE streaming not supported. Use POST for MCP communication.' ), 405 );
        $response->header( 'Allow', 'POST, DELETE, OPTIONS' );
        $response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, private' );
        $response->header( 'Pragma', 'no-cache' );
        return $response;
    }

    public function handle_delete( \WP_REST_Request $request ) {
        $this->inject_url_token( $request );

        $origin_error = $this->validate_origin( $request );
        if ( $origin_error ) {
            return $origin_error;
        }

        
        $token_id    = null;
        $auth_source = null;
        if ( $this->is_oauth_available() ) {
            $oauth_tm        = new OAuth_Token_Manager();
            $oauth_validator = new OAuth_Token_Validator( $oauth_tm );
            $oauth_result    = $oauth_validator->authenticate( $request );
            if ( ! \is_wp_error( $oauth_result ) ) {
                $token_id    = $oauth_result['token_id'];
                $auth_source = 'oauth';
            }
        }
        if ( null === $token_id ) {
            $auth        = new Token_Auth( $this->token_manager );
            $legacy_result = $auth->authenticate( $request );
            if ( ! \is_wp_error( $legacy_result ) ) {
                $token_id    = $legacy_result['token_id'];
                $auth_source = 'legacy';
            }
        }
        if ( null === $token_id ) {
            return $this->make_unauthorized_response( array( 'error' => 'Authentication required' ) );
        }

        $session_id = $request->get_header( 'mcp-session-id' );
        if ( $session_id && self::is_valid_session_id_format( $session_id ) ) {
            $session_data = $this->server->get_session_manager()->validate( $session_id );
            
            
            
            
            
            
            $session_source = isset( $session_data['auth_source'] ) ? $session_data['auth_source'] : 'legacy';
            $token_source   = null === $auth_source ? 'legacy' : $auth_source;
            if ( $session_data
                && (int) $session_data['token_id'] === (int) $token_id
                && $session_source === $token_source
            ) {
                $this->server->get_session_manager()->destroy( $session_id );
            }
        }

        $response = new \WP_REST_Response( null, 204 );
        $this->add_cors_headers( $response );
        return $response;
    }

    public function handle_options( \WP_REST_Request $request ) {
        $response = new \WP_REST_Response( null, 204 );
        $this->add_cors_headers( $response );
        return $response;
    }

    







    private function validate_origin( \WP_REST_Request $request ) {
        $origin = $request->get_header( 'origin' );
        if ( empty( $origin ) ) {
            return null; 
        }

        $allowed = array( rtrim( \get_site_url(), '/' ) );

        if ( ! in_array( rtrim( $origin, '/' ), $allowed, true ) ) {
            return new \WP_REST_Response( null, 403 );
        }

        return null;
    }

    







    private function validate_protocol_version_header( \WP_REST_Request $request ) {
        $header_version = $request->get_header( 'mcp-protocol-version' );
        if ( empty( $header_version ) ) {
            
            return null;
        }
        if ( ! in_array( $header_version, Server::SUPPORTED_PROTOCOL_VERSIONS, true ) ) {
            return new \WP_REST_Response(
                array(
                    'error'     => 'unsupported_protocol_version',
                    'supported' => Server::SUPPORTED_PROTOCOL_VERSIONS,
                ),
                400
            );
        }
        
        $session_version = $this->get_session_protocol_version( $request );
        if ( $session_version && $header_version !== $session_version ) {
            return new \WP_REST_Response(
                array(
                    'error'   => 'protocol_version_mismatch',
                    'message' => 'MCP-Protocol-Version header does not match the negotiated session version.',
                ),
                400
            );
        }
        return null;
    }

    




    private function get_session_protocol_version( \WP_REST_Request $request ) {
        $session_id = $request->get_header( 'mcp-session-id' );
        if ( ! $session_id || ! self::is_valid_session_id_format( $session_id ) ) {
            return null;
        }
        $session_data = $this->server->get_session_manager()->validate( $session_id );
        if ( ! $session_data ) {
            return null;
        }
        return isset( $session_data['protocol_version'] ) ? $session_data['protocol_version'] : '2025-03-26';
    }

    private function add_cors_headers( \WP_REST_Response $response ) {
        $response->header( 'Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS' );
        $response->header( 'Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, Mcp-Session-Id, Last-Event-ID' );
        $response->header( 'Access-Control-Expose-Headers', 'Content-Type, Mcp-Session-Id' );
        
        
        $response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, private' );
        $response->header( 'Pragma', 'no-cache' );
    }

    






    private function revalidate_session( $session_id ) {
        $session_data = $this->server->get_session_manager()->validate( $session_id );
        if ( ! $session_data ) {
            return false;
        }

        
        
        
        
        $auth_source = isset( $session_data['auth_source'] ) ? $session_data['auth_source'] : 'legacy';
        $token_id    = (int) $session_data['token_id'];

        if ( 'oauth' === $auth_source ) {
            if ( ! $this->is_oauth_available() ) {
                $this->server->get_session_manager()->destroy( $session_id );
                return false;
            }

            
            $throttle_key = 'rankout_connector_oat_srv_' . $token_id;
            $cached = \get_transient( $throttle_key );
            if ( is_array( $cached ) ) {
                $this->server->get_session_manager()->touch( $session_id );
                return $cached;
            }

            global $wpdb;
            $table = $wpdb->prefix . 'rankout_connector_oauth_access_tokens';
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table prefixed by $wpdb->prefix; token revalidation must be fresh.
            $row = $wpdb->get_row(
                $wpdb->prepare( "SELECT id, wp_user_id, is_active, expires_at FROM {$table} WHERE id = %d LIMIT 1", $token_id ),
                ARRAY_A
            );
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
            if ( ! $row || empty( $row['is_active'] ) ) {
                $this->server->get_session_manager()->destroy( $session_id );
                return false;
            }
            if ( ! empty( $row['expires_at'] ) && strtotime( $row['expires_at'] . ' UTC' ) < time() ) {
                $this->server->get_session_manager()->destroy( $session_id );
                return false;
            }
            $result = array(
                'token_id'    => (int) $row['id'],
                'wp_user_id'  => (int) $row['wp_user_id'],
                'auth_source' => 'oauth',
            );
            \set_transient( $throttle_key, $result, 60 );
            $this->server->get_session_manager()->touch( $session_id );
            return $result;
        }

        
        $token = $this->token_manager->get_token_by_id( $token_id );
        if ( ! $token || empty( $token['is_active'] ) ) {
            $this->server->get_session_manager()->destroy( $session_id );
            return false;
        }
        
        if ( ! empty( $token['expires_at'] ) && strtotime( $token['expires_at'] . ' UTC' ) < time() ) {
            $this->server->get_session_manager()->destroy( $session_id );
            return false;
        }
        $auth = new Token_Auth( $this->token_manager );
        if ( ! $auth->check_ip_allowed() ) {
            $this->server->get_session_manager()->destroy( $session_id );
            return false;
        }
        $this->server->get_session_manager()->touch( $session_id );
        return array(
            'token_id'    => (int) $token['id'],
            'wp_user_id'  => (int) $token['wp_user_id'],
            'auth_source' => 'legacy',
        );
    }

    


    public static function is_valid_session_id_format( $session_id ) {
        return is_string( $session_id ) && 1 === preg_match( '/^[0-9a-f]{64}$/', $session_id );
    }

    





    private function set_current_user( $user_id ) {
        $user_id = (int) $user_id;
        if ( $user_id > 0 && \get_userdata( $user_id ) ) {
            \wp_set_current_user( $user_id );
            return true;
        }
        return false;
    }

    


    private function is_oauth_available() {
        if ( ! \apply_filters( 'rankout_connector_oauth_enabled', true ) ) {
            return false;
        }
        $file = RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-oauth-token-validator.php';
        if ( ! file_exists( $file ) ) {
            return false;
        }
        if ( ! class_exists( OAuth_Token_Validator::class ) ) {
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-oauth-token-manager.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-oauth-token-validator.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-scope-map.php';
        }
        return true;
    }

    





    private function make_unauthorized_response( $data, $http_status = 401, $error_code = null ) {
        $response = new \WP_REST_Response( $data, $http_status );

        
        
        
        
        $params = array();
        if ( $error_code ) {
            
            
            $params[] = 'error="' . $error_code . '"';
            $params[] = 'error_description="The access token is invalid or expired"';
        }
        if ( $this->is_oauth_available() ) {
            
            $params[] = 'resource_metadata="' . \home_url( '/.well-known/oauth-protected-resource' ) . '"';
        }
        $challenge = 'Bearer' . ( $params ? ' ' . implode( ', ', $params ) : '' );
        $response->header( 'WWW-Authenticate', $challenge );

        $this->add_cors_headers( $response );
        return $response;
    }
}
