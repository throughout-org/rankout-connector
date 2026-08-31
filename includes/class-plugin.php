<?php
namespace RankOut_Connector;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/class-activator.php';
require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/class-github-updater.php';

class Plugin {

    





    const CLEANUP_MAX_ITERATIONS = 20;

    private static $instance = null;
    private $server;
    private $token_manager;
    private $tool_registry;
    private $resource_registry;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        
        
        
        \add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        \add_action( 'init', array( $this, 'handle_well_known' ), 0 );
        \add_action( 'wp_head', array( $this, 'output_post_schema' ) );

        new GitHub_Updater( RANKOUT_CONNECTOR_PLUGIN_FILE, RANKOUT_CONNECTOR_VERSION );
        
        
        
        
        
        
        
        
        
        
        \add_action( 'init', array( $this, 'handle_oauth_authorize_request' ), PHP_INT_MAX );
        \add_action( 'rankout_connector_cleanup_audit_log', array( $this, 'cleanup_audit_log' ) );
        \add_action( 'rankout_connector_cleanup_oauth', array( $this, 'cleanup_oauth_storage' ) );
        \add_action( 'rankout_connector_cleanup_new_token_meta', array( $this, 'cleanup_new_token_meta' ) );
        \add_action( 'rankout_connector_cleanup_change_log', array( __CLASS__, 'cleanup_change_log' ) );
        
        \add_action( 'plugins_loaded', array( 'RankOut_Connector\Activator', 'maybe_upgrade' ) );
        \add_action( 'plugins_loaded', array( $this, 'maybe_upgrade_oauth' ) );
        
        if ( \is_multisite() ) {
            \add_action( 'wp_initialize_site', array( $this, 'on_new_site' ), 10, 1 );
        }
        if ( \is_admin() && ! \wp_doing_cron() ) {
            if ( \wp_doing_ajax() ) {
                
                
                \add_action( 'init', array( $this, 'init_admin_ajax' ) );
            } else {
                \add_action( 'init', array( $this, 'init_admin' ) );
            }
        }
    }

    


    private function load_mcp_includes() {
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/mcp/class-error-codes.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/mcp/class-json-rpc.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/mcp/class-session.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/mcp/class-transport.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/mcp/class-server.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/auth/class-token-manager.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/auth/class-token-auth.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/auth/class-permission-guard.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/tools/class-base-tool.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/tools/class-tool-registry.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/tools/class-dynamic-tool.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/tools/class-dynamic-tool-registrar.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/class-abstract-google-client.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/gsc/class-gsc-client.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/ga/class-ga-client.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/dfs/class-dataforseo-client.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/semrush/class-semrush-client.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/semrush/class-semrush-validators.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/resources/class-base-resource.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/resources/class-resource-registry.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/history/class-change-log-schema.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/history/class-change-redactor.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/history/class-change-context.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/history/class-change-log-repository.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/history/class-change-recorder.php';
    }

    



    public function init_admin_ajax() {
        
        
        
        
        
        
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only gate to decide whether to load AJAX handler files; the actual wp_ajax_* handler verifies its own nonce via check_ajax_referer().
        $action = isset( $_REQUEST['action'] ) ? \sanitize_key( \wp_unslash( $_REQUEST['action'] ) ) : '';

        
        static $external_data_actions = array(
            'rankout_connector_gsc_test',
            'rankout_connector_ga_test',
            'rankout_connector_dfs_test',
            'rankout_connector_dfs_refresh_balance',
            'rankout_connector_semrush_test',
            'rankout_connector_semrush_refresh_balance',
        );
        if ( in_array( $action, $external_data_actions, true ) ) {
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/class-abstract-google-client.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/gsc/class-gsc-client.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/ga/class-ga-client.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/dfs/class-dataforseo-client.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/semrush/class-semrush-client.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/semrush/class-semrush-validators.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/admin/class-external-data-admin.php';
            new Admin\External_Data_Admin();
            return;
        }

        
        
        
        
        
        
        
        if ( 'rankout_connector_get_changes_for_audit' === $action ) {
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/auth/class-token-manager.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/tools/class-base-tool.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/tools/class-tool-registry.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/admin/class-plugin-integration-registry.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/admin/class-plugin-integrations-page.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/admin/class-admin-page.php';
            new Admin\Admin_Page( new Auth\Token_Manager(), new Tools\Tool_Registry() );
            return;
        }
    }

    public function init_admin() {
        $admin_lang = \get_option( 'rankout_connector_admin_language', '' );
        if ( ! empty( $admin_lang ) ) {
            
            
            $safe_lang = preg_replace( '/[^a-zA-Z_]/', '', $admin_lang );
            $mo_file = RANKOUT_CONNECTOR_PLUGIN_DIR . 'languages/rankout-connector-' . $safe_lang . '.mo';
            if ( file_exists( $mo_file ) ) {
                \unload_textdomain( 'rankout-connector' );
                \load_textdomain( 'rankout-connector', $mo_file );
            }
        }
        $this->load_mcp_includes();
        $this->token_manager = new Auth\Token_Manager();
        $this->tool_registry = new Tools\Tool_Registry();
        $this->register_tools();
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/admin/class-admin-page.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/admin/class-abilities-page.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/admin/class-external-data-admin.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/admin/class-plugin-integration-registry.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/admin/class-plugin-integrations-page.php';
        new Admin\Admin_Page( $this->token_manager, $this->tool_registry );
        new Admin\Abilities_Page();
        new Admin\External_Data_Admin();
        if ( \apply_filters( 'rankout_connector_oauth_enabled', true ) ) {
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/admin/class-oauth-admin.php';
            new Admin\OAuth_Admin();
        }
    }

    









    public function handle_well_known() {
        
        
        
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public routing check on REQUEST_URI; no state is mutated here.
        $request_uri_raw = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        $is_well_known   = false !== strpos( $request_uri_raw, '/.well-known/' );
        if ( ! $is_well_known ) {
            
            
            
            return;
        }

        if ( ! \apply_filters( 'rankout_connector_oauth_enabled', true ) ) {
            return;
        }

        
        $request_uri = \wp_parse_url( $request_uri_raw, PHP_URL_PATH );

        
        $home_path = trim( \wp_parse_url( \home_url(), PHP_URL_PATH ) ?? '', '/' );
        if ( $home_path ) {
            $request_uri = preg_replace( '#^/' . preg_quote( $home_path, '#' ) . '#', '', $request_uri );
        }

        $is_protected_resource = ( '/.well-known/oauth-protected-resource' === $request_uri );
        $is_auth_server        = ( '/.well-known/oauth-authorization-server' === $request_uri
                                || '/.well-known/openid-configuration' === $request_uri );
        if ( ! $is_protected_resource && ! $is_auth_server ) {
            return;
        }

        
        
        
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/tools/class-dynamic-tool-registrar.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-scope-map.php';
        
        
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-token-endpoint.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-discovery.php';
        $discovery = new OAuth\Discovery();
        $rest_req  = new \WP_REST_Request( 'GET' );
        $response  = $is_protected_resource
            ? $discovery->get_protected_resource_metadata( $rest_req )
            : $discovery->get_authorization_server_metadata( $rest_req );

        
        
        
        if ( is_wp_error( $response ) ) {
            $err_data = $response->get_error_data();
            $status   = is_array( $err_data ) && isset( $err_data['status'] ) ? (int) $err_data['status'] : 400;
            $body     = array(
                'error'             => $response->get_error_code(),
                'error_description' => $response->get_error_message(),
            );
        } elseif ( $response instanceof \WP_REST_Response ) {
            $status = $response->get_status();
            $body   = $response->get_data();
        } else {
            $status = 200;
            $body   = $response;
        }

        \status_header( $status );
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Access-Control-Allow-Origin: *' );
        header( 'Cache-Control: no-store' );
        header( 'Pragma: no-cache' );
        echo \wp_json_encode( $body ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded.
        exit;
    }

    









    public function handle_oauth_authorize_request() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public routing check; authorize handler enforces its own nonce downstream.
        $oauth_param = isset( $_GET['rankout_connector_oauth'] ) ? sanitize_text_field( wp_unslash( $_GET['rankout_connector_oauth'] ) ) : '';
        if ( 'authorize' !== $oauth_param ) {
            return;
        }
        if ( ! \apply_filters( 'rankout_connector_oauth_enabled', true ) ) {
            return;
        }
        $this->handle_oauth_authorize();
    }

    







    private function handle_oauth_authorize() {
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-oauth-schema.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-scope-map.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-consent-screen.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-client-registry.php';
        
        
        
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-token-endpoint.php';
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-authorization-endpoint.php';

        $method  = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
        $request = new \WP_REST_Request( $method );

        if ( 'POST' === $method ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified inside Authorization_Endpoint::handle_post().
            $request->set_body_params( isset( $_POST ) ? \wp_unslash( $_POST ) : array() );
        } else {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth params, no state change.
            $request->set_query_params( isset( $_GET ) ? \wp_unslash( $_GET ) : array() );
        }

        $endpoint = new OAuth\Authorization_Endpoint();
        $response = 'POST' === $method ? $endpoint->handle_post( $request ) : $endpoint->handle_get( $request );

        $this->send_authorize_response( $response );
        exit;
    }

    









    private function send_authorize_response( $response ) {
        if ( \is_wp_error( $response ) ) {
            $status = 400;
            $data   = $response->get_error_data();
            if ( is_array( $data ) && ! empty( $data['status'] ) ) {
                $status = (int) $data['status'];
            }
            \status_header( $status );
            header( 'Content-Type: text/html; charset=utf-8' );
            header( 'X-Frame-Options: DENY' );
            header( "Content-Security-Policy: frame-ancestors 'none'" );
            header( 'X-Content-Type-Options: nosniff' );
            $code    = \esc_html( $response->get_error_code() );
            $message = \esc_html( $response->get_error_message() );
            echo '<!DOCTYPE html><html><body><p>' . $code . ': ' . $message . '</p></body></html>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped above.
            return;
        }

        \status_header( $response->get_status() );

        
        
        header_remove( 'Content-Security-Policy' );

        $headers      = $response->get_headers();
        $content_type = '';
        foreach ( $headers as $name => $value ) {
            header( $name . ': ' . $value );
            if ( 0 === strcasecmp( $name, 'Content-Type' ) ) {
                $content_type = (string) $value;
            }
        }

        $data = $response->get_data();
        if ( null === $data ) {
            return;
        }

        
        
        
        if ( is_string( $data ) && 0 === strpos( strtolower( $content_type ), 'text/html' ) ) {
            echo $data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in template.
            return;
        }

        
        if ( '' === $content_type ) {
            header( 'Content-Type: application/json; charset=utf-8' );
        }
        echo \wp_json_encode( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded.
    }

    public function register_rest_routes() {
        
        $this->load_mcp_includes();

        
        
        \RankOut_Connector\History\Change_Log_Schema::maybe_upgrade();

        $this->token_manager     = new Auth\Token_Manager();
        $this->tool_registry     = new Tools\Tool_Registry();
        $this->resource_registry = new Resources\Resource_Registry();
        $this->server            = new MCP\Server( $this->tool_registry, $this->resource_registry, $this->token_manager );

        $this->register_tools();
        $this->register_resources();

        
        
        
        if ( \get_option( 'rankout_connector_change_log_enabled', true ) ) {
            ( new \RankOut_Connector\History\Change_Recorder(
                new \RankOut_Connector\History\Change_Log_Repository()
            ) )->register();
        }

        $transport = new MCP\Transport( $this->server, $this->token_manager );
        $transport->register_routes();

        
        if ( \apply_filters( 'rankout_connector_oauth_enabled', true ) ) {
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-oauth-schema.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-scope-map.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-discovery.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-client-registry.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-oauth-token-manager.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-oauth-token-validator.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-authorization-endpoint.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-token-endpoint.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-consent-screen.php';
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-oauth-routes.php';
            $oauth_routes = new OAuth\OAuth_Routes();
            $oauth_routes->register_routes();
        }
    }

    private function register_tools() {
        $tool_dirs = array(
            'posts', 'pages', 'media', 'taxonomy', 'comments',
            'users', 'site', 'menus', 'plugins', 'themes',
            'revisions', 'meta', 'search', 'blocks', 'cpt', 'templates', 'styles',
            'history', 'schema', 'filesystem', 'database', 'geo', 'aeo', 'eeat', 'reporting',
            'site-health',
        );

        // GA4/GSC/Semrush/DataForSEO tool categories are deliberately never
        // loaded here — RankOut's own dashboard already has first-party
        // Google OAuth integrations for GA/GSC and its own paid Semrush/
        // DataForSEO API relationships; a client's WordPress admin pasting
        // its own copies of those credentials would be a strictly worse,
        // redundant credential surface for data RankOut already pulls
        // itself. The External Data admin settings page and these client
        // classes are left in place (dead but harmless) rather than
        // deleted outright, since class-external-data-admin.php still
        // references them — removing that page is a follow-up cleanup,
        // not required for these tool categories to be genuinely inert.

        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/tools/users/trait-user-meta-auth-guard.php';

        foreach ( $tool_dirs as $dir ) {
            $tool_path = RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/tools/' . $dir . '/';
            if ( is_dir( $tool_path ) ) {
                $files = glob( $tool_path . 'class-*.php' );
                if ( $files ) {
                    foreach ( $files as $file ) {
                        require_once $file;
                    }
                }
            }
        }

        
        $enabled_plugin_groups = (array) \get_option( 'rankout_connector_enabled_plugin_groups', array() );
        if ( ! empty( $enabled_plugin_groups ) ) {
            $group_dir_map = array(
                'woocommerce'         => 'woocommerce',
                'acf'                 => 'acf',
                'the-events-calendar' => 'events-calendar',
                'buddypress'          => 'buddypress',
                'yoast-seo'           => 'seo/yoast',
                'rank-math'           => 'seo/rank-math',
                'aioseo'              => 'seo/aioseo',
            );
            $dirs_to_load = array();
            foreach ( $enabled_plugin_groups as $group_slug ) {
                if ( isset( $group_dir_map[ $group_slug ] ) ) {
                    $dirs_to_load[ $group_dir_map[ $group_slug ] ] = true;
                }
            }
            foreach ( array_keys( $dirs_to_load ) as $plugin_dir ) {
                $plugin_tool_path = RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/tools/' . $plugin_dir . '/';
                if ( is_dir( $plugin_tool_path ) ) {
                    $plugin_files = glob( $plugin_tool_path . 'class-*.php' );
                    if ( $plugin_files ) {
                        foreach ( $plugin_files as $file ) {
                            require_once $file;
                        }
                    }
                }
            }
        }

        $this->tool_registry->auto_discover();

        
        
        
        
        
        
        
        
        
        
        
        
        
        $registry          = $this->tool_registry;
        $dynamic_registrar = new Tools\Dynamic_Tool_Registrar();
        if ( \doing_action( 'init' ) ) {
            \add_action(
                'wp_loaded',
                static function () use ( $dynamic_registrar, $registry ) {
                    $dynamic_registrar->register_to( $registry );
                }
            );
        } else {
            $dynamic_registrar->register_to( $registry );
        }
    }

    private function register_resources() {
        $files = glob( RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/resources/class-*-resource.php' );
        if ( $files ) {
            foreach ( $files as $file ) {
                require_once $file;
            }
        }
        $this->resource_registry->auto_discover();
    }

    












    public function cleanup_oauth_storage() {
        if ( ! \wp_doing_cron() ) {
            return;
        }
        global $wpdb;
        $codes_table  = $wpdb->prefix . 'rankout_connector_oauth_codes';
        $tokens_table = $wpdb->prefix . 'rankout_connector_oauth_access_tokens';

        $i = 0;
        do {
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned tables; names prefixed by $wpdb->prefix.
            $deleted = $wpdb->query(
                "DELETE FROM `{$codes_table}` WHERE expires_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY) LIMIT 500"
            );
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        } while ( $deleted > 0 && ++$i < self::CLEANUP_MAX_ITERATIONS );

        $i = 0;
        do {
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned tables; names prefixed by $wpdb->prefix.
            $deleted = $wpdb->query(
                "DELETE FROM `{$tokens_table}` WHERE is_active = 0 AND COALESCE(refresh_expires_at, expires_at) < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) LIMIT 500"
            );
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        } while ( $deleted > 0 && ++$i < self::CLEANUP_MAX_ITERATIONS );
    }

    public function cleanup_new_token_meta() {
        if ( ! \wp_doing_cron() ) {
            return;
        }
        global $wpdb;
        $cutoff = time() - DAY_IN_SECONDS;
        $i = 0;
        do {
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- direct DB required for batched usermeta cleanup.
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT umeta_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key LIKE %s LIMIT 500",
                $wpdb->esc_like( '_rankout_connector_new_token_' ) . '%'
            ), ARRAY_A );
            $deleted = 0;
            if ( $rows ) {
                $expired_ids = array();
                foreach ( $rows as $row ) {
                    $val = \maybe_unserialize( $row['meta_value'] );
                    $exp = is_array( $val ) && isset( $val['expires'] ) ? (int) $val['expires'] : 0;
                    if ( $exp < $cutoff ) {
                        $expired_ids[] = (int) $row['umeta_id'];
                    }
                }
                if ( $expired_ids ) {
                    $placeholders = implode( ',', array_fill( 0, count( $expired_ids ), '%d' ) );
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- placeholders generated above; $expired_ids spread as bound args.
                    $deleted = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE umeta_id IN ({$placeholders})", $expired_ids ) );
                }
            }
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        } while ( $deleted > 0 && ++$i < self::CLEANUP_MAX_ITERATIONS );
    }

    




    public static function cleanup_change_log() {
        if ( ! \wp_doing_cron() ) {
            return;
        }
        $retention = (int) \get_option( 'rankout_connector_change_log_retention', 30 );
        if ( $retention <= 0 ) {
            return;
        }
        $cutoff = \gmdate( 'Y-m-d H:i:s', time() - ( $retention * DAY_IN_SECONDS ) );

        if ( ! class_exists( '\\RankOut_Connector\\History\\Change_Log_Repository' ) ) {
            $f = RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/history/class-change-log-repository.php';
            if ( ! file_exists( $f ) ) {
                return;
            }
            require_once $f;
        }
        $repo = new \RankOut_Connector\History\Change_Log_Repository();
        for ( $i = 0; $i < self::CLEANUP_MAX_ITERATIONS; $i++ ) {
            $n = $repo->delete_older_than( $cutoff, 500 );
            if ( $n < 500 ) {
                break;
            }
        }
    }

    public function cleanup_audit_log() {
        if ( ! \wp_doing_cron() ) {
            return;
        }
        global $wpdb;
        $retention = max( 1, (int) \get_option( 'rankout_connector_audit_log_retention', 30 ) );
        
        $i = 0;
        do {
            $table   = \esc_sql( $wpdb->prefix . 'rankout_connector_audit_log' );
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- table name is not user input, direct DB required for batch cleanup.
            $deleted = $wpdb->query( $wpdb->prepare(
                "DELETE FROM `{$table}` WHERE created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY) LIMIT 500",
                $retention
            ) );
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        } while ( $deleted > 0 && ++$i < self::CLEANUP_MAX_ITERATIONS );
    }

    public function on_new_site( $site ) {
        \switch_to_blog( $site->id );
        try {
            Activator::activate();
            
            if ( \apply_filters( 'rankout_connector_oauth_enabled', true ) ) {
                require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-oauth-schema.php';
                OAuth\OAuth_Schema::create_tables();
            }
        } finally {
            \restore_current_blog();
        }
    }

    


    public function maybe_upgrade_oauth() {
        
        
        if ( ! \is_admin() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
            return;
        }
        if ( ! \apply_filters( 'rankout_connector_oauth_enabled', true ) ) {
            return;
        }
        $oauth_schema_file = RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-oauth-schema.php';
        if ( file_exists( $oauth_schema_file ) ) {
            require_once $oauth_schema_file;
            OAuth\OAuth_Schema::maybe_upgrade();
        }
    }

    public function output_post_schema() {
        if ( ! \is_singular() ) {
            return;
        }
        $post_id = \get_the_ID();
        if ( ! $post_id ) {
            return;
        }
        $json = \get_post_meta( $post_id, '_easy_mcp_schema', true );
        if ( empty( $json ) ) {
            return;
        }
        $decoded = json_decode( $json );
        if ( ! $decoded ) {
            return;
        }
        echo '<script type="application/ld+json">' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            . \wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public function get_server() { return $this->server; }
    public function get_token_manager() { return $this->token_manager; }
    public function get_tool_registry() { return $this->tool_registry; }
}
