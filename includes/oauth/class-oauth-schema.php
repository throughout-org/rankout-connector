<?php
namespace RankOut_Connector\OAuth;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OAuth_Schema {

    














    const DB_VERSION = '1.0.5';
    const VERSION_OPTION = 'rankout_connector_oauth_db_version';

    




    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $prefix          = $wpdb->prefix . 'rankout_connector_oauth_';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $clients_table = $prefix . 'clients';
        $sql_clients   = "CREATE TABLE {$clients_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id VARCHAR(64) NOT NULL,
            client_name VARCHAR(255) NOT NULL,
            redirect_uris LONGTEXT NOT NULL,
            grant_types VARCHAR(255) NOT NULL,
            response_types VARCHAR(255) NOT NULL,
            scope VARCHAR(255) NOT NULL DEFAULT '',
            software_id VARCHAR(255) DEFAULT NULL,
            software_version VARCHAR(64) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            created_by_ip VARCHAR(45) NOT NULL DEFAULT '',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY  (id),
            UNIQUE KEY client_id (client_id),
            KEY is_active_idx (is_active)
        ) {$charset_collate};";

        $codes_table = $prefix . 'codes';
        $sql_codes   = "CREATE TABLE {$codes_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            code_hash CHAR(64) NOT NULL,
            client_id VARCHAR(64) NOT NULL,
            wp_user_id BIGINT UNSIGNED NOT NULL,
            redirect_uri TEXT NOT NULL,
            code_challenge VARCHAR(128) NOT NULL,
            code_challenge_method VARCHAR(16) NOT NULL DEFAULT 'S256',
            resource TEXT NOT NULL,
            scope TEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME DEFAULT NULL,
            minted_token_id BIGINT UNSIGNED DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY code_hash (code_hash),
            KEY expires_at_idx (expires_at)
        ) {$charset_collate};";

        $tokens_table = $prefix . 'access_tokens';
        $sql_tokens   = "CREATE TABLE {$tokens_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            token_hash CHAR(64) NOT NULL,
            refresh_hash CHAR(64) DEFAULT NULL,
            refresh_parent_id BIGINT UNSIGNED DEFAULT NULL,
            client_id VARCHAR(64) NOT NULL,
            wp_user_id BIGINT UNSIGNED NOT NULL,
            resource TEXT NOT NULL,
            scope TEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            refresh_expires_at DATETIME DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            last_used_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY token_hash (token_hash),
            UNIQUE KEY refresh_hash (refresh_hash),
            KEY client_user_idx (client_id, wp_user_id),
            KEY refresh_parent_idx (refresh_parent_id),
            KEY active_expires_idx (is_active, expires_at)
        ) {$charset_collate};";

        $consents_table = $prefix . 'consents';
        $sql_consents   = "CREATE TABLE {$consents_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            wp_user_id BIGINT UNSIGNED NOT NULL,
            client_id VARCHAR(64) NOT NULL,
            scope TEXT NOT NULL,
            granted_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_client_idx (wp_user_id, client_id)
        ) {$charset_collate};";

        dbDelta( $sql_clients );
        dbDelta( $sql_codes );
        dbDelta( $sql_tokens );
        dbDelta( $sql_consents );

        
        
        
        
        
        $expected = array(
            $clients_table,
            $codes_table,
            $tokens_table,
            $consents_table,
        );
        foreach ( $expected as $table ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table existence check; name is $wpdb->prefix-derived.
            $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
            if ( $exists !== $table ) {
                return;
            }
        }

        update_option( self::VERSION_OPTION, self::DB_VERSION );
        require_once __DIR__ . '/class-client-registry.php';
        Client_Registry::register_rankout_client();
    }

    




    public static function maybe_upgrade() {
        $installed_version = get_option( self::VERSION_OPTION, '0' );

        if ( version_compare( $installed_version, self::DB_VERSION, '<' ) ) {
            self::create_tables();
        } else {
            require_once __DIR__ . '/class-client-registry.php';
            Client_Registry::register_rankout_client();
        }
    }
}
