<?php
namespace RankOut_Connector\History;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Change_Recorder {

    
    private $repo;

    
    private $post_before = array();

    
    private $meta_before = array();

    
    private $term_before = array();

    
    private $comment_before = array();

    
    private $wc_product_before = array();

    
    private $wc_order_before = array();

    







    private $pending_revision_links = array();

    









    private $post_content_hash_before = array();

    




    private static $option_allowlist = array(
        'blogname', 'blogdescription', 'admin_email', 'siteurl', 'home',
        'default_category', 'default_comment_status', 'default_ping_status',
        'posts_per_page', 'date_format', 'time_format', 'start_of_week',
        'timezone_string', 'permalink_structure', 'show_on_front', 'page_on_front', 'page_for_posts',
        'rankout_connector_force_draft_on_create', 'rankout_connector_disabled_tools', 'rankout_connector_allowed_tool_patterns',
    );

    




    public static function option_allowlist() {
        return (array) \apply_filters( 'rankout_connector_change_log_option_allowlist', self::$option_allowlist );
    }

    public function __construct( Change_Log_Repository $repo ) {
        $this->repo = $repo;
    }

    public function register() {
        
        \add_action( 'pre_post_update',     array( $this, 'on_pre_post_update' ),     10, 2 );
        \add_action( 'save_post',           array( $this, 'on_save_post' ),           10, 3 );
        
        
        
        \add_action( 'wp_after_insert_post', array( $this, 'on_wp_after_insert_post' ), 10, 4 );
        \add_action( 'before_delete_post',  array( $this, 'on_before_delete_post' ),  10, 1 );
        \add_action( 'deleted_post',        array( $this, 'on_deleted_post' ),        10, 1 );

        
        \add_action( 'update_post_meta',  array( $this, 'on_update_post_meta' ),  10, 4 );
        \add_action( 'added_post_meta',   array( $this, 'on_added_post_meta' ),   10, 4 );
        \add_action( 'updated_post_meta', array( $this, 'on_updated_post_meta' ), 10, 4 );
        \add_action( 'deleted_post_meta', array( $this, 'on_deleted_post_meta' ), 10, 4 );

        
        \add_action( 'pre_insert_term', array( $this, 'on_pre_insert_term' ), 10, 2 );
        \add_action( 'created_term',    array( $this, 'on_created_term' ), 10, 3 );
        \add_action( 'edit_terms',      array( $this, 'on_edit_terms' ),   10, 2 );
        \add_action( 'edited_term',     array( $this, 'on_edited_term' ),  10, 3 );
        \add_action( 'pre_delete_term', array( $this, 'on_pre_delete_term' ), 10, 2 );
        \add_action( 'delete_term',     array( $this, 'on_delete_term' ),  10, 4 );

        
        \add_action( 'user_register',  array( $this, 'on_user_register' ),  10, 1 );
        \add_action( 'profile_update', array( $this, 'on_profile_update' ), 10, 2 );
        \add_action( 'deleted_user',   array( $this, 'on_deleted_user' ),   10, 1 );

        
        \add_action( 'updated_option', array( $this, 'on_updated_option' ), 10, 3 );
        \add_action( 'added_option',   array( $this, 'on_added_option' ),   10, 2 );
        \add_action( 'deleted_option', array( $this, 'on_deleted_option' ), 10, 1 );

        
        \add_action( 'wp_insert_comment',       array( $this, 'on_wp_insert_comment' ),       10, 2 );
        \add_filter( 'wp_update_comment_data',  array( $this, 'on_update_comment_data' ),      1,  3 );
        \add_action( 'edit_comment',            array( $this, 'on_edit_comment' ),             10, 2 );
        \add_action( 'deleted_comment',         array( $this, 'on_deleted_comment' ),          10, 1 );

        
        if ( class_exists( 'WooCommerce' ) ) {
            \add_action( 'woocommerce_new_product',          array( $this, 'on_woocommerce_new_product' ),          10, 1 );
            \add_action( 'woocommerce_before_product_object_save', array( $this, 'on_woocommerce_before_product_save' ), 10, 1 );
            \add_action( 'woocommerce_update_product',       array( $this, 'on_woocommerce_update_product' ),       10, 2 );
            \add_action( 'woocommerce_delete_product',       array( $this, 'on_woocommerce_delete_product' ),       10, 1 );
            \add_action( 'woocommerce_new_order',            array( $this, 'on_woocommerce_new_order' ),            10, 2 );
            \add_action( 'woocommerce_before_order_object_save', array( $this, 'on_woocommerce_before_order_save' ), 10, 1 );
            \add_action( 'woocommerce_update_order',         array( $this, 'on_woocommerce_update_order' ),         10, 2 );
            \add_action( 'woocommerce_order_status_changed', array( $this, 'on_woocommerce_order_status_changed' ), 10, 4 );
        }

        
        if ( function_exists( 'buddypress' ) ) {
            \add_action( 'bp_activity_add',                array( $this, 'on_bp_activity_add' ),     10, 2 );
            \add_action( 'bp_activity_deleted_activities', array( $this, 'on_bp_activity_deleted' ), 10, 1 );
        }
    }

    

    public function on_pre_post_update( $post_id, $data = array() ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $existing = \get_post( $post_id );
        if ( $existing ) {
            $this->post_before[ $post_id ]              = $this->snapshot_post( $existing );
            $this->post_content_hash_before[ $post_id ] = md5( (string) $existing->post_content );
        }
    }

    public function on_save_post( $post_id, $post, $update ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        if ( (int) $post_id <= 0 ) {
            return;
        }
        if ( isset( $post->post_type ) && 'revision' === $post->post_type ) {
            return;
        }
        $after  = $this->snapshot_post( $post );
        $before = isset( $this->post_before[ $post_id ] ) ? $this->post_before[ $post_id ] : null;
        $action = ( $update && $before ) ? 'update' : 'create';
        $changed = $before ? $this->diff_keys( $before, $after ) : array_keys( $after );

        
        
        
        
        if ( $update && isset( $this->post_content_hash_before[ $post_id ] ) ) {
            $after_hash = md5( (string) ( $post->post_content ?? '' ) );
            if ( $after_hash !== $this->post_content_hash_before[ $post_id ] && ! in_array( 'post_content', $changed, true ) ) {
                $changed[] = 'post_content';
            }
            unset( $this->post_content_hash_before[ $post_id ] );
        }

        if ( 'update' === $action && empty( $changed ) ) {
            unset( $this->post_before[ $post_id ] );
            return;
        }

        $row_id = $this->write_row( array(
            'action'         => $action,
            'object_type'    => 'post',
            'object_id'      => (string) $post_id,
            'object_subtype' => isset( $post->post_type ) ? $post->post_type : null,
            'before_value'   => $before,
            'after_value'    => $after,
            'changed_fields' => $changed,
            
            
            
            
            'revision_id'    => $this->latest_revision_id( $post_id ),
        ) );

        if ( $row_id ) {
            $this->pending_revision_links[ $post_id ] = (int) $row_id;
        }

        unset( $this->post_before[ $post_id ] );
    }

    






    public function on_wp_after_insert_post( $post_id, $post, $update, $post_before ) {
        if ( ! isset( $this->pending_revision_links[ $post_id ] ) ) {
            return;
        }
        $row_id = $this->pending_revision_links[ $post_id ];
        unset( $this->pending_revision_links[ $post_id ] );
        $rev_id = $this->latest_revision_id( $post_id );
        if ( $rev_id ) {
            $this->repo->update_revision_id( $row_id, $rev_id );
        }
    }

    public function on_before_delete_post( $post_id ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $existing = \get_post( $post_id );
        if ( $existing ) {
            $this->post_before[ $post_id ] = $this->snapshot_post( $existing );
        }
    }

    public function on_deleted_post( $post_id ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $before = isset( $this->post_before[ $post_id ] ) ? $this->post_before[ $post_id ] : null;
        $this->write_row( array(
            'action'         => 'delete',
            'object_type'    => 'post',
            'object_id'      => (string) $post_id,
            'object_subtype' => $before ? ( $before['post_type'] ?? null ) : null,
            'before_value'   => $before,
            'after_value'    => null,
            'changed_fields' => $before ? array_keys( $before ) : null,
        ) );
        unset( $this->post_before[ $post_id ] );
    }

    

    public function on_update_post_meta( $meta_id, $post_id, $key, $value ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $this->meta_before[ $post_id . ':' . $key ] = \get_post_meta( $post_id, $key, true );
    }

    public function on_updated_post_meta( $meta_id, $post_id, $key, $value ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $cache_key = $post_id . ':' . $key;
        $before    = $this->meta_before[ $cache_key ] ?? null;
        unset( $this->meta_before[ $cache_key ] );
        $this->write_row( array(
            'action'         => 'update',
            'object_type'    => 'meta',
            'object_id'      => $post_id . ':' . $key,
            'object_subtype' => 'post',
            'before_value'   => array( 'value' => $before ),
            'after_value'    => array( 'value' => $value ),
            'changed_fields' => array( 'value' ),
        ) );
    }

    public function on_added_post_meta( $meta_id, $post_id, $key, $value ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $this->write_row( array(
            'action'         => 'create',
            'object_type'    => 'meta',
            'object_id'      => $post_id . ':' . $key,
            'object_subtype' => 'post',
            'after_value'    => array( 'value' => $value ),
            'changed_fields' => array( 'value' ),
        ) );
    }

    public function on_deleted_post_meta( $meta_ids, $post_id, $key, $value ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $this->write_row( array(
            'action'         => 'delete',
            'object_type'    => 'meta',
            'object_id'      => $post_id . ':' . $key,
            'object_subtype' => 'post',
            'before_value'   => array( 'value' => $value ),
            'changed_fields' => array( 'value' ),
        ) );
    }

    

    





    public function on_pre_insert_term( $term, $taxonomy ) {
        
        
        
        
        
        return $term;
    }

    public function on_created_term( $term_id, $tt_id, $taxonomy ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $term = function_exists( '\get_term' ) ? \get_term( $term_id, $taxonomy, ARRAY_A ) : null;
        $this->write_row( array(
            'action'         => 'create',
            'object_type'    => 'term',
            'object_id'      => (string) $term_id,
            'object_subtype' => $taxonomy,
            'after_value'    => is_array( $term ) ? $term : null,
        ) );
    }

    public function on_edit_terms( $term_id, $taxonomy ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        if ( function_exists( '\get_term' ) ) {
            $this->term_before[ $term_id ] = \get_term( $term_id, $taxonomy, ARRAY_A );
        }
    }

    public function on_edited_term( $term_id, $tt_id, $taxonomy ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $before = $this->term_before[ $term_id ] ?? null;
        $after  = function_exists( '\get_term' ) ? \get_term( $term_id, $taxonomy, ARRAY_A ) : null;
        unset( $this->term_before[ $term_id ] );
        $this->write_row( array(
            'action'         => 'update',
            'object_type'    => 'term',
            'object_id'      => (string) $term_id,
            'object_subtype' => $taxonomy,
            'before_value'   => is_array( $before ) ? $before : null,
            'after_value'    => is_array( $after )  ? $after  : null,
            'changed_fields' => is_array( $before ) && is_array( $after ) ? $this->diff_keys( $before, $after ) : null,
        ) );
    }

    public function on_pre_delete_term( $term_id, $taxonomy ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        if ( function_exists( '\get_term' ) ) {
            $this->term_before[ $term_id ] = \get_term( $term_id, $taxonomy, ARRAY_A );
        }
    }

    public function on_delete_term( $term_id, $tt_id, $taxonomy, $deleted_term ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $before = $this->term_before[ $term_id ] ?? ( is_object( $deleted_term ) ? get_object_vars( $deleted_term ) : null );
        unset( $this->term_before[ $term_id ] );
        $this->write_row( array(
            'action'         => 'delete',
            'object_type'    => 'term',
            'object_id'      => (string) $term_id,
            'object_subtype' => $taxonomy,
            'before_value'   => $before,
        ) );
    }

    

    public function on_user_register( $user_id ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $this->write_row( array(
            'action'      => 'create',
            'object_type' => 'user',
            'object_id'   => (string) $user_id,
            'after_value' => $this->snapshot_user( $user_id ),
        ) );
    }

    public function on_profile_update( $user_id, $old_user_data ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $before = is_object( $old_user_data ) ? array(
            'ID'           => $old_user_data->ID           ?? null,
            'user_login'   => $old_user_data->user_login   ?? null,
            'user_email'   => $old_user_data->user_email   ?? null,
            'display_name' => $old_user_data->display_name ?? null,
        ) : null;
        $after = $this->snapshot_user( $user_id );
        $this->write_row( array(
            'action'         => 'update',
            'object_type'    => 'user',
            'object_id'      => (string) $user_id,
            'before_value'   => $before,
            'after_value'    => $after,
            'changed_fields' => is_array( $before ) && is_array( $after ) ? $this->diff_keys( $before, $after ) : null,
        ) );
    }

    public function on_deleted_user( $user_id ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $this->write_row( array(
            'action'      => 'delete',
            'object_type' => 'user',
            'object_id'   => (string) $user_id,
        ) );
    }

    private function snapshot_user( $user_id ) {
        if ( ! function_exists( '\get_userdata' ) ) {
            return null;
        }
        $u = \get_userdata( $user_id );
        if ( ! $u ) {
            return null;
        }
        return array(
            'ID'           => $u->ID           ?? null,
            'user_login'   => $u->user_login   ?? null,
            'user_email'   => $u->user_email   ?? null,
            'display_name' => $u->display_name ?? null,
            'roles'        => isset( $u->roles ) ? $u->roles : array(),
        );
    }

    

    public function on_added_option( $name, $value ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $name = (string) $name;
        if ( ! in_array( $name, self::option_allowlist(), true ) ) {
            return;
        }
        $this->write_row( array(
            'action'         => 'create',
            'object_type'    => 'option',
            'object_id'      => $name,
            'object_subtype' => $name,
            'after_value'    => array( 'name' => $name, 'value' => $value ),
        ) );
    }

    public function on_updated_option( $name, $old, $new ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $name = (string) $name;
        if ( ! in_array( $name, self::option_allowlist(), true ) ) {
            return;
        }
        $this->write_row( array(
            'action'         => 'update',
            'object_type'    => 'option',
            'object_id'      => $name,
            'object_subtype' => $name,
            'before_value'   => array( 'name' => $name, 'value' => $old ),
            'after_value'    => array( 'name' => $name, 'value' => $new ),
            'changed_fields' => array( 'value' ),
        ) );
    }

    public function on_deleted_option( $name ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $name = (string) $name;
        if ( ! in_array( $name, self::option_allowlist(), true ) ) {
            return;
        }
        $this->write_row( array(
            'action'         => 'delete',
            'object_type'    => 'option',
            'object_id'      => $name,
            'object_subtype' => $name,
        ) );
    }

    

    private function snapshot_comment( $comment ) {
        if ( ! is_object( $comment ) ) {
            return null;
        }
        return array(
            'comment_ID'       => $comment->comment_ID       ?? null,
            'comment_post_ID'  => $comment->comment_post_ID  ?? null,
            'comment_author'   => $comment->comment_author   ?? null,
            'comment_content'  => $comment->comment_content  ?? null,
            'comment_approved' => $comment->comment_approved ?? null,
        );
    }

    public function on_wp_insert_comment( $comment_id, $comment ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $this->write_row( array(
            'action'      => 'create',
            'object_type' => 'comment',
            'object_id'   => (string) $comment_id,
            'after_value' => $this->snapshot_comment( $comment ),
        ) );
    }

    















    public function on_update_comment_data( $data, $comment, $commentarr ) {
        if ( ! Change_Context::is_active() ) {
            return $data;
        }
        $comment_id = isset( $data['comment_ID'] ) ? (int) $data['comment_ID']
                    : ( isset( $comment['comment_ID'] ) ? (int) $comment['comment_ID'] : 0 );
        if ( $comment_id <= 0 ) {
            return $data;
        }
        
        
        $before_obj = is_array( $comment ) ? (object) $comment : null;
        if ( ! isset( $this->comment_before[ $comment_id ] ) ) {
            $this->comment_before[ $comment_id ] = array(
                'snapshot' => $this->snapshot_comment( $before_obj ),
                'context'  => Change_Context::all(),
            );
        }
        return $data;
    }

    public function on_edit_comment( $comment_id, $data = null ) {
        
        
        
        
        
        
        
        
        if ( ! isset( $this->comment_before[ $comment_id ] ) ) {
            return;
        }
        $entry   = $this->comment_before[ $comment_id ];
        $before  = is_array( $entry ) && isset( $entry['snapshot'] ) ? $entry['snapshot'] : $entry;
        $context = is_array( $entry ) && isset( $entry['context'] )  ? $entry['context']  : null;
        unset( $this->comment_before[ $comment_id ] );

        
        if ( function_exists( '\wp_cache_delete' ) ) {
            \wp_cache_delete( $comment_id, 'comment' );
        }
        $after = function_exists( '\get_comment' ) ? \get_comment( $comment_id ) : null;

        $this->write_row( array(
            'action'         => 'update',
            'object_type'    => 'comment',
            'object_id'      => (string) $comment_id,
            'before_value'   => $before,
            'after_value'    => $this->snapshot_comment( $after ),
            'changed_fields' => is_array( $before ) && $after
                ? $this->diff_keys( $before, $this->snapshot_comment( $after ) )
                : null,
        ), $context );
    }

    




    public function flush_comment_update( $comment_id ) {
        
    }

    public function on_deleted_comment( $comment_id ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $this->write_row( array(
            'action'      => 'delete',
            'object_type' => 'comment',
            'object_id'   => (string) $comment_id,
        ) );
    }

    

    public function on_woocommerce_new_product( $product_id ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $this->write_row( array(
            'action'      => 'create',
            'object_type' => 'wc_product',
            'object_id'   => (string) $product_id,
            'after_value' => $this->snapshot_wc_product( $product_id ),
        ) );
    }

    public function on_woocommerce_before_product_save( $product ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
            return;
        }
        $pid = (int) $product->get_id();
        if ( $pid <= 0 ) {
            return;
        }
        
        
        
        $this->wc_product_before[ $pid ] = $this->snapshot_wc_product( $pid );
    }

    public function on_woocommerce_update_product( $product_id, $product = null ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        
        
        
        
        
        
        $snapshot = $this->snapshot_wc_product( $product_id );
        $before   = $this->wc_product_before[ (int) $product_id ] ?? null;
        unset( $this->wc_product_before[ (int) $product_id ] );
        $changed = ( is_array( $before ) && is_array( $snapshot ) )
            ? $this->diff_keys( $before, $snapshot )
            : null;
        $this->write_row( array(
            'action'         => 'update',
            'object_type'    => 'wc_product',
            'object_id'      => (string) $product_id,
            'before_value'   => $before,
            'after_value'    => $snapshot,
            'changed_fields' => $changed,
        ) );
    }

    public function on_woocommerce_delete_product( $product_id ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $this->write_row( array(
            'action'      => 'delete',
            'object_type' => 'wc_product',
            'object_id'   => (string) $product_id,
        ) );
    }

    private function snapshot_wc_product( $product_id ) {
        if ( ! function_exists( '\wc_get_product' ) ) {
            return null;
        }
        $product = \wc_get_product( $product_id );
        if ( ! $product || ! method_exists( $product, 'get_data' ) ) {
            return null;
        }
        return $product->get_data();
    }

    public function on_woocommerce_new_order( $order_id, $order = null ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $this->write_row( array(
            'action'      => 'create',
            'object_type' => 'wc_order',
            'object_id'   => (string) $order_id,
            'after_value' => $this->snapshot_wc_order( $order_id, $order ),
        ) );
    }

    public function on_woocommerce_before_order_save( $order ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        if ( ! is_object( $order ) || ! method_exists( $order, 'get_id' ) ) {
            return;
        }
        $oid = (int) $order->get_id();
        if ( $oid <= 0 ) {
            return;
        }
        $this->wc_order_before[ $oid ] = $this->snapshot_wc_order( $oid );
    }

    public function on_woocommerce_update_order( $order_id, $order = null ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $after   = $this->snapshot_wc_order( $order_id, $order );
        $before  = $this->wc_order_before[ (int) $order_id ] ?? null;
        unset( $this->wc_order_before[ (int) $order_id ] );
        $changed = ( is_array( $before ) && is_array( $after ) )
            ? $this->diff_keys( $before, $after )
            : null;
        $this->write_row( array(
            'action'         => 'update',
            'object_type'    => 'wc_order',
            'object_id'      => (string) $order_id,
            'before_value'   => $before,
            'after_value'    => $after,
            'changed_fields' => $changed,
        ) );
    }

    public function on_woocommerce_order_status_changed( $order_id, $from, $to, $order = null ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $this->write_row( array(
            'action'         => 'update',
            'object_type'    => 'wc_order',
            'object_id'      => (string) $order_id,
            'before_value'   => array( 'status' => $from ),
            'after_value'    => array( 'status' => $to ),
            'changed_fields' => array( 'status' ),
        ) );
    }

    private function snapshot_wc_order( $order_id, $order = null ) {
        
        if ( ! is_object( $order ) && function_exists( '\wc_get_order' ) ) {
            $order = \wc_get_order( $order_id );
        }
        if ( is_object( $order ) && method_exists( $order, 'get_data' ) ) {
            return $order->get_data();
        }
        return null;
    }

    

    public function on_bp_activity_add( $args, $activity_id = null ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $id = $activity_id ?: ( is_array( $args ) && isset( $args['id'] ) ? $args['id'] : null );
        if ( ! $id ) {
            return;
        }
        $this->write_row( array(
            'action'      => 'create',
            'object_type' => 'bp_activity',
            'object_id'   => (string) $id,
            'after_value' => is_array( $args ) ? $args : null,
        ) );
    }

    public function on_bp_activity_deleted( $activity_ids ) {
        if ( ! Change_Context::is_active() ) {
            return;
        }
        $ids   = array_values( array_filter( array_map( 'intval', (array) $activity_ids ) ) );
        $cap   = (int) apply_filters( 'rankout_connector_bp_activity_delete_log_cap', 100 );
        $total = count( $ids );
        $write = $cap > 0 ? array_slice( $ids, 0, $cap ) : $ids;
        foreach ( $write as $id ) {
            $this->write_row( array(
                'action'      => 'delete',
                'object_type' => 'bp_activity',
                'object_id'   => (string) $id,
            ) );
        }
        if ( $cap > 0 && $total > $cap ) {
            
            
            $this->write_row( array(
                'action'         => 'delete',
                'object_type'    => 'bp_activity',
                'object_id'      => 'bulk',
                'after_value'    => array(
                    'total_deleted'  => $total,
                    'logged_count'   => count( $write ),
                    'omitted_count'  => $total - count( $write ),
                ),
                'changed_fields' => array( 'bulk_delete_summary' ),
            ) );
        }
    }

    

    private function snapshot_post( $post ) {
        $fields = array(
            'post_title', 'post_status', 'post_type', 'post_excerpt',
            'post_parent', 'menu_order', 'comment_status', 'ping_status',
            'post_password', 'post_name', 'post_author',
        );
        $out = array();
        foreach ( $fields as $f ) {
            $out[ $f ] = isset( $post->$f ) ? $post->$f : null;
        }
        return $out;
    }

    private function diff_keys( array $before, array $after ) {
        $changed = array();
        foreach ( $after as $k => $v ) {
            $b = isset( $before[ $k ] ) ? $before[ $k ] : null;
            if ( $b !== $v ) {
                $changed[] = $k;
            }
        }
        foreach ( $before as $k => $_v ) {
            if ( ! array_key_exists( $k, $after ) ) {
                $changed[] = $k;
            }
        }
        return array_values( array_unique( $changed ) );
    }

    private function latest_revision_id( $post_id ) {
        if ( ! function_exists( '\wp_get_post_revisions' ) ) {
            return null;
        }
        $rev = \wp_get_post_revisions( $post_id, array( 'numberposts' => 1 ) );
        if ( empty( $rev ) ) {
            return null;
        }
        $first = reset( $rev );
        return isset( $first->ID ) ? (int) $first->ID : null;
    }

    








    private function write_row( array $partial, ?array $context_override = null ) {
        $ctx     = null !== $context_override ? $context_override : Change_Context::all();
        $payload = array_merge( array(
            'audit_id'        => $ctx['audit_id']        ?? null,
            'auth_source'     => $ctx['auth_source']     ?? 'legacy',
            'token_id'        => $ctx['token_id']        ?? 0,
            'oauth_client_id' => $ctx['oauth_client_id'] ?? null,
            'wp_user_id'      => $ctx['wp_user_id']      ?? 0,
            'tool_name'       => $ctx['tool_name']       ?? '',
            'ip_address'      => $ctx['ip_address']      ?? null,
        ), $partial );

        
        
        
        
        
        
        $meta_key_sensitive = false;
        if ( ( $payload['object_type'] ?? '' ) === 'meta' ) {
            $oid   = (string) ( $payload['object_id'] ?? '' );
            $parts = explode( ':', $oid, 2 );
            if ( isset( $parts[1] ) && Change_Redactor::key_is_sensitive( $parts[1] ) ) {
                $meta_key_sensitive = true;
            }
        }

        foreach ( array( 'before_value', 'after_value' ) as $f ) {
            if ( is_array( $payload[ $f ] ?? null ) ) {
                if ( $meta_key_sensitive ) {
                    $payload[ $f ] = array( 'value' => '[REDACTED]' );
                    continue;
                }
                $redacted      = Change_Redactor::redact( $payload[ $f ] );
                $payload[ $f ] = $redacted['value'];
                if ( $redacted['truncated'] ) {
                    $payload['truncated'] = 1;
                }
            }
        }

        return $this->repo->insert( $payload );
    }
}
