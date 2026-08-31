<?php
namespace RankOut_Connector\Tools\History;

use RankOut_Connector\Tools\Base_Tool;
use RankOut_Connector\History\Change_Log_Repository;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class History_Diff extends Base_Tool {
    public function get_name() { return 'wp_history_diff'; }
    public function get_description() {
        return 'Diff two change-history entries (or one entry against current live state if "id_b" is omitted). Returns per-field before/after for keys that differ.';
    }
    public function get_category() { return 'history'; }
    public function get_required_capability() { return 'read'; }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'id_a' => array( 'type' => 'integer', 'description' => 'Older history entry id' ),
                'id_b' => array( 'type' => 'integer', 'description' => 'Newer history entry id (optional; defaults to live state)' ),
            ),
            'required' => array( 'id_a' ),
        );
    }

    public function execute( array $args ) {
        $this->validate_required( $args, array( 'id_a' ) );
        $repo         = new Change_Log_Repository();
        $can_view_all = \current_user_can( 'rankout_connector_view_all_history' );
        $current_uid  = (int) \get_current_user_id();

        $row_a = $repo->find( (int) $args['id_a'] );
        if ( ! $row_a ) {
            throw new \Exception( 'id_a not found' );
        }
        if ( ! $can_view_all && (int) ( $row_a['wp_user_id'] ?? 0 ) !== $current_uid ) {
            throw new \Exception( 'id_a not found' );
        }
        $a_after = ! empty( $row_a['after_value'] ) ? json_decode( $row_a['after_value'], true ) : array();

        if ( isset( $args['id_b'] ) ) {
            $row_b = $repo->find( (int) $args['id_b'] );
            if ( ! $row_b ) {
                throw new \Exception( 'id_b not found' );
            }
            if ( ! $can_view_all && (int) ( $row_b['wp_user_id'] ?? 0 ) !== $current_uid ) {
                throw new \Exception( 'id_b not found' );
            }
            
            
            
            
            if ( $row_a['object_type'] !== $row_b['object_type']
                || (string) $row_a['object_id'] !== (string) $row_b['object_id']
            ) {
                throw new \Exception( 'id_a and id_b refer to different objects; diff is only meaningful between history entries of the same object_type and object_id' );
            }
            $b_after = ! empty( $row_b['after_value'] ) ? json_decode( $row_b['after_value'], true ) : array();
            $b_label = (int) $row_b['id'];
        } else {
            $b_after = $this->live_state( $row_a['object_type'], $row_a['object_id'], $can_view_all, $row_a['object_subtype'] ?? null );
            
            
            
            
            
            
            
            
            
            if ( is_array( $b_after ) ) {
                $redacted = \RankOut_Connector\History\Change_Redactor::redact( $b_after );
                $b_after  = $redacted['value'];
            }
            $b_label = 'live';
        }

        $diff = array();
        $keys = array_unique( array_merge( array_keys( (array) $a_after ), array_keys( (array) $b_after ) ) );
        foreach ( $keys as $k ) {
            $av = $a_after[ $k ] ?? null;
            $bv = $b_after[ $k ] ?? null;
            if ( $av !== $bv ) {
                $diff[ $k ] = array( 'a' => $av, 'b' => $bv );
            }
        }
        return array(
            'a'           => (int) $row_a['id'],
            'b'           => $b_label,
            'object_type' => $row_a['object_type'],
            'object_id'   => $row_a['object_id'],
            'diff'        => $diff,
        );
    }

    













    private function live_state( $type, $id, $can_view_all = false, $object_subtype = null ) {
        switch ( $type ) {
            case 'post':
                if ( ! function_exists( '\get_post' ) ) {
                    return array();
                }
                $p = \get_post( (int) $id );
                if ( ! $p ) {
                    return array();
                }
                
                
                
                if ( ! $can_view_all && ! \current_user_can( 'read_post', (int) $id ) ) {
                    return array();
                }
                
                
                
                
                
                
                
                return array(
                    'post_title'     => $p->post_title     ?? null,
                    'post_status'    => $p->post_status    ?? null,
                    'post_type'      => $p->post_type      ?? null,
                    'post_excerpt'   => $p->post_excerpt   ?? null,
                    'post_parent'    => $p->post_parent    ?? null,
                    'menu_order'     => $p->menu_order     ?? null,
                    'comment_status' => $p->comment_status ?? null,
                    'ping_status'    => $p->ping_status    ?? null,
                    
                    
                    
                    
                    'post_password'  => '[REDACTED]',
                    'post_name'      => $p->post_name      ?? null,
                    'post_author'    => $p->post_author    ?? null,
                );

            case 'option':
                
                
                
                
                $allowlist = \RankOut_Connector\History\Change_Recorder::option_allowlist();
                if ( ! in_array( (string) $id, $allowlist, true ) ) {
                    return array();
                }
                
                
                
                if ( ! $can_view_all && ! \current_user_can( 'manage_options' ) ) {
                    return array();
                }
                return array( 'value' => function_exists( '\get_option' ) ? \get_option( $id ) : null );

            case 'term':
                if ( ! function_exists( '\get_term' ) ) {
                    return array();
                }
                
                
                $term = $object_subtype
                    ? \get_term( (int) $id, (string) $object_subtype )
                    : \get_term( (int) $id );
                if ( ! $term || \is_wp_error( $term ) ) {
                    return array();
                }
                return array(
                    'term_id'     => isset( $term->term_id )     ? (int) $term->term_id     : null,
                    'name'        => isset( $term->name )        ? (string) $term->name     : null,
                    'slug'        => isset( $term->slug )        ? (string) $term->slug     : null,
                    'description' => isset( $term->description ) ? (string) $term->description : null,
                    'parent'      => isset( $term->parent )      ? (int) $term->parent      : null,
                    'count'       => isset( $term->count )       ? (int) $term->count       : null,
                    'taxonomy'    => isset( $term->taxonomy )    ? (string) $term->taxonomy : null,
                );

            case 'user':
                if ( ! function_exists( '\get_userdata' ) ) {
                    return array();
                }
                
                
                
                
                $can_read = $can_view_all
                    || ( (int) \get_current_user_id() === (int) $id )
                    || \current_user_can( 'list_users' );
                if ( ! $can_read ) {
                    return array();
                }
                $u = \get_userdata( (int) $id );
                if ( ! $u ) {
                    return array();
                }
                
                
                
                
                
                
                
                return array(
                    'ID'           => isset( $u->ID )           ? (int) $u->ID           : null,
                    'user_login'   => isset( $u->user_login )   ? (string) $u->user_login : null,
                    'user_email'   => '[REDACTED]',
                    'display_name' => isset( $u->display_name ) ? (string) $u->display_name : null,
                    'roles'        => isset( $u->roles )        ? $u->roles : array(),
                );

            case 'comment':
                if ( ! function_exists( '\get_comment' ) ) {
                    return array();
                }
                $c = \get_comment( (int) $id );
                if ( ! $c ) {
                    return array();
                }
                
                
                $parent = isset( $c->comment_post_ID ) ? (int) $c->comment_post_ID : 0;
                if ( ! $can_view_all && $parent > 0 && ! \current_user_can( 'read_post', $parent ) ) {
                    return array();
                }
                return array(
                    'comment_ID'       => isset( $c->comment_ID )       ? (string) $c->comment_ID       : null,
                    'comment_post_ID'  => isset( $c->comment_post_ID )  ? (string) $c->comment_post_ID  : null,
                    'comment_author'   => isset( $c->comment_author )   ? (string) $c->comment_author   : null,
                    'comment_content'  => isset( $c->comment_content )  ? (string) $c->comment_content  : null,
                    'comment_approved' => isset( $c->comment_approved ) ? (string) $c->comment_approved : null,
                );

            case 'wc_product':
                if ( ! function_exists( '\wc_get_product' ) ) {
                    return array();
                }
                if ( ! $can_view_all && ! \current_user_can( 'edit_products' ) && ! \current_user_can( 'manage_woocommerce' ) ) {
                    return array();
                }
                $product = \wc_get_product( (int) $id );
                if ( ! $product || ! method_exists( $product, 'get_data' ) ) {
                    return array();
                }
                
                
                
                
                
                
                return json_decode( wp_json_encode( $product->get_data() ), true ) ?: array();

            case 'wc_order':
                if ( ! function_exists( '\wc_get_order' ) ) {
                    return array();
                }
                if ( ! $can_view_all && ! \current_user_can( 'edit_shop_orders' ) && ! \current_user_can( 'manage_woocommerce' ) ) {
                    return array();
                }
                $order = \wc_get_order( (int) $id );
                if ( ! $order || ! method_exists( $order, 'get_data' ) ) {
                    return array();
                }
                
                
                
                return json_decode( wp_json_encode( $order->get_data() ), true ) ?: array();

            case 'bp_activity':
                if ( ! function_exists( '\bp_activity_get_specific' ) ) {
                    return array();
                }
                $resp = \bp_activity_get_specific( array( 'activity_ids' => array( (int) $id ) ) );
                if ( empty( $resp['activities'][0] ) ) {
                    return array();
                }
                $a = $resp['activities'][0];
                return array(
                    'id'        => isset( $a->id )        ? (int) $a->id        : null,
                    'user_id'   => isset( $a->user_id )   ? (int) $a->user_id   : null,
                    'component' => isset( $a->component ) ? (string) $a->component : null,
                    'type'      => isset( $a->type )      ? (string) $a->type   : null,
                    'action'    => isset( $a->action )    ? (string) $a->action : null,
                    'content'   => isset( $a->content )   ? (string) $a->content : null,
                    'item_id'   => isset( $a->item_id )   ? (int) $a->item_id   : null,
                    'hide_sitewide' => isset( $a->hide_sitewide ) ? (int) $a->hide_sitewide : null,
                );

            case 'meta':
                
                
                $parts = explode( ':', (string) $id, 2 );
                if ( count( $parts ) !== 2 || ! function_exists( '\get_post_meta' ) ) {
                    return array();
                }
                $post_id  = (int) $parts[0];
                $meta_key = $parts[1];
                
                
                
                if ( ! $can_view_all && ! \current_user_can( 'edit_post', $post_id ) ) {
                    return array();
                }
                
                
                
                if ( function_exists( '\is_protected_meta' ) && \is_protected_meta( $meta_key, 'post' ) ) {
                    return array();
                }
                return array( 'value' => \get_post_meta( $post_id, $meta_key, true ) );

            default:
                return array();
        }
    }
}
