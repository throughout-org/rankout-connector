<?php
namespace RankOut_Connector\Tools\ACF;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Get_Fields extends Base_Tool {

    public function get_name() {
        return 'wp_acf_get_fields';
    }

    public function get_description() {
        return 'Gets all ACF (Advanced Custom Fields) field values for a post or page. Returns fields under the "acf" key keyed by field name (e.g. "my_field_name"). Field groups must have "Show in REST API" enabled in ACF settings. To update fields with wp_acf_update_fields, use the field key (e.g. "field_abc123"), not the field name — use the key visible in ACF field group settings. Works with ACF and Secure Custom Fields (SCF).';
    }

    public function get_category() {
        return 'acf';
    }

    public function get_required_capability() {
        return 'edit_posts';
    }

    public function get_annotations() {
        return array( 'title' => $this->get_title(), 'readOnlyHint' => true, 'destructiveHint' => false, 'openWorldHint' => false );
    }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'post_id'   => array( 'type' => 'integer', 'description' => 'The ID of the post, page, or custom post type item to retrieve ACF fields from.' ),
                'post_type' => array( 'type' => 'string',  'description' => 'REST base of the post type (e.g. "posts", "pages", or a CPT slug). Defaults to "posts".', 'default' => 'posts' ),
            ),
            'required' => array( 'post_id' ),
        );
    }

    public function execute( array $arguments ) {
        if ( ! class_exists( 'ACF' ) ) {
            throw new \RuntimeException( 'Advanced Custom Fields (ACF) is not active on this site. Note: Secure Custom Fields (SCF) uses the same ACF class name so this check covers both.' );
        }
        $post_id   = $this->parse_required_id( $arguments['post_id'], 'post_id' );
        $post_type = ! empty( $arguments['post_type'] ) ? $this->validate_rest_route_segment( $arguments['post_type'], 'post_type' ) : 'posts';
        $data      = $this->rest_request( 'GET', '/wp/v2/' . $post_type . '/' . $post_id );
        return array(
            'post_id'    => $post_id,
            'post_type'  => $post_type,
            'acf_fields' => $data['acf'] ?? array(),
        );
    }
}
