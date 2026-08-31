<?php
namespace RankOut_Connector\Tools\Meta;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Update_Post_Meta extends Base_Tool {

    public function get_name() {
        return 'wp_update_post_meta';
    }

    public function get_description() {
        return 'Updates REST-API-visible meta fields for a post. Only fields registered with show_in_rest can be updated. Pass a JSON object of key-value pairs.';
    }

    public function get_category() {
        return 'meta';
    }

    






    public function get_required_capability() {
        return 'edit_posts';
    }

    public function get_annotations() {
        return array(
            'title'           => $this->get_title(),
            'readOnlyHint'    => false,
            'destructiveHint' => false,
            'openWorldHint'   => false,
        );
    }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'post_id'   => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the post to update meta for.',
                ),
                'meta'      => array(
                    'type'        => 'object',
                    'description' => 'Object of meta key-value pairs to set or update.',
                ),
                'post_type' => array(
                    'type'        => 'string',
                    'description' => 'The REST base for the post type (e.g. posts, pages). Default: posts.',
                    'default'     => 'posts',
                ),
            ),
            'required'   => array( 'post_id', 'meta' ),
        );
    }

    public function execute( array $arguments ) {
        $this->validate_required( $arguments, array( 'post_id', 'meta' ) );

        $meta = $this->parse_json_param( $arguments['meta'], 'meta' );

        $post_id   = $this->parse_required_id( $arguments['post_id'], 'post_id' );
        $post_type = ! empty( $arguments['post_type'] )
            ? $this->validate_rest_route_segment( $arguments['post_type'], 'post_type' )
            : 'posts';

        
        $this->rest_request( 'POST', '/wp/v2/' . $post_type . '/' . $post_id, array(
            'meta' => $meta,
        ) );

        
        $read_data = $this->rest_request( 'GET', '/wp/v2/' . $post_type . '/' . $post_id, array(
            'context' => 'edit',
        ) );

        
        $persisted_meta = isset( $read_data['meta'] ) ? $read_data['meta'] : array();
        $requested_keys = array_keys( $arguments['meta'] );
        $ignored_keys   = array();
        foreach ( $requested_keys as $key ) {
            if ( ! array_key_exists( $key, $persisted_meta ) ) {
                $ignored_keys[] = $key;
            }
        }

        $result = array(
            'post_id'      => $post_id,
            'updated_meta' => ! empty( $persisted_meta ) ? $persisted_meta : new \stdClass(),
        );

        if ( ! empty( $ignored_keys ) ) {
            $result['ignored_keys'] = $ignored_keys;
            $result['notice']       = sprintf(
                'The following meta keys were sent but not persisted (they may not be registered with show_in_rest=true): %s. Meta fields must be registered by the theme or a plugin to be writable via the REST API.',
                implode( ', ', $ignored_keys )
            );
        }

        return $result;
    }
}
