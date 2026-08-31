<?php
namespace RankOut_Connector\MCP;

use RankOut_Connector\Auth\Token_Manager;
use RankOut_Connector\Auth\Permission_Guard;
use RankOut_Connector\Tools\Tool_Registry;
use RankOut_Connector\Resources\Resource_Registry;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Server {
    const PROTOCOL_VERSION = '2025-11-25';
    const SERVER_NAME      = 'rankout-connector';

    private $tool_registry;
    private $resource_registry;
    private $token_manager;
    private $session_manager;
    private $permission_guard;
    private $disabled_tools;
    private $audit_log_enabled;
    private $allowed_tool_patterns;
    private $last_negotiated_version;

    
    
    
    private $request_auth_source = null;
    private $request_wp_user_id  = 0;
    private $request_client_id   = null;

    public function __construct( Tool_Registry $tool_registry, Resource_Registry $resource_registry, Token_Manager $token_manager ) {
        $this->tool_registry     = $tool_registry;
        $this->resource_registry = $resource_registry;
        $this->token_manager     = $token_manager;
        $this->session_manager   = new Session();
        $this->permission_guard  = new Permission_Guard( $token_manager );
        $this->disabled_tools       = (array) get_option( 'rankout_connector_disabled_tools', array() );
        $this->audit_log_enabled    = (bool)  get_option( 'rankout_connector_audit_log_enabled', true );
        $this->allowed_tool_patterns = (array) get_option( 'rankout_connector_allowed_tool_patterns', array() );

        
        
        register_shutdown_function( function () {
            $err = error_get_last();
            if ( ! $err ) { return; }
            if ( ! in_array( $err['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ), true ) ) {
                return;
            }
            if ( ! class_exists( '\\RankOut_Connector\\History\\Change_Context' ) ) { return; }
            if ( ! \RankOut_Connector\History\Change_Context::is_active() ) { return; }
            $audit_id = \RankOut_Connector\History\Change_Context::get( 'audit_id' );
            if ( $audit_id ) {
                $this->update_audit_status( (int) $audit_id, 'error' );
            }
        } );
    }

    public function set_request_identity( $auth_source, $wp_user_id, $client_id = null ) {
        $this->request_auth_source = $auth_source;
        $this->request_wp_user_id  = (int) $wp_user_id;
        $this->request_client_id   = $client_id;
    }

    public function clear_request_identity() {
        $this->request_auth_source = null;
        $this->request_wp_user_id  = 0;
        $this->request_client_id   = null;
    }

    public function handle_message( $message, $token_id = null, $allowed_tools = null ) {
        $method = isset( $message['method'] ) ? $message['method'] : '';
        $params = isset( $message['params'] ) ? $message['params'] : array();
        $id     = isset( $message['id'] ) ? $message['id'] : null;

        if ( JSON_RPC::is_notification( $message ) ) {
            return null;
        }

        switch ( $method ) {
            case 'initialize':
                return $this->handle_initialize( $id, $params, $token_id );
            case 'ping':
                return JSON_RPC::success_response( $id, new \stdClass() );
            case 'tools/list':
                return $this->handle_tools_list( $id, $params, $token_id, $allowed_tools );
            case 'tools/call':
                return $this->handle_tools_call( $id, $params, $token_id, $allowed_tools );
            case 'resources/list':
                return $this->handle_resources_list( $id, $params, $token_id );
            case 'resources/read':
                return $this->handle_resources_read( $id, $params, $token_id );
            case 'prompts/list':
                return JSON_RPC::success_response( $id, array( 'prompts' => array() ) );
            case 'prompts/get':
                return JSON_RPC::error_response( $id, Error_Codes::METHOD_NOT_FOUND, 'No prompts available' );
            default:
                return JSON_RPC::error_response( $id, Error_Codes::METHOD_NOT_FOUND, 'Method not found' );
        }
    }

    
    const SUPPORTED_PROTOCOL_VERSIONS = array( '2025-11-25', '2025-06-18', '2025-03-26' );

    private function handle_initialize( $id, $params, $token_id ) {
        
        if ( ! $this->check_rate_limit( $token_id ) ) {
            return JSON_RPC::error_response( $id, Error_Codes::RATE_LIMITED, 'Rate limit exceeded. Please try again later.' );
        }

        
        
        
        
        $client_version = isset( $params['protocolVersion'] ) ? $params['protocolVersion'] : null;
        if ( $client_version && in_array( $client_version, self::SUPPORTED_PROTOCOL_VERSIONS, true ) ) {
            $negotiated_version = $client_version;
        } else {
            
            $negotiated_version = self::PROTOCOL_VERSION;
        }

        
        $this->last_negotiated_version = $negotiated_version;

        return JSON_RPC::success_response( $id, array(
            'protocolVersion' => $negotiated_version,
            'capabilities'    => array(
                'tools'     => new \stdClass(),
                'resources' => new \stdClass(),
            ),
            'serverInfo'      => array( 'name' => self::SERVER_NAME, 'version' => RANKOUT_CONNECTOR_VERSION ),
            'instructions'    => 'WordPress MCP Server. Use tools to manage posts, pages, media, comments, users, and site settings. Use resources to read site information.',
        ) );
    }

    




    public function get_last_negotiated_version() {
        return isset( $this->last_negotiated_version ) ? $this->last_negotiated_version : null;
    }

    private function handle_tools_list( $id, $params, $token_id, $allowed_tools = null ) {
        if ( null === $token_id ) {
            return JSON_RPC::error_response( $id, Error_Codes::UNAUTHORIZED, 'Authentication required' );
        }
        if ( ! $this->check_rate_limit( $token_id ) ) {
            return JSON_RPC::error_response( $id, Error_Codes::RATE_LIMITED, 'Rate limit exceeded. Please try again later.' );
        }

        $all_tools = $this->tool_registry->get_all_definitions();
        
        $allowed = null !== $allowed_tools ? $allowed_tools : $this->permission_guard->get_allowed_tools( $token_id );
        if ( ! in_array( '*', $allowed, true ) ) {
            $all_tools = array_values( array_filter( $all_tools, function ( $tool ) use ( $allowed ) {
                $name = $tool['name'];
                if ( in_array( $name, $allowed, true ) ) {
                    return true;
                }
                
                foreach ( $allowed as $pattern ) {
                    if ( false !== strpos( $pattern, '*' ) && fnmatch( $pattern, $name ) ) {
                        return true;
                    }
                }
                return false;
            } ) );
        }
        if ( ! empty( $this->disabled_tools ) ) {
            $all_tools = array_values( array_filter( $all_tools, function ( $tool ) {
                return ! in_array( $tool['name'], $this->disabled_tools, true );
            } ) );
        }
        if ( ! empty( $this->allowed_tool_patterns ) ) {
            $all_tools = array_values( array_filter( $all_tools, function ( $tool ) {
                return $this->tool_matches_pattern_filter( $tool['name'] );
            } ) );
        }
        return JSON_RPC::success_response( $id, array( 'tools' => $all_tools ) );
    }

    private function handle_tools_call( $id, $params, $token_id, $allowed_tools = null ) {
        $tool_name = isset( $params['name'] ) ? $params['name'] : '';
        $arguments = isset( $params['arguments'] ) ? $params['arguments'] : array();

        if ( empty( $tool_name ) ) {
            return JSON_RPC::error_response( $id, Error_Codes::INVALID_PARAMS, 'Missing tool name' );
        }

        
        if ( null === $token_id ) {
            return JSON_RPC::error_response( $id, Error_Codes::UNAUTHORIZED, 'Authentication required' );
        }
        
        if ( null !== $allowed_tools ) {
            if ( ! $this->permission_guard->can_use_tool_with_scope( $allowed_tools, $tool_name ) ) {
                return JSON_RPC::error_response( $id, Error_Codes::FORBIDDEN, 'Token does not have permission to use this tool' );
            }
        } elseif ( ! $this->permission_guard->can_use_tool( $token_id, $tool_name ) ) {
            return JSON_RPC::error_response( $id, Error_Codes::FORBIDDEN, 'Token does not have permission to use this tool' );
        }

        
        if ( ! $this->check_rate_limit( $token_id ) ) {
            return JSON_RPC::error_response( $id, Error_Codes::RATE_LIMITED, 'Rate limit exceeded. Please try again later.' );
        }

        $tool = $this->tool_registry->get_tool( $tool_name );
        if ( null === $tool ) {
            
            return JSON_RPC::error_response( $id, Error_Codes::INVALID_PARAMS, 'Unknown tool' );
        }

        $required_cap = $tool->get_required_capability();
        if ( $required_cap && ! \current_user_can( $required_cap ) ) {
            return JSON_RPC::error_response( $id, Error_Codes::FORBIDDEN, 'Insufficient WordPress permissions for this tool' );
        }

        if ( ! empty( $this->disabled_tools ) && in_array( $tool_name, $this->disabled_tools, true ) ) {
            return JSON_RPC::error_response( $id, Error_Codes::FORBIDDEN, 'This tool has been disabled by the administrator.' );
        }

        if ( ! $this->tool_matches_pattern_filter( $tool_name ) ) {
            return JSON_RPC::error_response( $id, Error_Codes::FORBIDDEN, 'This tool has been disabled by the administrator.' );
        }

        
        
        $audit_id     = $this->log_tool_call( $token_id, $tool_name, $arguments, 'pending' );
        $final_status = null;

        if ( class_exists( '\\RankOut_Connector\\History\\Change_Context' ) ) {
            \RankOut_Connector\History\Change_Context::set( array(
                'audit_id'        => $audit_id,
                'tool_name'       => $tool_name,
                'token_id'        => $token_id ? (int) $token_id : 0,
                'auth_source'     => $this->request_auth_source,
                'oauth_client_id' => $this->request_client_id,
                'wp_user_id'      => $this->request_wp_user_id,
                'ip_address'      => self::get_client_ip(),
            ) );
        }

        try {
            $result       = $tool->execute( $arguments );
            $final_status = 'success';
            return JSON_RPC::success_response( $id, array(
                'content' => array( array(
                    'type' => 'text',
                    'text' => is_string( $result ) ? $result : wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
                ) ),
            ) );
        } catch ( \Exception $e ) {
            
            
            
            $final_status = 'error';
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( sprintf( 'WP MCP Server tool exception [%s]: %s in %s:%d', $tool_name, $e->getMessage(), $e->getFile(), $e->getLine() ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional debug logging
            }
            return JSON_RPC::success_response( $id, array(
                'content' => array( array( 'type' => 'text', 'text' => 'Error: ' . self::sanitize_error_message( $e->getMessage() ) ) ),
                'isError' => true,
            ) );
        } catch ( \Error $e ) {
            
            
            $final_status = 'error';
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( sprintf( 'WP MCP Server tool error [%s]: %s in %s:%d', $tool_name, $e->getMessage(), $e->getFile(), $e->getLine() ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional debug logging
            }
            return JSON_RPC::success_response( $id, array(
                'content' => array( array( 'type' => 'text', 'text' => 'Tool execution failed. Check server error logs for details.' ) ),
                'isError' => true,
            ) );
        } finally {
            
            
            
            $this->update_audit_status( $audit_id, null === $final_status ? 'error' : $final_status );
            if ( class_exists( '\\RankOut_Connector\\History\\Change_Context' ) ) {
                \RankOut_Connector\History\Change_Context::clear();
            }
            
            
            
            \RankOut_Connector\Tools\Base_Tool::flush_deferred_purges();
        }
    }

    




    public static function sanitize_error_message( $message ) {
        if ( ! is_string( $message ) || '' === $message ) {
            return 'Tool execution failed.';
        }

        
        $message = preg_replace( '/\s*Stack trace:.*$/s', '', $message );

        
        $message = preg_replace( '/\s+in\s+\S+\.php(?:\(\d+\)|:\d+| on line \d+)/', '', $message );

        
        $message = preg_replace( '#(?:/|[A-Z]:\\\\)[^\s\'"<>]*\.(?:php|inc|tpl|phtml)\b#', '[file]', $message );

        
        $message = preg_replace_callback(
            '/\b(?:\d{1,3}\.){3}\d{1,3}\b/',
            function ( $m ) {
                $ip = $m[0];
                $parts = array_map( 'intval', explode( '.', $ip ) );
                if ( 127 === $parts[0] ) { return '[internal]'; }
                if ( 10 === $parts[0] ) { return '[internal]'; }
                if ( 192 === $parts[0] && 168 === $parts[1] ) { return '[internal]'; }
                if ( 172 === $parts[0] && $parts[1] >= 16 && $parts[1] <= 31 ) { return '[internal]'; }
                if ( 169 === $parts[0] && 254 === $parts[1] ) { return '[internal]'; }
                return $ip;
            },
            $message
        );
        $message = preg_replace( '/\[?::1\]?|\blocalhost\b/i', '[internal]', $message );

        
        $message = trim( $message );
        if ( strlen( $message ) > 200 ) {
            $message = substr( $message, 0, 200 ) . '…[truncated]';
        }

        return '' === $message ? 'Tool execution failed.' : $message;
    }

    private function handle_resources_list( $id, $params, $token_id ) {
        if ( null === $token_id ) {
            return JSON_RPC::error_response( $id, Error_Codes::UNAUTHORIZED, 'Authentication required' );
        }
        if ( ! $this->check_rate_limit( $token_id ) ) {
            return JSON_RPC::error_response( $id, Error_Codes::RATE_LIMITED, 'Rate limit exceeded. Please try again later.' );
        }
        
        
        
        if ( ! \current_user_can( 'read' ) ) {
            return JSON_RPC::error_response( $id, Error_Codes::FORBIDDEN, 'Insufficient permissions to list resources' );
        }
        return JSON_RPC::success_response( $id, array( 'resources' => $this->resource_registry->get_all_definitions() ) );
    }

    private function handle_resources_read( $id, $params, $token_id ) {
        if ( null === $token_id ) {
            return JSON_RPC::error_response( $id, Error_Codes::UNAUTHORIZED, 'Authentication required' );
        }
        if ( ! $this->check_rate_limit( $token_id ) ) {
            return JSON_RPC::error_response( $id, Error_Codes::RATE_LIMITED, 'Rate limit exceeded. Please try again later.' );
        }

        
        if ( ! \current_user_can( 'read' ) ) {
            return JSON_RPC::error_response( $id, Error_Codes::FORBIDDEN, 'Insufficient permissions to read resources' );
        }

        $uri = isset( $params['uri'] ) ? $params['uri'] : '';
        if ( empty( $uri ) ) {
            return JSON_RPC::error_response( $id, Error_Codes::INVALID_PARAMS, 'Missing resource URI' );
        }
        $resource = $this->resource_registry->get_resource( $uri );
        if ( null === $resource ) {
            return JSON_RPC::error_response( $id, Error_Codes::RESOURCE_NOT_FOUND, 'Resource not found' );
        }
        try {
            $content = $resource->read();
            return JSON_RPC::success_response( $id, array(
                'contents' => array( array(
                    'uri'      => $uri,
                    'mimeType' => $resource->get_mime_type(),
                    'text'     => is_string( $content ) ? $content : wp_json_encode( $content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
                ) ),
            ) );
        } catch ( \Throwable $e ) {
            return JSON_RPC::error_response( $id, Error_Codes::INTERNAL_ERROR, 'Failed to read resource' );
        }
    }

    private function check_rate_limit( $token_id ) {
        if ( null === $token_id ) {
            return true; 
        }
        $limit     = (int) get_option( 'rankout_connector_rate_limit_per_minute', 60 );
        $cache_key = 'rankout_connector_rate_' . (int) $token_id;

        
        
        
        if ( \wp_using_ext_object_cache() ) {
            \wp_cache_add( $cache_key, 0, 'rankout_connector', 60 );
            $new_count = \wp_cache_incr( $cache_key, 1, 'rankout_connector' );
            return $new_count <= $limit;
        }

        
        
        
        
        $current = (int) \get_transient( $cache_key );
        if ( $current >= $limit ) {
            return false;
        }
        \set_transient( $cache_key, $current + 1, 60 );
        return true;
    }

    public function log_auth_failure( $ip, $reason ) {
        if ( ! $this->audit_log_enabled ) {
            return;
        }
        global $wpdb;
        $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct insert required for audit logging.
            $wpdb->prefix . 'rankout_connector_audit_log',
            array(
                'token_id'      => 0,
                'tool_name'     => '_auth_failure',
                'arguments'     => wp_json_encode( array( 'reason' => $reason ) ),
                'result_status' => 'auth_failure',
                'ip_address'    => $ip,
                'created_at'    => current_time( 'mysql', true ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    private function log_tool_call( $token_id, $tool_name, $arguments, $status ) {
        if ( ! $this->audit_log_enabled ) {
            return 0;
        }
        global $wpdb;
        $safe_args = self::redact_sensitive_args( $arguments );
        $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct insert required for audit logging.
            $wpdb->prefix . 'rankout_connector_audit_log',
            array(
                'token_id'      => $token_id ? (int) $token_id : 0,
                'tool_name'     => $tool_name,
                'arguments'     => wp_json_encode( $safe_args ),
                'result_status' => $status,
                'ip_address'    => self::get_client_ip(),
                'created_at'    => current_time( 'mysql', true ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );
        return (int) $wpdb->insert_id;
    }

    private function update_audit_status( $audit_id, $status ) {
        if ( ! $this->audit_log_enabled || ! $audit_id ) {
            return;
        }
        global $wpdb;
        $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct update required to finalize audit status.
            $wpdb->prefix . 'rankout_connector_audit_log',
            array( 'result_status' => $status ),
            array( 'id' => (int) $audit_id ),
            array( '%s' ),
            array( '%d' )
        );
    }

    private static function redact_sensitive_args( $args ) {
        if ( ! is_array( $args ) ) {
            return $args;
        }
        
        $sensitive_pattern = '/^(password|pass|secret|token|api[_\-]?key|authorization|content_base64|private[_\-]?key|access[_\-]?token|client[_\-]?secret|credential)$/i';
        $result = array();
        foreach ( $args as $key => $value ) {
            if ( preg_match( $sensitive_pattern, $key ) ) {
                $result[ $key ] = '[REDACTED]';
            } elseif ( is_array( $value ) ) {
                $result[ $key ] = self::redact_sensitive_args( $value );
            } else {
                $result[ $key ] = $value;
            }
        }
        return $result;
    }

    private static function get_client_ip() {
        
        
        if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        } else {
            $ip = '';
        }
        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
    }

    private function tool_matches_pattern_filter( $tool_name ) {
        if ( empty( $this->allowed_tool_patterns ) ) {
            return true;
        }
        foreach ( $this->allowed_tool_patterns as $pattern ) {
            $pattern = trim( $pattern );
            if ( '' === $pattern ) {
                continue;
            }
            
            if ( false === strpos( $pattern, '*' ) && false === strpos( $pattern, '?' ) ) {
                $pattern = '*' . $pattern . '*';
            }
            if ( fnmatch( $pattern, $tool_name ) ) {
                return true;
            }
        }
        return false;
    }

    public function get_session_manager() {
        return $this->session_manager;
    }
}
