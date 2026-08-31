<?php
namespace RankOut_Connector\Tools\ACF;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Update_Fields extends Base_Tool {

    public function get_name() {
        return 'wp_acf_update_fields';
    }

    public function get_description() {
        return 'Updates one or more ACF field values on a post or page. Pass field keys and values as an object in the "fields" parameter (e.g. {"field_abc123": "value"}). Fields must be registered with Show in REST API enabled.';
    }

    public function get_category() {
        return 'acf';
    }

    public function get_required_capability() {
        return 'edit_posts';
    }

    public function get_annotations() {
        return array( 'title' => $this->get_title(), 'readOnlyHint' => false, 'destructiveHint' => false, 'openWorldHint' => false );
    }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'post_id'   => array( 'type' => 'integer', 'description' => 'The ID of the post, page, or CPT item to update ACF fields on.' ),
                'post_type' => array( 'type' => 'string',  'description' => 'REST base of the post type (e.g. "posts", "pages"). Defaults to "posts".', 'default' => 'posts' ),
                'fields'    => array( 'type' => 'object',  'description' => 'Key-value pairs of ACF field keys and their new values. Use field keys (e.g. field_abc123) not field names.' ),
            ),
            'required' => array( 'post_id', 'fields' ),
        );
    }

    public function execute( array $arguments ) {
        if ( ! class_exists( 'ACF' ) ) {
            throw new \RuntimeException( 'Advanced Custom Fields (ACF) is not active on this site. Note: Secure Custom Fields (SCF) uses the same ACF class name so this check covers both.' );
        }
        $this->validate_required( $arguments, array( 'post_id', 'fields' ) );
        $post_id   = $this->parse_required_id( $arguments['post_id'], 'post_id' );
        $post_type = ! empty( $arguments['post_type'] ) ? $this->validate_rest_route_segment( $arguments['post_type'], 'post_type' ) : 'posts';
        $fields    = $this->parse_json_param( $arguments['fields'], 'fields' );
        $data      = $this->rest_request( 'POST', '/wp/v2/' . $post_type . '/' . $post_id, array( 'acf' => $fields ) );
        return array(
            'post_id'    => $post_id,
            'post_type'  => $post_type,
            'acf_fields' => $data['acf'] ?? array(),
        );
    }
}
