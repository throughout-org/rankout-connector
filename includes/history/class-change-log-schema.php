<?php
namespace RankOut_Connector\History;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Change_Log_Schema {
    const DB_VERSION  = '1.0.0';
    const OPTION_NAME = 'rankout_connector_change_log_db_version';

    public static function maybe_upgrade() {
        if ( \version_compare( (string) \get_option( self::OPTION_NAME, '0' ), self::DB_VERSION, '>=' ) ) {
            return;
        }
        self::create_tables();
    }

    public static function create_tables() {
        global $wpdb;
        $table           = $wpdb->prefix . 'rankout_connector_change_log';
        $charset_collate = method_exists( $wpdb, 'get_charset_collate' ) ? $wpdb->get_charset_collate() : '';
        $sql = "CREATE TABLE {$table} (
            id              bigint(20)   unsigned NOT NULL AUTO_INCREMENT,
            audit_id        bigint(20)   unsigned DEFAULT NULL,
            auth_source     varchar(16)  NOT NULL DEFAULT 'legacy',
            token_id        bigint(20)   unsigned NOT NULL DEFAULT 0,
            oauth_client_id varchar(191) DEFAULT NULL,
            wp_user_id      bigint(20)   unsigned NOT NULL DEFAULT 0,
            tool_name       varchar(255) NOT NULL,
            action          varchar(16)  NOT NULL,
            object_type     varchar(64)  NOT NULL,
            object_id       varchar(191) NOT NULL,
            object_subtype  varchar(64)  DEFAULT NULL,
            before_value    longtext     DEFAULT NULL,
            after_value     longtext     DEFAULT NULL,
            changed_fields  text         DEFAULT NULL,
            revision_id     bigint(20)   unsigned DEFAULT NULL,
            truncated       tinyint(1)   NOT NULL DEFAULT 0,
            ip_address      varchar(45)  DEFAULT NULL,
            created_at      datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY object_lookup (object_type, object_id, created_at),
            KEY tool_name (tool_name, created_at),
            KEY wp_user_id (wp_user_id, created_at),
            KEY audit_id (audit_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        if ( ! function_exists( 'dbDelta' ) ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }
        \dbDelta( $sql );

        
        
        
        
        
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table existence check.
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $exists !== $table ) {
            return;
        }
        \update_option( self::OPTION_NAME, self::DB_VERSION );
    }
}
