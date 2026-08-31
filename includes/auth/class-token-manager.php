<?php
namespace RankOut_Connector\Auth;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Token_Manager {

    const TOKEN_PREFIX = 'wpmcp_';

    public function create_token( $name, $wp_user_id, $allowed_tools = array( '*' ), $expires_at = null ) {
        global $wpdb;
        $raw_token   = self::TOKEN_PREFIX . bin2hex( random_bytes( 32 ) );
        $token_hash  = hash( 'sha256', $raw_token );
        $token_pfx   = substr( $raw_token, 0, 14 );
        $table = esc_sql( $wpdb->prefix . 'rankout_connector_tokens' );
        $result = $wpdb->insert( $table, array( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct DB required; table name is plugin-controlled, not user input.
            'name'          => sanitize_text_field( $name ),
            'token_hash'    => $token_hash,
            'token_prefix'  => $token_pfx,
            'allowed_tools' => wp_json_encode( $allowed_tools ),
            'wp_user_id'    => absint( $wp_user_id ),
            'expires_at'    => $expires_at ? $this->normalize_expires_at( sanitize_text_field( $expires_at ) ) : null,
            'is_active'     => 1,
            'created_at'    => current_time( 'mysql', true ),
            'updated_at'    => current_time( 'mysql', true ),
        ), array( '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s' ) );
        if ( false === $result ) {
            return new \WP_Error( 'token_create_failed', __( 'Failed to create token.', 'rankout-connector' ) );
        }
        return array( 'id' => $wpdb->insert_id, 'raw_token' => $raw_token, 'prefix' => $token_pfx );
    }

    public function validate_token( $raw_token ) {
        global $wpdb;
        if ( empty( $raw_token ) || 0 !== strpos( $raw_token, self::TOKEN_PREFIX ) ) {
            return false;
        }
        $token_hash = hash( 'sha256', $raw_token );
        $cache_key  = 'token_' . $token_hash;
        $cached     = wp_cache_get( $cache_key, 'rankout_connector' );
        if ( false !== $cached ) {
            if ( empty( $cached['expires_at'] ) || strtotime( $cached['expires_at'] . ' UTC' ) >= time() ) {
                return $cached;
            }
            return false;
        }
        $table = esc_sql( $wpdb->prefix . 'rankout_connector_tokens' );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- cached above; table name is plugin-controlled, not user input.
        $token = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE token_hash = %s AND is_active = 1", $token_hash ), ARRAY_A );
        if ( ! $token ) {
            return false;
        }
        if ( ! empty( $token['expires_at'] ) && strtotime( $token['expires_at'] . ' UTC' ) < time() ) {
            return false;
        }
        \wp_cache_set( $cache_key, $token, 'rankout_connector', 60 );
        
        
        \wp_cache_set( 'token_id_' . $token['id'], $token, 'rankout_connector', 60 );
        return $token;
    }

    public function update_last_used( $token_id ) {
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 'rankout_connector_tokens' );
        
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct DB required; table name is plugin-controlled, not user input.
        $wpdb->query( $wpdb->prepare(
            "UPDATE `{$table}` SET last_used_at = UTC_TIMESTAMP() WHERE id = %d AND (last_used_at IS NULL OR last_used_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 5 MINUTE))",
            absint( $token_id )
        ) );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    public function get_token_by_id( $token_id ) {
        $cache_key = 'token_id_' . absint( $token_id );
        $cached    = wp_cache_get( $cache_key, 'rankout_connector' );
        if ( false !== $cached ) {
            return $cached;
        }
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 'rankout_connector_tokens' );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- cached above; table name is plugin-controlled, not user input.
        $token = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", absint( $token_id ) ), ARRAY_A );
        
        
        
        
        
        
        if ( null !== $token ) {
            \wp_cache_set( $cache_key, $token, 'rankout_connector', 60 );
        }
        return $token;
    }

    private function invalidate_token_cache( $token_id ) {
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 'rankout_connector_tokens' );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- lightweight lookup for cache invalidation only; table name is plugin-controlled.
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT token_hash FROM `{$table}` WHERE id = %d", absint( $token_id ) ), ARRAY_A );
        if ( $row ) {
            \wp_cache_delete( 'token_' . $row['token_hash'], 'rankout_connector' );
        }
        \wp_cache_delete( 'token_id_' . \absint( $token_id ), 'rankout_connector' );
    }

    public function count_tokens() {
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 'rankout_connector_tokens' );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct DB required; table name is plugin-controlled, not user input.
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP())" );
    }

    public function get_all_tokens( $limit = 200, $offset = 0 ) {
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 'rankout_connector_tokens' );
        
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct DB required; table name is plugin-controlled, not user input.
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` ORDER BY created_at DESC LIMIT %d OFFSET %d", absint( $limit ), absint( $offset ) ), ARRAY_A );
    }

    public function update_token( $token_id, $data ) {
        $this->invalidate_token_cache( $token_id );
        global $wpdb;
        $update  = array( 'updated_at' => current_time( 'mysql', true ) );
        $formats = array( '%s' );
        if ( isset( $data['name'] ) ) { $update['name'] = sanitize_text_field( $data['name'] ); $formats[] = '%s'; }
        if ( isset( $data['allowed_tools'] ) ) { $update['allowed_tools'] = wp_json_encode( $data['allowed_tools'] ); $formats[] = '%s'; }
        if ( isset( $data['wp_user_id'] ) ) { $update['wp_user_id'] = absint( $data['wp_user_id'] ); $formats[] = '%d'; }
        if ( isset( $data['is_active'] ) ) { $update['is_active'] = absint( $data['is_active'] ); $formats[] = '%d'; }
        if ( array_key_exists( 'expires_at', $data ) ) { $update['expires_at'] = $data['expires_at'] ? $this->normalize_expires_at( sanitize_text_field( $data['expires_at'] ) ) : null; $formats[] = '%s'; }
        return $wpdb->update( $wpdb->prefix . 'rankout_connector_tokens', $update, array( 'id' => absint( $token_id ) ), $formats, array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct DB required; table name is plugin-controlled, not user input.
    }

    public function delete_token( $token_id ) {
        $this->invalidate_token_cache( $token_id );
        global $wpdb;
        return $wpdb->delete( $wpdb->prefix . 'rankout_connector_tokens', array( 'id' => absint( $token_id ) ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct DB required; table name is plugin-controlled, not user input.
    }

    public function revoke_token( $token_id ) {
        return $this->update_token( $token_id, array( 'is_active' => 0 ) );
    }

    public function get_allowed_tools( $token_id ) {
        $token = $this->get_token_by_id( $token_id );
        if ( ! $token ) { return array(); }
        $tools = json_decode( $token['allowed_tools'], true );
        return is_array( $tools ) ? $tools : array();
    }

    






    private function normalize_expires_at( $value ) {
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
            return $value . ' 23:59:59';
        }
        return $value;
    }
}
