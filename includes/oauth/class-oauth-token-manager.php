<?php
namespace RankOut_Connector\OAuth;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}







class OAuth_Token_Manager {

    const TOKEN_PREFIX          = 'wpmcp_oat_';
    const DEFAULT_ACCESS_TTL   = 3600;
    const DEFAULT_REFRESH_TTL  = 2592000; 

    






    const REFRESH_GRACE_SECONDS = 30;

    











    public function mint_access_token( string $client_id, int $wp_user_id, string $resource, string $scope, int $parent_id = 0 ): ?array {
        global $wpdb;

        $table = $wpdb->prefix . 'rankout_connector_oauth_access_tokens';

        $access_ttl  = (int) get_option( 'rankout_connector_oauth_access_token_ttl', self::DEFAULT_ACCESS_TTL );
        $refresh_ttl = (int) get_option( 'rankout_connector_oauth_refresh_token_ttl', self::DEFAULT_REFRESH_TTL );

        
        $raw_access    = self::TOKEN_PREFIX . bin2hex( random_bytes( 32 ) );
        $raw_refresh   = bin2hex( random_bytes( 32 ) );
        $access_hash   = hash( 'sha256', $raw_access );
        $refresh_hash  = hash( 'sha256', $raw_refresh );

        $now               = current_time( 'mysql', true );
        $expires_at        = gmdate( 'Y-m-d H:i:s', time() + $access_ttl );
        $refresh_expires   = gmdate( 'Y-m-d H:i:s', time() + $refresh_ttl );

        $data = array(
            'token_hash'         => $access_hash,
            'refresh_hash'       => $refresh_hash,
            'client_id'          => sanitize_text_field( $client_id ),
            'wp_user_id'         => absint( $wp_user_id ),
            'resource'           => esc_url_raw( $resource ),
            'scope'              => sanitize_text_field( $scope ),
            'expires_at'         => $expires_at,
            'refresh_expires_at' => $refresh_expires,
            'is_active'          => 1,
            'created_at'         => $now,
        );
        $format = array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s' );

        if ( $parent_id > 0 ) {
            $data['refresh_parent_id'] = $parent_id;
            $format[] = '%d';
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned table; writes don't need caching.
        $inserted = $wpdb->insert( $table, $data, $format );
        if ( false === $inserted ) {
            return null;
        }
        $token_id = (int) $wpdb->insert_id;

        return array(
            'access_token'  => $raw_access,
            'refresh_token' => $raw_refresh,
            'expires_in'    => $access_ttl,
            'scope'         => $scope,
            'token_id'      => $token_id,
        );
    }

    





    public function validate_access_token( string $raw_token ) {
        global $wpdb;

        $table = $wpdb->prefix . 'rankout_connector_oauth_access_tokens';

        
        if ( 0 !== strpos( $raw_token, self::TOKEN_PREFIX ) ) {
            return false;
        }

        $token_hash = hash( 'sha256', $raw_token );

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table prefixed by $wpdb->prefix; token lookup must be fresh.
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, wp_user_id, client_id, scope, resource, is_active, expires_at FROM {$table} WHERE token_hash = %s LIMIT 1",
                $token_hash
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        if ( ! $row ) {
            return false;
        }

        if ( ! (int) $row['is_active'] ) {
            return false;
        }

        
        
        if ( strtotime( $row['expires_at'] . ' UTC' ) < time() ) {
            return false;
        }

        
        
        $throttle_key = 'rankout_connector_oat_lu_' . (int) $row['id'];
        if ( ! \get_transient( $throttle_key ) ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table write; throttled via transient.
            $wpdb->update(
                $table,
                array( 'last_used_at' => current_time( 'mysql', true ) ),
                array( 'id' => (int) $row['id'] ),
                array( '%s' ),
                array( '%d' )
            );
            \set_transient( $throttle_key, 1, 60 );
        }

        return $row;
    }

    









    public function refresh( string $raw_refresh_token, string $client_id, string $resource ) {
        global $wpdb;

        $table        = $wpdb->prefix . 'rankout_connector_oauth_access_tokens';
        $refresh_hash = hash( 'sha256', $raw_refresh_token );

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table prefixed by $wpdb->prefix; refresh lookup must be fresh.
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, is_active, client_id, wp_user_id, resource, scope, refresh_expires_at, refresh_parent_id, token_hash FROM {$table} WHERE refresh_hash = %s LIMIT 1",
                $refresh_hash
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        if ( ! $row ) {
            return false;
        }

        
        
        $row['scope'] = implode( ' ', \RankOut_Connector\OAuth\Scope_Map::apply_legacy_scope_upgrades(
            array_filter( explode( ' ', $row['scope'] ) )
        ) );

        
        
        
        
        
        
        
        if ( ! (int) $row['is_active'] ) {
            if ( $row['client_id'] !== $client_id ) {
                return false;
            }
            $chain_id = ! empty( $row['refresh_parent_id'] ) ? (int) $row['refresh_parent_id'] : (int) $row['id'];
            if ( $this->has_recent_successor( $chain_id ) ) {
                $this->log_refresh_event( 'grace_retry', $chain_id, $client_id );
                return $this->mint_access_token(
                    $client_id,
                    (int) $row['wp_user_id'],
                    $row['resource'],
                    $row['scope'],
                    $chain_id
                );
            }
            $this->log_refresh_event( 'reuse_revoked', $chain_id, $client_id );
            $this->revoke_chain( $chain_id );
            return false;
        }

        
        if ( $row['client_id'] !== $client_id ) {
            return false;
        }

        
        
        if ( ! empty( $row['refresh_expires_at'] ) && strtotime( $row['refresh_expires_at'] . ' UTC' ) < time() ) {
            return false;
        }

        
        $parent_id = ! empty( $row['refresh_parent_id'] ) ? (int) $row['refresh_parent_id'] : (int) $row['id'];

        
        
        
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table prefixed by $wpdb->prefix; atomic claim must hit DB.
        $claimed = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET is_active = 0 WHERE id = %d AND is_active = 1",
                (int) $row['id']
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        if ( 1 !== (int) $claimed ) {
            
            
            
            
            
            if ( $this->has_recent_successor( $parent_id ) ) {
                $this->log_refresh_event( 'grace_race', $parent_id, $client_id );
                return $this->mint_access_token(
                    $client_id,
                    (int) $row['wp_user_id'],
                    $row['resource'],
                    $row['scope'],
                    $parent_id
                );
            }
            $this->log_refresh_event( 'reuse_revoked', $parent_id, $client_id );
            $this->revoke_chain( $parent_id );
            return false;
        }

        
        
        $new_pair = $this->mint_access_token(
            $client_id,
            (int) $row['wp_user_id'],
            $row['resource'],
            $row['scope'],
            $parent_id
        );

        if ( null === $new_pair ) {
            return false;
        }

        return $new_pair;
    }

    






    public function revoke_token( string $token_hash, string $client_id = '' ): bool {
        global $wpdb;

        $table = $wpdb->prefix . 'rankout_connector_oauth_access_tokens';

        
        if ( '' !== $client_id ) {
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table prefixed by $wpdb->prefix.
            $stored = $wpdb->get_var(
                $wpdb->prepare( "SELECT client_id FROM {$table} WHERE token_hash = %s LIMIT 1", $token_hash )
            );
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
            if ( null === $stored || $stored !== $client_id ) {
                return false;
            }
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table write.
        $result = $wpdb->update(
            $table,
            array( 'is_active' => 0 ),
            array( 'token_hash' => $token_hash ),
            array( '%d' ),
            array( '%s' )
        );

        
        
        
        
        
        return false !== $result;
    }

    







    public function revoke_by_refresh_hash( string $refresh_hash, string $client_id = '' ): bool {
        global $wpdb;

        $table = $wpdb->prefix . 'rankout_connector_oauth_access_tokens';
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table prefixed by $wpdb->prefix.
        $row   = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, client_id, refresh_parent_id FROM {$table} WHERE refresh_hash = %s LIMIT 1",
                $refresh_hash
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        if ( ! $row ) {
            return false;
        }
        
        if ( '' !== $client_id && $row['client_id'] !== $client_id ) {
            return false;
        }
        $chain_id = ! empty( $row['refresh_parent_id'] ) ? (int) $row['refresh_parent_id'] : (int) $row['id'];
        $this->revoke_chain( $chain_id );
        return true;
    }

    










    private function has_recent_successor( int $chain_id ): bool {
        global $wpdb;

        if ( $chain_id <= 0 ) {
            return false;
        }

        $table  = $wpdb->prefix . 'rankout_connector_oauth_access_tokens';
        $cutoff = gmdate( 'Y-m-d H:i:s', time() - self::REFRESH_GRACE_SECONDS );

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table prefixed by $wpdb->prefix; freshness required.
        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE refresh_parent_id = %d AND is_active = 1 AND created_at >= %s",
                $chain_id,
                $cutoff
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        return 1 === $count;
    }

    









    private function log_refresh_event( string $event, int $chain_id, string $client_id ): void {
        if ( ! \get_option( 'rankout_connector_audit_log_enabled', true ) ) {
            return;
        }
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned audit table.
        $wpdb->insert(
            $wpdb->prefix . 'rankout_connector_audit_log',
            array(
                'token_id'      => 0,
                'tool_name'     => '_oauth_refresh',
                'arguments'     => \wp_json_encode( array(
                    'event'     => $event,
                    'chain_id'  => $chain_id,
                    'client_id' => $client_id,
                ) ),
                'result_status' => $event,
                'ip_address'    => isset( $_SERVER['REMOTE_ADDR'] ) ? \sanitize_text_field( \wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
                'created_at'    => \current_time( 'mysql', true ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    public function revoke_chain( int $refresh_parent_id ): bool {
        if ( $refresh_parent_id <= 0 ) {
            return false;
        }

        global $wpdb;

        $table  = $wpdb->prefix . 'rankout_connector_oauth_access_tokens';
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table prefixed by $wpdb->prefix.
        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET is_active = 0 WHERE refresh_parent_id = %d OR id = %d",
                $refresh_parent_id,
                $refresh_parent_id
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        return false !== $result;
    }
}
