<?php
namespace RankOut_Connector;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Deactivator {
    public static function deactivate() {
        global $wpdb;
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_session_%', '_transient_timeout_rankout_connector_session_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on deactivation.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_rate_%', '_transient_timeout_rankout_connector_rate_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on deactivation.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_new_token_%', '_transient_timeout_rankout_connector_new_token_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on deactivation.
        \wp_clear_scheduled_hook( 'rankout_connector_cleanup_audit_log' );
        \wp_clear_scheduled_hook( 'rankout_connector_cleanup_oauth' );
        \wp_clear_scheduled_hook( 'rankout_connector_cleanup_change_log' );
        \flush_rewrite_rules();
    }
}
