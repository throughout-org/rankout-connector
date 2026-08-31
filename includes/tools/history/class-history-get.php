<?php
namespace RankOut_Connector\Tools\History;

use RankOut_Connector\Tools\Base_Tool;
use RankOut_Connector\History\Change_Log_Repository;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class History_Get extends Base_Tool {
    public function get_name() { return 'wp_history_get'; }
    public function get_description() {
        return 'Get one change-history entry by id, including before/after payloads. For post/page entries with a revision_id, also returns the linked revision post_content.';
    }
    public function get_category() { return 'history'; }
    public function get_required_capability() { return 'read'; }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array( 'id' => array( 'type' => 'integer' ) ),
            'required'   => array( 'id' ),
        );
    }

    public function execute( array $args ) {
        $this->validate_required( $args, array( 'id' ) );
        $row = ( new Change_Log_Repository() )->find( (int) $args['id'] );
        if ( ! $row ) {
            throw new \Exception( 'History entry not found' );
        }

        
        
        
        $can_view_all = \current_user_can( 'rankout_connector_view_all_history' );
        if ( ! $can_view_all && (int) ( $row['wp_user_id'] ?? 0 ) !== (int) \get_current_user_id() ) {
            throw new \Exception( 'History entry not found' );
        }

        $out = array(
            'id'              => (int) $row['id'],
            'audit_id'        => isset( $row['audit_id'] ) ? (int) $row['audit_id'] : null,
            'tool_name'       => $row['tool_name'],
            'action'          => $row['action'],
            'object_type'     => $row['object_type'],
            'object_id'       => $row['object_id'],
            'object_subtype'  => $row['object_subtype'] ?? null,
            'before_value'    => ! empty( $row['before_value'] )   ? json_decode( $row['before_value'],   true ) : null,
            'after_value'     => ! empty( $row['after_value'] )    ? json_decode( $row['after_value'],    true ) : null,
            'changed_fields'  => ! empty( $row['changed_fields'] ) ? json_decode( $row['changed_fields'], true ) : null,
            'revision_id'     => isset( $row['revision_id'] ) ? (int) $row['revision_id'] : null,
            'wp_user_id'      => (int) ( $row['wp_user_id'] ?? 0 ),
            'oauth_client_id' => $row['oauth_client_id'] ?? null,
            'auth_source'     => $row['auth_source'] ?? null,
            'created_at'      => $row['created_at'] ?? null,
            'truncated'       => ! empty( $row['truncated'] ),
        );
        if ( $can_view_all && isset( $row['ip_address'] ) ) {
            $out['ip_address'] = $row['ip_address'];
        }

        if ( $out['revision_id'] && function_exists( '\wp_get_post_revision' ) ) {
            $rev = \wp_get_post_revision( $out['revision_id'] );
            
            
            
            
            
            if ( $rev && isset( $rev->post_content ) ) {
                $parent_id = isset( $rev->post_parent ) ? (int) $rev->post_parent : 0;
                $allowed   = $can_view_all
                    || ( $parent_id > 0 && \current_user_can( 'read_post', $parent_id ) );
                if ( $allowed ) {
                    $out['revision_content'] = $rev->post_content;
                }
            }
        }
        return $out;
    }
}
