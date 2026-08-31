<?php
namespace RankOut_Connector\Admin;

use RankOut_Connector\GSC\GSC_Client;
use RankOut_Connector\GA\GA_Client;
use RankOut_Connector\DFS\DataforSEO_Client;
use RankOut_Connector\Semrush\Semrush_Client;
use RankOut_Connector\Tools\Tool_Registry;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class External_Data_Admin {

    const OPTION_GSC_SITES_CACHE = 'rankout_connector_gsc_sites_cache';
    const OPTION_GA_PROPS_CACHE  = 'rankout_connector_ga_properties_cache';

    




    private static function refresh_gsc_sites_cache(): void {
        try {
            $data = GSC_Client::get( 'https://www.googleapis.com/webmasters/v3/sites' );
            self::write_gsc_sites_cache( $data );
        } catch ( \Throwable $e ) {
            
        }
    }

    



    private static function write_gsc_sites_cache( array $data ): void {
        $sites = array();
        foreach ( $data['siteEntry'] ?? array() as $entry ) {
            $url = $entry['siteUrl'] ?? '';
            if ( '' !== $url ) {
                $sites[] = $url;
            }
        }
        \update_option( self::OPTION_GSC_SITES_CACHE, $sites, false );
        if ( ! empty( $sites ) && '' === \get_option( GSC_Client::OPTION_SITE_URL, '' ) ) {
            \update_option( GSC_Client::OPTION_SITE_URL, $sites[0] );
        }
    }

    




    private static function refresh_ga_properties_cache(): void {
        try {
            $data = GA_Client::get( 'https://analyticsadmin.googleapis.com/v1beta/accountSummaries?pageSize=200' );
            self::write_ga_properties_cache( $data );
        } catch ( \Throwable $e ) {
            
        }
    }

    



    private static function write_ga_properties_cache( array $data ): void {
        $properties = array();
        foreach ( $data['accountSummaries'] ?? array() as $account ) {
            $account_name = $account['displayName'] ?? '';
            foreach ( $account['propertySummaries'] ?? array() as $prop ) {
                $resource = $prop['property'] ?? '';
                $id       = ltrim( str_replace( 'properties/', '', $resource ), '/' );
                if ( '' === $id ) {
                    continue;
                }
                $prop_name    = $prop['displayName'] ?? $resource;
                $properties[] = array(
                    'id'    => $id,
                    'label' => $account_name . ' – ' . $prop_name,
                );
            }
        }
        \update_option( self::OPTION_GA_PROPS_CACHE, $properties, false );
        if ( ! empty( $properties ) && '' === \get_option( GA_Client::OPTION_PROPERTY_ID, '' ) ) {
            \update_option( GA_Client::OPTION_PROPERTY_ID, $properties[0]['id'] );
        }
    }

    








    private static function purge_transients_by_prefix( string $prefix ): void {
        global $wpdb;
        $like_value   = $wpdb->esc_like( '_transient_' . $prefix ) . '%';
        $like_timeout = $wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%';
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $like_value, $like_timeout ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    public function __construct() {
        \add_action( 'admin_post_rankout_connector_save_external_data', array( $this, 'handle_save' ) );
        \add_action( 'admin_post_rankout_connector_remove_gsc_key', array( $this, 'handle_remove_key' ) );
        \add_action( 'admin_post_rankout_connector_remove_ga_key', array( $this, 'handle_remove_ga_key' ) );
        \add_action( 'wp_ajax_rankout_connector_gsc_test', array( $this, 'handle_test_connection' ) );
        \add_action( 'wp_ajax_rankout_connector_ga_test', array( $this, 'handle_test_ga_connection' ) );
        \add_action( 'admin_post_rankout_connector_remove_dfs_key',   array( $this, 'handle_remove_dfs_key' ) );
        \add_action( 'wp_ajax_rankout_connector_dfs_test',            array( $this, 'handle_test_dfs_connection' ) );
        \add_action( 'wp_ajax_rankout_connector_dfs_refresh_balance', array( $this, 'handle_refresh_dfs_balance' ) );
        \add_action( 'admin_post_rankout_connector_clear_ga_cache',   array( $this, 'handle_clear_ga_cache' ) );
        \add_action( 'admin_post_rankout_connector_clear_gsc_cache',  array( $this, 'handle_clear_gsc_cache' ) );
        \add_action( 'admin_post_rankout_connector_remove_semrush_key',     array( $this, 'handle_remove_semrush_key' ) );
        \add_action( 'wp_ajax_rankout_connector_semrush_test',              array( $this, 'handle_test_semrush_connection' ) );
        \add_action( 'wp_ajax_rankout_connector_semrush_refresh_balance',   array( $this, 'handle_refresh_semrush_balance' ) );
    }

    











    public function handle_clear_ga_cache(): void {
        if ( ! \current_user_can( 'manage_options' ) ) {
            \wp_die( 'Unauthorized' );
        }
        \check_admin_referer( 'rankout_connector_clear_ga_cache' );

        self::purge_transients_by_prefix( 'rankout_connector_ga_' );

        
        if ( \get_option( GA_Client::OPTION_JSON, '' ) !== '' ) {
            self::refresh_ga_properties_cache();
        }

        \wp_safe_redirect( \add_query_arg( 'message', 'ga_cache_cleared', \admin_url( 'admin.php?page=rankout-connector-external-data' ) ) );
        exit;
    }

    






    public function handle_clear_gsc_cache(): void {
        if ( ! \current_user_can( 'manage_options' ) ) {
            \wp_die( 'Unauthorized' );
        }
        \check_admin_referer( 'rankout_connector_clear_gsc_cache' );

        self::purge_transients_by_prefix( 'rankout_connector_gsc_' );

        if ( \get_option( GSC_Client::OPTION_JSON, '' ) !== '' ) {
            self::refresh_gsc_sites_cache();
        }

        \wp_safe_redirect( \add_query_arg( 'message', 'gsc_cache_cleared', \admin_url( 'admin.php?page=rankout-connector-external-data' ) ) );
        exit;
    }

    public function handle_remove_key(): void {
        if ( ! \current_user_can( 'manage_options' ) ) {
            \wp_die( 'Unauthorized' );
        }
        \check_admin_referer( 'rankout_connector_remove_gsc_key' );

        \delete_option( GSC_Client::OPTION_JSON );
        \delete_option( GSC_Client::OPTION_SITE_URL );
        \delete_option( self::OPTION_GSC_SITES_CACHE );
        \delete_option( 'rankout_connector_disabled_gsc_tools' );
        self::purge_transients_by_prefix( 'rankout_connector_gsc_' );

        
        $all_gsc_names   = array_keys( self::get_gsc_tools() );
        $global_disabled = (array) \get_option( 'rankout_connector_disabled_tools', array() );
        \update_option( 'rankout_connector_disabled_tools', array_values( array_diff( $global_disabled, $all_gsc_names ) ) );

        \wp_safe_redirect( \add_query_arg( 'message', 'removed', \admin_url( 'admin.php?page=rankout-connector-external-data' ) ) );
        exit;
    }

    public function handle_remove_ga_key(): void {
        if ( ! \current_user_can( 'manage_options' ) ) {
            \wp_die( 'Unauthorized' );
        }
        \check_admin_referer( 'rankout_connector_remove_ga_key' );

        \delete_option( GA_Client::OPTION_JSON );
        \delete_option( GA_Client::OPTION_PROPERTY_ID );
        \delete_option( self::OPTION_GA_PROPS_CACHE );
        \delete_option( 'rankout_connector_disabled_ga_tools' );
        
        
        
        self::purge_transients_by_prefix( 'rankout_connector_ga_' );

        
        $all_ga_names    = array_keys( self::get_ga_tools() );
        $global_disabled = (array) \get_option( 'rankout_connector_disabled_tools', array() );
        \update_option( 'rankout_connector_disabled_tools', array_values( array_diff( $global_disabled, $all_ga_names ) ) );

        \wp_safe_redirect( \add_query_arg( 'message', 'ga_removed', \admin_url( 'admin.php?page=rankout-connector-external-data' ) ) );
        exit;
    }

    public function handle_remove_dfs_key(): void {
        if ( ! \current_user_can( 'manage_options' ) ) {
            \wp_die( 'Unauthorized' );
        }
        \check_admin_referer( 'rankout_connector_remove_dfs_key' );

        \delete_option( DataforSEO_Client::OPTION_LOGIN );
        \delete_option( DataforSEO_Client::OPTION_API_PASSWORD );
        self::purge_transients_by_prefix( DataforSEO_Client::TRANSIENT_BALANCE_PREFIX );
        \delete_option( 'rankout_connector_disabled_dfs_tools' );

        
        $all_dfs_names   = array_keys( self::get_dfs_tools() );
        $global_disabled = (array) \get_option( 'rankout_connector_disabled_tools', array() );
        \update_option( 'rankout_connector_disabled_tools', array_values( array_diff( $global_disabled, $all_dfs_names ) ) );

        \wp_safe_redirect( \add_query_arg( 'message', 'dfs_removed', \admin_url( 'admin.php?page=rankout-connector-external-data' ) ) );
        exit;
    }

    public function handle_remove_semrush_key(): void {
        if ( ! \current_user_can( 'manage_options' ) ) {
            \wp_die( 'Unauthorized' );
        }
        \check_admin_referer( 'rankout_connector_remove_semrush_key' );

        \delete_option( Semrush_Client::OPTION_API_KEY );
        \delete_option( 'rankout_connector_disabled_semrush_tools' );

        $all_semrush_names = array_keys( self::get_semrush_tools() );
        $global_disabled   = (array) \get_option( 'rankout_connector_disabled_tools', array() );
        \update_option( 'rankout_connector_disabled_tools', array_values( array_diff( $global_disabled, $all_semrush_names ) ) );

        \wp_safe_redirect( \add_query_arg( 'message', 'semrush_removed', \admin_url( 'admin.php?page=rankout-connector-external-data' ) ) );
        exit;
    }

    public function handle_test_semrush_connection(): void {
        if ( ! \current_user_can( 'manage_options' ) || ! \check_ajax_referer( 'rankout_connector_semrush_test', 'nonce', false ) ) {
            \wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
        try {
            $r = ( new Semrush_Client() )->test_connection();
            \wp_send_json_success( array( 'message' => 'Connected. Balance: ' . (int) $r['balance'] . ' API units.' ) );
        } catch ( \RuntimeException $e ) {
            \wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    public function handle_refresh_semrush_balance(): void {
        if ( ! \current_user_can( 'manage_options' ) || ! \check_ajax_referer( 'rankout_connector_semrush_refresh_balance', 'nonce', false ) ) {
            \wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
        try {
            $r = ( new Semrush_Client() )->get_balance();
            \wp_send_json_success( array(
                'balance'    => (int) $r['balance'],
                'fetched_at' => $r['fetched_at'],
            ) );
        } catch ( \RuntimeException $e ) {
            \wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    public static function get_semrush_tools(): array {
        static $cached = null;
        if ( null !== $cached ) {
            return $cached;
        }

        $semrush_dir = RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/tools/semrush/';
        if ( is_dir( $semrush_dir ) ) {
            $client_file = RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/semrush/class-semrush-client.php';
            $valid_file  = RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/semrush/class-semrush-validators.php';
            if ( file_exists( $client_file ) ) { require_once $client_file; }
            if ( file_exists( $valid_file ) )  { require_once $valid_file; }
            foreach ( (array) glob( $semrush_dir . 'class-*.php' ) as $file ) {
                require_once $file;
            }
        }

        if ( ! class_exists( '\\RankOut_Connector\\Tools\\Tool_Registry' ) ) {
            $cached = array();
            return $cached;
        }

        $registry = new Tool_Registry();
        $registry->auto_discover();
        $tools       = array();
        $by_category = $registry->get_tools_by_category();
        foreach ( $by_category['semrush'] ?? array() as $def ) {
            $tools[ $def['name'] ] = $def['description'] ?? $def['name'];
        }
        $cached = $tools;
        return $cached;
    }

    public function handle_test_dfs_connection(): void {
        if ( ! \current_user_can( 'manage_options' ) || ! \check_ajax_referer( 'rankout_connector_dfs_test', 'nonce', false ) ) {
            \wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
        try {
            $client  = new DataforSEO_Client();
            $balance = $client->get_balance( true );
            \wp_send_json_success( array( 'message' => 'Connected. Balance: $' . number_format( (float) $balance['balance'], 2 ) . ' USD.' ) );
        } catch ( \RuntimeException $e ) {
            \wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    public function handle_refresh_dfs_balance(): void {
        if ( ! \current_user_can( 'manage_options' ) || ! \check_ajax_referer( 'rankout_connector_dfs_refresh_balance', 'nonce', false ) ) {
            \wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
        try {
            self::purge_transients_by_prefix( DataforSEO_Client::TRANSIENT_BALANCE_PREFIX );
            $client  = new DataforSEO_Client();
            $balance = $client->get_balance( true );
            \wp_send_json_success( array(
                'balance'  => $balance['balance'],
                'total'    => $balance['total'],
                'currency' => 'USD',
            ) );
        } catch ( \RuntimeException $e ) {
            \wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    








    public static function get_gsc_tools(): array {
        static $cached = null;
        if ( null !== $cached ) {
            return $cached;
        }

        $gsc_dir = RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/tools/gsc/';
        if ( is_dir( $gsc_dir ) ) {
            foreach ( (array) glob( $gsc_dir . 'class-*.php' ) as $file ) {
                require_once $file;
            }
        }

        if ( ! class_exists( '\\RankOut_Connector\\Tools\\Tool_Registry' ) ) {
            $cached = array();
            return $cached;
        }

        $registry = new Tool_Registry();
        $registry->auto_discover();
        $tools        = array();
        $by_category  = $registry->get_tools_by_category();
        foreach ( $by_category['gsc'] ?? array() as $def ) {
            $tools[ $def['name'] ] = $def['description'] ?? $def['name'];
        }
        $cached = $tools;
        return $cached;
    }

    









    public static function get_ga_tools(): array {
        static $cached = null;
        if ( null !== $cached ) {
            return $cached;
        }

        $ga_dir = RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/tools/ga/';
        if ( is_dir( $ga_dir ) ) {
            foreach ( (array) glob( $ga_dir . 'class-*.php' ) as $file ) {
                require_once $file;
            }
        }

        if ( ! class_exists( '\\RankOut_Connector\\Tools\\Tool_Registry' ) ) {
            $cached = array();
            return $cached;
        }

        $registry = new Tool_Registry();
        $registry->auto_discover();
        $tools        = array();
        $by_category  = $registry->get_tools_by_category();
        foreach ( $by_category['ga'] ?? array() as $def ) {
            $tools[ $def['name'] ] = $def['description'] ?? $def['name'];
        }
        $cached = $tools;
        return $cached;
    }

    








    public static function get_dfs_tools(): array {
        static $cached = null;
        if ( null !== $cached ) {
            return $cached;
        }

        $dfs_dir = RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/tools/dfs/';
        if ( is_dir( $dfs_dir ) ) {
            
            $client_file = RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/dfs/class-dataforseo-client.php';
            if ( file_exists( $client_file ) ) {
                require_once $client_file;
            }
            foreach ( (array) glob( $dfs_dir . 'class-*.php' ) as $file ) {
                require_once $file;
            }
        }

        if ( ! class_exists( '\\RankOut_Connector\\Tools\\Tool_Registry' ) ) {
            $cached = array();
            return $cached;
        }

        $registry = new Tool_Registry();
        $registry->auto_discover();
        $tools       = array();
        $by_category = $registry->get_tools_by_category();
        foreach ( $by_category['dfs'] ?? array() as $def ) {
            $tools[ $def['name'] ] = $def['description'] ?? $def['name'];
        }
        $cached = $tools;
        return $cached;
    }

    public function render_page(): void {
        $message            = isset( $_GET['message'] ) ? \sanitize_text_field( \wp_unslash( $_GET['message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $site_url           = \get_option( GSC_Client::OPTION_SITE_URL, '' );
        $has_json           = ! empty( \get_option( GSC_Client::OPTION_JSON, '' ) );
        $gsc_disabled_tools = (array) \get_option( 'rankout_connector_disabled_gsc_tools', array() );
        $gsc_tools          = self::get_gsc_tools();
        $gsc_sites          = (array) \get_option( self::OPTION_GSC_SITES_CACHE, array() );
        $ga_property_id     = \get_option( GA_Client::OPTION_PROPERTY_ID, '' );
        $has_ga_json        = ! empty( \get_option( GA_Client::OPTION_JSON, '' ) );
        $ga_disabled_tools  = (array) \get_option( 'rankout_connector_disabled_ga_tools', array() );
        $ga_tools           = self::get_ga_tools();
        $ga_properties      = (array) \get_option( self::OPTION_GA_PROPS_CACHE, array() );
        $has_dfs_credentials = ! empty( \get_option( DataforSEO_Client::OPTION_LOGIN, '' ) )
                            && ! empty( \get_option( DataforSEO_Client::OPTION_API_PASSWORD, '' ) );
        $dfs_tools           = self::get_dfs_tools();
        $dfs_disabled_tools  = (array) \get_option( 'rankout_connector_disabled_dfs_tools', array() );
        $dfs_balance         = null;
        if ( $has_dfs_credentials ) {
            try {
                $dfs_balance = ( new DataforSEO_Client() )->get_balance();
            } catch ( \Throwable $e ) {
                
            }
        }
        
        $dfs_login_raw     = \get_option( DataforSEO_Client::OPTION_LOGIN, '' );
        $dfs_login_display = '';
        if ( $has_dfs_credentials && '' !== $dfs_login_raw ) {
            try {
                $decrypted = DataforSEO_Client::decrypt( $dfs_login_raw );
                if ( false !== $decrypted ) {
                    $dfs_login_display = mb_substr( $decrypted, 0, 3 ) . '***';
                }
            } catch ( \RuntimeException $e ) {
                
            }
        }
        
        
        $has_semrush_credentials = ! empty( \get_option( Semrush_Client::OPTION_API_KEY, '' ) );
        $semrush_tools           = self::get_semrush_tools();
        $semrush_disabled_tools  = (array) \get_option( 'rankout_connector_disabled_semrush_tools', array() );
        $semrush_balance         = null;
        if ( $has_semrush_credentials ) {
            try {
                $semrush_balance = ( new Semrush_Client() )->get_balance();
            } catch ( \Throwable $e ) {
                
            }
        }
        $semrush_api_key_masked = '';
        if ( $has_semrush_credentials ) {
            try {
                $decrypted = Semrush_Client::decrypt( \get_option( Semrush_Client::OPTION_API_KEY, '' ) );
                if ( false !== $decrypted && '' !== $decrypted ) {
                    $semrush_api_key_masked = mb_substr( $decrypted, 0, 3 ) . '***';
                }
            } catch ( \RuntimeException $e ) {
                
            }
        }
        $weak_salts = ! defined( 'SECURE_AUTH_KEY' )
            || ! defined( 'SECURE_AUTH_SALT' )
            || strlen( SECURE_AUTH_KEY . SECURE_AUTH_SALT ) < 64
            || false !== strpos( SECURE_AUTH_KEY . SECURE_AUTH_SALT, 'put your unique phrase here' );
        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/admin/views/external-data.php';
    }

    public function handle_save(): void {
        if ( ! \current_user_can( 'manage_options' ) ) {
            \wp_die( 'Unauthorized' );
        }
        \check_admin_referer( 'rankout_connector_external_data' );

        $redirect_base = \admin_url( 'admin.php?page=rankout-connector-external-data' );

        

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated by GSC_Client::validate_site_url() and sanitize_text_field() applied before persist below.
        $site_url_raw = trim( (string) \wp_unslash( $_POST['gsc_default_site_url'] ?? '' ) );
        if ( '' === $site_url_raw ) {
            \delete_option( GSC_Client::OPTION_SITE_URL );
        } else {
            try {
                
                
                
                $validated = GSC_Client::validate_site_url( $site_url_raw );
                \update_option( GSC_Client::OPTION_SITE_URL, \sanitize_text_field( $validated ) );
            } catch ( \InvalidArgumentException $e ) {
                \wp_safe_redirect( \add_query_arg( 'message', 'gsc_site_url_invalid', $redirect_base ) );
                exit;
            }
        }

        
        
        
        
        
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $json_raw = trim( (string) \wp_unslash( $_POST['gsc_service_account_json'] ?? '' ) );
        if ( ! empty( $json_raw ) ) {
            $decoded = json_decode( $json_raw, true );
            if ( ! is_array( $decoded ) ) {
                \wp_safe_redirect( \add_query_arg( 'message', 'json_invalid', $redirect_base ) );
                exit;
            }
            if ( ( $decoded['type'] ?? '' ) !== 'service_account' ) {
                \wp_safe_redirect( \add_query_arg( 'message', 'json_wrong_type', $redirect_base ) );
                exit;
            }
            foreach ( array( 'private_key', 'client_email', 'token_uri' ) as $field ) {
                if ( empty( $decoded[ $field ] ) ) {
                    \wp_safe_redirect( \add_query_arg( 'message', 'json_missing_field', $redirect_base ) );
                    exit;
                }
            }
            \update_option( GSC_Client::OPTION_JSON, GSC_Client::encrypt( $json_raw ), false );
            
            
            
            self::purge_transients_by_prefix( 'rankout_connector_gsc_' );
            self::refresh_gsc_sites_cache();
        }

        
        if ( \get_option( GSC_Client::OPTION_JSON, '' ) !== '' ) {
            self::refresh_gsc_sites_cache();
        }

        $gsc_tools     = self::get_gsc_tools();
        $all_gsc_names = array_keys( $gsc_tools );
        if ( isset( $_POST['gsc_enabled_tools'] ) ) {
            $checked_gsc = array_map( '\sanitize_key', (array) \wp_unslash( $_POST['gsc_enabled_tools'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized via array_map( 'sanitize_key', ... )
        } elseif ( ! empty( $json_raw ) ) {
            
            $checked_gsc = $all_gsc_names;
        } else {
            $checked_gsc = array();
        }
        $disabled_gsc  = array();
        foreach ( $all_gsc_names as $tool_name ) {
            if ( ! in_array( $tool_name, $checked_gsc, true ) ) {
                $disabled_gsc[] = $tool_name;
            }
        }
        \update_option( 'rankout_connector_disabled_gsc_tools', $disabled_gsc );

        

        
        
        
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated by GA_Client::normalize_property() which throws on malformed input.
        $ga_property_raw = trim( (string) \wp_unslash( $_POST['ga_default_property_id'] ?? '' ) );
        if ( '' === $ga_property_raw ) {
            \delete_option( GA_Client::OPTION_PROPERTY_ID );
        } else {
            try {
                $normalized = GA_Client::normalize_property( $ga_property_raw );
                \update_option( GA_Client::OPTION_PROPERTY_ID, $normalized );
            } catch ( \RuntimeException $e ) {
                \wp_safe_redirect( \add_query_arg( 'message', 'ga_property_invalid', $redirect_base ) );
                exit;
            }
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $ga_json_raw = trim( (string) \wp_unslash( $_POST['ga_service_account_json'] ?? '' ) );
        if ( ! empty( $ga_json_raw ) ) {
            $decoded = json_decode( $ga_json_raw, true );
            if ( ! is_array( $decoded ) ) {
                \wp_safe_redirect( \add_query_arg( 'message', 'ga_json_invalid', $redirect_base ) );
                exit;
            }
            if ( ( $decoded['type'] ?? '' ) !== 'service_account' ) {
                \wp_safe_redirect( \add_query_arg( 'message', 'ga_json_wrong_type', $redirect_base ) );
                exit;
            }
            foreach ( array( 'private_key', 'client_email', 'token_uri' ) as $field ) {
                if ( empty( $decoded[ $field ] ) ) {
                    \wp_safe_redirect( \add_query_arg( 'message', 'ga_json_missing_field', $redirect_base ) );
                    exit;
                }
            }
            \update_option( GA_Client::OPTION_JSON, GA_Client::encrypt( $ga_json_raw ), false );
            self::purge_transients_by_prefix( 'rankout_connector_ga_' );
            self::refresh_ga_properties_cache();
        }

        if ( \get_option( GA_Client::OPTION_JSON, '' ) !== '' ) {
            self::refresh_ga_properties_cache();
        }

        $ga_tools     = self::get_ga_tools();
        $all_ga_names = array_keys( $ga_tools );
        if ( isset( $_POST['ga_enabled_tools'] ) ) {
            $checked_ga = array_map( '\sanitize_key', (array) \wp_unslash( $_POST['ga_enabled_tools'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized via array_map( 'sanitize_key', ... )
        } elseif ( ! empty( $ga_json_raw ) ) {
            
            $checked_ga = $all_ga_names;
        } else {
            $checked_ga = array();
        }
        $disabled_ga  = array();
        foreach ( $all_ga_names as $tool_name ) {
            if ( ! in_array( $tool_name, $checked_ga, true ) ) {
                $disabled_ga[] = $tool_name;
            }
        }
        \update_option( 'rankout_connector_disabled_ga_tools', $disabled_ga );

        

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- trimmed only; sanitize_text_field would mangle high-entropy API password.
        $dfs_login_raw    = trim( (string) \wp_unslash( $_POST['dfs_login'] ?? '' ) );
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $dfs_api_pwd_raw  = trim( (string) \wp_unslash( $_POST['dfs_api_password'] ?? '' ) );

        if ( '' !== $dfs_login_raw || '' !== $dfs_api_pwd_raw ) {
            if ( '' === $dfs_login_raw || '' === $dfs_api_pwd_raw ) {
                \wp_safe_redirect( \add_query_arg( 'message', 'dfs_partial_credentials', $redirect_base ) );
                exit;
            }
            try {
                \update_option( DataforSEO_Client::OPTION_LOGIN, DataforSEO_Client::encrypt( $dfs_login_raw ), false );
                \update_option( DataforSEO_Client::OPTION_API_PASSWORD, DataforSEO_Client::encrypt( $dfs_api_pwd_raw ), false );
                self::purge_transients_by_prefix( DataforSEO_Client::TRANSIENT_BALANCE_PREFIX );
            } catch ( \RuntimeException $e ) {
                \wp_safe_redirect( \add_query_arg( 'message', 'dfs_weak_salts', $redirect_base ) );
                exit;
            }
            try {
                ( new DataforSEO_Client() )->get_balance();
            } catch ( \RuntimeException $e ) {
                \delete_option( DataforSEO_Client::OPTION_LOGIN );
                \delete_option( DataforSEO_Client::OPTION_API_PASSWORD );
                \wp_safe_redirect( \add_query_arg( array( 'message' => 'dfs_invalid_credentials', 'error' => rawurlencode( $e->getMessage() ) ), $redirect_base ) );
                exit;
            }
        }

        $dfs_tools     = self::get_dfs_tools();
        $all_dfs_names = array_keys( $dfs_tools );
        if ( isset( $_POST['dfs_enabled_tools'] ) ) {
            $checked_dfs = array_map( '\sanitize_key', (array) \wp_unslash( $_POST['dfs_enabled_tools'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        } elseif ( '' !== $dfs_login_raw && '' !== $dfs_api_pwd_raw ) {
            $checked_dfs = $all_dfs_names;
        } else {
            $checked_dfs = array();
        }
        $disabled_dfs = array();
        foreach ( $all_dfs_names as $tool_name ) {
            if ( ! in_array( $tool_name, $checked_dfs, true ) ) {
                $disabled_dfs[] = $tool_name;
            }
        }
        \update_option( 'rankout_connector_disabled_dfs_tools', $disabled_dfs );

        

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- trimmed only; sanitize_text_field would mangle high-entropy API key.
        $semrush_key_raw   = trim( (string) \wp_unslash( $_POST['semrush_api_key'] ?? '' ) );
        $semrush_key_saved = false;
        if ( '' !== $semrush_key_raw ) {
            try {
                \update_option( Semrush_Client::OPTION_API_KEY, Semrush_Client::encrypt( $semrush_key_raw ), false );
            } catch ( \RuntimeException $e ) {
                \wp_safe_redirect( \add_query_arg( 'message', 'semrush_weak_salts', $redirect_base ) );
                exit;
            }
            try {
                ( new Semrush_Client() )->get_balance();
                $semrush_key_saved = true;
            } catch ( \RuntimeException $e ) {
                \delete_option( Semrush_Client::OPTION_API_KEY );
                \wp_safe_redirect( \add_query_arg( array( 'message' => 'semrush_invalid_key', 'error' => rawurlencode( $e->getMessage() ) ), $redirect_base ) );
                exit;
            }
        }

        $semrush_tools     = self::get_semrush_tools();
        $all_semrush_names = array_keys( $semrush_tools );
        if ( isset( $_POST['semrush_enabled_tools'] ) ) {
            $checked_semrush = array_map( '\sanitize_key', (array) \wp_unslash( $_POST['semrush_enabled_tools'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        } elseif ( '' !== $semrush_key_raw ) {
            $checked_semrush = $all_semrush_names;
        } else {
            $checked_semrush = array();
        }
        $disabled_semrush = array();
        foreach ( $all_semrush_names as $tool_name ) {
            if ( ! in_array( $tool_name, $checked_semrush, true ) ) {
                $disabled_semrush[] = $tool_name;
            }
        }
        \update_option( 'rankout_connector_disabled_semrush_tools', $disabled_semrush );

        

        $global_disabled = (array) \get_option( 'rankout_connector_disabled_tools', array() );
        $other_disabled  = array_values( array_diff( $global_disabled, $all_gsc_names, $all_ga_names, $all_dfs_names, $all_semrush_names ) );
        \update_option(
            'rankout_connector_disabled_tools',
            array_values( array_unique( array_merge( $other_disabled, $disabled_gsc, $disabled_ga, $disabled_dfs, $disabled_semrush ) ) )
        );

        $message = $semrush_key_saved ? 'semrush_saved' : 'saved';
        \wp_safe_redirect( \add_query_arg( 'message', $message, $redirect_base ) );
        exit;
    }

    public function handle_test_connection(): void {
        if ( ! \current_user_can( 'manage_options' ) || ! \check_ajax_referer( 'rankout_connector_gsc_test', 'nonce', false ) ) {
            \wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
        try {
            $data  = GSC_Client::get( 'https://www.googleapis.com/webmasters/v3/sites', true );
            $count = count( $data['siteEntry'] ?? array() );
            self::write_gsc_sites_cache( $data );
            \wp_send_json_success( array( 'message' => "Connected. Found {$count} propert" . ( 1 === $count ? 'y' : 'ies' ) . '.' ) );
        } catch ( \RuntimeException $e ) {
            \wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    public function handle_test_ga_connection(): void {
        if ( ! \current_user_can( 'manage_options' ) || ! \check_ajax_referer( 'rankout_connector_ga_test', 'nonce', false ) ) {
            \wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
        try {
            $data     = GA_Client::get( 'https://analyticsadmin.googleapis.com/v1beta/accountSummaries?pageSize=200', true );
            $accounts = $data['accountSummaries'] ?? array();
            $props    = 0;
            foreach ( $accounts as $account ) {
                $props += count( $account['propertySummaries'] ?? array() );
            }
            $a_count = count( $accounts );
            self::write_ga_properties_cache( $data );
            \wp_send_json_success( array(
                'message' => "Connected. Found {$a_count} " . ( 1 === $a_count ? 'account' : 'accounts' )
                             . ", {$props} " . ( 1 === $props ? 'property' : 'properties' ) . '.',
            ) );
        } catch ( \RuntimeException $e ) {
            \wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }
}
