<?php






if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;


wp_clear_scheduled_hook( 'rankout_connector_cleanup_audit_log' );
wp_clear_scheduled_hook( 'rankout_connector_cleanup_oauth' );
wp_clear_scheduled_hook( 'rankout_connector_cleanup_new_token_meta' );
wp_clear_scheduled_hook( 'rankout_connector_cleanup_change_log' );


$options = array( // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
    'rankout_connector_db_version',
    'rankout_connector_rate_limit_per_minute',
    'rankout_connector_session_ttl_minutes',  
    'rankout_connector_cors_origins',         
    'rankout_connector_audit_log_retention',
    'rankout_connector_enabled_categories',
    'rankout_connector_ip_whitelist',
    'rankout_connector_disabled_tools',
    'rankout_connector_force_draft_on_create',
    'rankout_connector_max_title_length',
    'rankout_connector_audit_log_enabled',
    'rankout_connector_allowed_tool_patterns',
    'rankout_connector_enabled_abilities',
    'rankout_connector_enabled_hooks',
    'rankout_connector_allowed_plugins',          
    'rankout_connector_admin_language',
    'rankout_connector_enabled_plugin_groups',    
    'rankout_connector_disabled_plugin_tools',   
    'rankout_connector_oauth_db_version',        
    'rankout_connector_oauth_access_token_ttl',  
    'rankout_connector_oauth_refresh_token_ttl', 
    'rankout_connector_oauth_dcr_enabled',       
    'rankout_connector_oauth_max_clients',       
    'rankout_connector_gsc_service_account_json', 
    'rankout_connector_gsc_default_site_url',     
    'rankout_connector_disabled_gsc_tools',       
    'rankout_connector_ga_service_account_json',  
    'rankout_connector_ga_default_property_id',   
    'rankout_connector_disabled_ga_tools',        
    'rankout_connector_gsc_sites_cache',          
    'rankout_connector_ga_properties_cache',      
    'rankout_connector_dfs_login',                
    'rankout_connector_dfs_api_password',         
    'rankout_connector_disabled_dfs_tools',       
    'rankout_connector_semrush_api_key',          
    'rankout_connector_disabled_semrush_tools',   
    'rankout_connector_change_log_db_version',    
    'rankout_connector_change_log_retention',     
    'rankout_connector_change_log_enabled',       
);

if ( is_multisite() ) {
    
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", $wpdb->esc_like( '_rankout_connector_new_token_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $rankout_connector_site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
    foreach ( $rankout_connector_site_ids as $rankout_connector_site_id ) {
        switch_to_blog( $rankout_connector_site_id );
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rankout_connector_tokens" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rankout_connector_audit_log" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rankout_connector_oauth_clients" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rankout_connector_oauth_codes" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rankout_connector_oauth_access_tokens" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rankout_connector_oauth_consents" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rankout_connector_change_log" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
        foreach ( $options as $rankout_connector_option ) {
            delete_option( $rankout_connector_option );
        }
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_session_%', '_transient_timeout_rankout_connector_session_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_rate_%', '_transient_timeout_rankout_connector_rate_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_new_token_%', '_transient_timeout_rankout_connector_new_token_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_dcr_rl_%', '_transient_timeout_rankout_connector_dcr_rl_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_revoke_rl_%', '_transient_timeout_rankout_connector_revoke_rl_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_token_rl_%', '_transient_timeout_rankout_connector_token_rl_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_oat_lu_%', '_transient_timeout_rankout_connector_oat_lu_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_oat_srv_%', '_transient_timeout_rankout_connector_oat_srv_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_auth_fail_%', '_transient_timeout_rankout_connector_auth_fail_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
        
        
        
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_rankout_connector_ga_' ) . '%', $wpdb->esc_like( '_transient_timeout_rankout_connector_ga_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_rankout_connector_gsc_' ) . '%', $wpdb->esc_like( '_transient_timeout_rankout_connector_gsc_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        
        
        
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_rankout_connector_dfs_balance' ) . '%', $wpdb->esc_like( '_transient_timeout_rankout_connector_dfs_balance' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        restore_current_blog();
    }
} else {
    
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rankout_connector_tokens" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rankout_connector_audit_log" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rankout_connector_oauth_clients" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rankout_connector_oauth_codes" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rankout_connector_oauth_access_tokens" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rankout_connector_oauth_consents" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rankout_connector_change_log" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Intentional schema drop on uninstall.

    foreach ( $options as $rankout_connector_option ) {
        delete_option( $rankout_connector_option );
    }

    
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_session_%', '_transient_timeout_rankout_connector_session_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_rate_%', '_transient_timeout_rankout_connector_rate_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_new_token_%', '_transient_timeout_rankout_connector_new_token_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_dcr_rl_%', '_transient_timeout_rankout_connector_dcr_rl_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_revoke_rl_%', '_transient_timeout_rankout_connector_revoke_rl_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_token_rl_%', '_transient_timeout_rankout_connector_token_rl_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_oat_lu_%', '_transient_timeout_rankout_connector_oat_lu_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_oat_srv_%', '_transient_timeout_rankout_connector_oat_srv_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_rankout_connector_auth_fail_%', '_transient_timeout_rankout_connector_auth_fail_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional direct DB call for transient cleanup on uninstall.
    
    
    
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_rankout_connector_ga_' ) . '%', $wpdb->esc_like( '_transient_timeout_rankout_connector_ga_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_rankout_connector_gsc_' ) . '%', $wpdb->esc_like( '_transient_timeout_rankout_connector_gsc_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    
    
    
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_rankout_connector_dfs_balance' ) . '%', $wpdb->esc_like( '_transient_timeout_rankout_connector_dfs_balance' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", $wpdb->esc_like( '_rankout_connector_new_token_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
}
