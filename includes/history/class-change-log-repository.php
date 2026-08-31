<?php
namespace RankOut_Connector\History;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Change_Log_Repository {

    public function table() {
        global $wpdb;
        return $wpdb->prefix . 'rankout_connector_change_log';
    }

    public function insert( array $row ) {
        global $wpdb;
        $defaults = array(
            'audit_id'        => null,
            'auth_source'     => 'legacy',
            'token_id'        => 0,
            'oauth_client_id' => null,
            'wp_user_id'      => 0,
            'tool_name'       => '',
            'action'          => 'update',
            'object_type'     => '',
            'object_id'       => '',
            'object_subtype'  => null,
            'before_value'    => null,
            'after_value'     => null,
            'changed_fields'  => null,
            'revision_id'     => null,
            'truncated'       => 0,
            'ip_address'      => null,
            'created_at'      => \current_time( 'mysql', true ),
        );
        $row = array_merge( $defaults, $row );

        foreach ( array( 'before_value', 'after_value', 'changed_fields' ) as $field ) {
            if ( is_array( $row[ $field ] ) || is_object( $row[ $field ] ) ) {
                $row[ $field ] = \wp_json_encode( $row[ $field ] );
            }
        }

        
        
        
        
        
        $formats = array(
            'audit_id'        => '%d',
            'auth_source'     => '%s',
            'token_id'        => '%d',
            'oauth_client_id' => '%s',
            'wp_user_id'      => '%d',
            'tool_name'       => '%s',
            'action'          => '%s',
            'object_type'     => '%s',
            'object_id'       => '%s',
            'object_subtype'  => '%s',
            'before_value'    => '%s',
            'after_value'     => '%s',
            'changed_fields'  => '%s',
            'revision_id'     => '%d',
            'truncated'       => '%d',
            'ip_address'      => '%s',
            'created_at'      => '%s',
        );
        $format_values = array();
        foreach ( $row as $col => $_ ) {
            $format_values[] = isset( $formats[ $col ] ) ? $formats[ $col ] : '%s';
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table direct insert for change log.
        $wpdb->insert( $this->table(), $row, $format_values );
        return (int) $wpdb->insert_id;
    }

    






    public function update_revision_id( $row_id, $revision_id ) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table back-patch.
        return $wpdb->update(
            $this->table(),
            array( 'revision_id' => (int) $revision_id ),
            array( 'id' => (int) $row_id ),
            array( '%d' ),
            array( '%d' )
        );
    }

    public function find( $id ) {
        global $wpdb;
        $table = $this->table();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table; row read with prepared placeholder.
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A );
    }

    












    public function query( array $filters, $limit = 50, $offset = 0, $columns = null ) {
        global $wpdb;
        $table  = $this->table();
        $cols   = ( is_string( $columns ) && '' !== $columns ) ? $columns : '*';
        $where  = array( '1=1' );
        $params = array();
        foreach ( array( 'object_type', 'object_id', 'tool_name', 'wp_user_id', 'oauth_client_id', 'auth_source', 'action' ) as $f ) {
            if ( isset( $filters[ $f ] ) && '' !== $filters[ $f ] ) {
                $where[]  = "{$f} = %s";
                $params[] = $filters[ $f ];
            }
        }
        if ( isset( $filters['audit_id'] ) && '' !== $filters['audit_id'] ) {
            $where[]  = 'audit_id = %d';
            $params[] = (int) $filters['audit_id'];
        }
        if ( ! empty( $filters['since'] ) ) {
            $where[]  = 'created_at >= %s';
            $params[] = $filters['since'];
        }
        if ( ! empty( $filters['until'] ) ) {
            $where[]  = 'created_at <= %s';
            $params[] = $filters['until'];
        }
        $params[] = (int) $limit;
        $params[] = (int) $offset;
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table; $cols/$table/$where are server-built from constant column lists, all user values bound via prepared placeholders.
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT {$cols} FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d',
                ...$params
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
    }

    public function delete_older_than( $datetime_gmt, $batch = 500 ) {
        global $wpdb;
        $table = $this->table();
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned table; batched delete with prepared placeholders.
        return (int) $wpdb->query(
            $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s LIMIT %d", $datetime_gmt, (int) $batch )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
    }
}
