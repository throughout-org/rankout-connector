<?php
namespace RankOut_Connector;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Activator {

    // RankOut's own fixed, non-secret public-client id — the one thing
    // this plugin and RankOut's backend must both agree on ahead of time
    // (matches backend/.env's WORDPRESS_CONNECTOR_CLIENT_ID). Seeding it
    // here, at activation, is what makes the connect flow genuinely
    // plug-and-play: RankOut never has to register itself against each
    // client's site individually the way an arbitrary third-party MCP
    // client would via Dynamic Client Registration (Client_Registry
    // above) — it's already a known, pre-authorized client the moment
    // this plugin is active. PKCE (required on every /oauth/token call
    // this client makes) is what protects the code exchange in place of
    // a client_secret, so nothing secret needs to ship in this file.
    const RANKOUT_STATIC_CLIENT_ID = 'rankout-dashboard';

    public static function activate( $network_wide = false ) {
        if ( \is_multisite() && $network_wide ) {

            $sites = \get_sites( array( 'number' => 0, 'fields' => 'ids' ) );
            foreach ( $sites as $blog_id ) {
                \switch_to_blog( $blog_id );
                try {
                    self::create_tables();
                    self::create_oauth_tables();
                    self::create_change_log_tables();
                    self::set_default_options();
                    self::seed_rankout_static_client();
                } finally {
                    \restore_current_blog();
                }
            }
        } else {
            self::create_tables();
            self::create_oauth_tables();
            self::create_change_log_tables();
            self::set_default_options();
            self::seed_rankout_static_client();
        }
        if ( ! \wp_next_scheduled( 'rankout_connector_cleanup_audit_log' ) ) {
            \wp_schedule_event( time(), 'daily', 'rankout_connector_cleanup_audit_log' );
        }
        if ( ! \wp_next_scheduled( 'rankout_connector_cleanup_oauth' ) ) {
            \wp_schedule_event( time(), 'daily', 'rankout_connector_cleanup_oauth' );
        }
        if ( ! \wp_next_scheduled( 'rankout_connector_cleanup_new_token_meta' ) ) {
            \wp_schedule_event( time(), 'daily', 'rankout_connector_cleanup_new_token_meta' );
        }
        if ( ! \wp_next_scheduled( 'rankout_connector_cleanup_change_log' ) ) {
            \wp_schedule_event( time(), 'daily', 'rankout_connector_cleanup_change_log' );
        }
        \flush_rewrite_rules();
    }

    



    public static function maybe_upgrade() {
        if ( \get_option( 'rankout_connector_db_version' ) !== RANKOUT_CONNECTOR_VERSION ) {
            self::create_tables();
        }


        self::maybe_upgrade_oauth_tables();
        self::maybe_upgrade_change_log_tables();
        // Idempotent — re-running on every upgrade (not just first
        // activation) self-heals the row if it's ever missing (a restored
        // backup predating this plugin version, a manually cleared
        // clients table, etc.) without requiring a full deactivate/
        // reactivate cycle.
        self::seed_rankout_static_client();
        if ( ! \wp_next_scheduled( 'rankout_connector_cleanup_audit_log' ) ) {
            \wp_schedule_event( time(), 'daily', 'rankout_connector_cleanup_audit_log' );
        }
        if ( ! \wp_next_scheduled( 'rankout_connector_cleanup_oauth' ) ) {
            \wp_schedule_event( time(), 'daily', 'rankout_connector_cleanup_oauth' );
        }
        if ( ! \wp_next_scheduled( 'rankout_connector_cleanup_new_token_meta' ) ) {
            \wp_schedule_event( time(), 'daily', 'rankout_connector_cleanup_new_token_meta' );
        }
        if ( ! \wp_next_scheduled( 'rankout_connector_cleanup_change_log' ) ) {
            \wp_schedule_event( time(), 'daily', 'rankout_connector_cleanup_change_log' );
        }
    }

    // Upserts RankOut's fixed static OAuth client into this install's own
    // client registry — see RANKOUT_STATIC_CLIENT_ID's doc comment above
    // for why this is what makes the connect flow plug-and-play rather
    // than requiring a per-site Dynamic Client Registration round trip.
    // Deliberately writes straight into the oauth_clients table rather
    // than going through Client_Registry::persist_client() — that method
    // always mints a fresh random client_id and enforces the DCR rate
    // limit/cap, neither of which applies to seeding one fixed, trusted,
    // first-party row at activation time.
    private static function seed_rankout_static_client() {
        $schema_file = RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-oauth-schema.php';
        if ( ! file_exists( $schema_file ) ) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'rankout_connector_oauth_clients';

        // The redirect_uri(s) RankOut's backend's WORDPRESS_CONNECTOR_CALLBACK_URL
        // may point at. A filter, not a hardcoded single URL, because the
        // real production callback domain is an operational detail this
        // plugin's source shouldn't need editing to match — set it once
        // via a small mu-plugin, or leave the default for local dev.
        $redirect_uris = \apply_filters(
            'rankout_connector_static_client_redirect_uris',
            array( 'http://localhost:4000/api/wordpress-connector/callback' )
        );
        if ( ! is_array( $redirect_uris ) || empty( $redirect_uris ) ) {
            return;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table write, one fixed row.
        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$table}
                    (client_id, client_name, redirect_uris, grant_types, response_types, scope, software_id, software_version, created_at, created_by_ip, is_active)
                 VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 1)
                 ON DUPLICATE KEY UPDATE
                    client_name = VALUES(client_name),
                    redirect_uris = VALUES(redirect_uris),
                    grant_types = VALUES(grant_types),
                    response_types = VALUES(response_types),
                    is_active = 1",
                self::RANKOUT_STATIC_CLIENT_ID,
                'RankOut',
                \wp_json_encode( array_values( $redirect_uris ) ),
                \wp_json_encode( array( 'authorization_code', 'refresh_token' ) ),
                \wp_json_encode( array( 'code' ) ),
                '',
                null,
                null,
                \current_time( 'mysql', true ),
                ''
            )
        );
    }

    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tokens_table = $wpdb->prefix . 'rankout_connector_tokens';
        $audit_table  = $wpdb->prefix . 'rankout_connector_audit_log';

        $sql = "CREATE TABLE {$tokens_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            token_hash varchar(64) NOT NULL,
            token_prefix varchar(14) NOT NULL,
            allowed_tools longtext NOT NULL,
            wp_user_id bigint(20) unsigned NOT NULL,
            last_used_at datetime DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY token_hash (token_hash),
            KEY wp_user_id (wp_user_id),
            KEY is_active (is_active)
        ) {$charset_collate};

        CREATE TABLE {$audit_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            token_id bigint(20) unsigned NOT NULL,
            tool_name varchar(255) NOT NULL,
            arguments longtext DEFAULT NULL,
            result_status varchar(20) NOT NULL DEFAULT 'success',
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY token_id (token_id),
            KEY tool_name (tool_name),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        \dbDelta( $sql );
        \update_option( 'rankout_connector_db_version', RANKOUT_CONNECTOR_VERSION );
    }

    



    private static function create_oauth_tables() {
        $schema_file = RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-oauth-schema.php';
        if ( file_exists( $schema_file ) ) {
            require_once $schema_file;
            \RankOut_Connector\OAuth\OAuth_Schema::create_tables();
        }
    }

    



    private static function maybe_upgrade_oauth_tables() {
        $schema_file = RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-oauth-schema.php';
        if ( file_exists( $schema_file ) ) {
            require_once $schema_file;
            \RankOut_Connector\OAuth\OAuth_Schema::maybe_upgrade();
        }
    }

    



    private static function create_change_log_tables() {
        $schema_file = RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/history/class-change-log-schema.php';
        if ( ! file_exists( $schema_file ) ) {
            return;
        }
        require_once $schema_file;
        \RankOut_Connector\History\Change_Log_Schema::create_tables();

        
        
        \add_option( 'rankout_connector_change_log_retention', 30 );
        \add_option( 'rankout_connector_change_log_enabled', true );

        self::ensure_view_all_history_cap();
    }

    









    private static function ensure_view_all_history_cap() {
        $admin_role = \get_role( 'administrator' );
        if ( $admin_role && ! $admin_role->has_cap( 'rankout_connector_view_all_history' ) ) {
            $admin_role->add_cap( 'rankout_connector_view_all_history' );
        }
    }

    




    private static function maybe_upgrade_change_log_tables() {
        $schema_file = RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/history/class-change-log-schema.php';
        if ( ! file_exists( $schema_file ) ) {
            return;
        }
        require_once $schema_file;
        \RankOut_Connector\History\Change_Log_Schema::maybe_upgrade();
        self::ensure_view_all_history_cap();
    }

    private static function set_default_options() {
        $defaults = array(
            'rate_limit_per_minute'  => 60,
            'audit_log_retention'    => 30,
            'ip_whitelist'           => '',
            'enabled_categories'     => array( 'posts', 'pages', 'media', 'taxonomy', 'comments', 'users', 'site', 'menus', 'plugins', 'themes' ),
            'allowed_tool_patterns'  => array(),
            'disabled_tools'         => array( 'wp_delete_post', 'wp_delete_page', 'wp_delete_media', 'wp_delete_comment', 'wp_delete_category', 'wp_delete_tag', 'wp_delete_user', 'wp_delete_block', 'wp_delete_cpt_item', 'wp_delete_menu', 'wp_delete_menu_item', 'wp_delete_revision', 'wp_create_user', 'wp_update_user' ),
            'enabled_abilities'      => array(),
            'enabled_hooks'          => array(),
        );
        foreach ( $defaults as $key => $value ) {
            if ( false === \get_option( 'rankout_connector_' . $key ) ) {
                \update_option( 'rankout_connector_' . $key, $value );
            }
        }
    }
}
