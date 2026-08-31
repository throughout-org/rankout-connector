<?php
namespace RankOut_Connector\Tools\ACF;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Get_Term_Fields extends Base_Tool {

    public function get_name() {
        return 'wp_acf_get_term_fields';
    }

    public function get_description() {
        return 'Gets ACF field values attached to a taxonomy term (category, tag, or custom taxonomy). Field groups must have "Show in REST API" enabled and location rules targeting the taxonomy.';
    }

    public function get_category() {
        return 'acf';
    }

    public function get_required_capability() {
        return 'manage_categories';
    }

    public function get_annotations() {
        return array( 'title' => $this->get_title(), 'readOnlyHint' => true, 'destructiveHint' => false, 'openWorldHint' => false );
    }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'term_id'  => array( 'type' => 'integer', 'description' => 'The ID of the taxonomy term.' ),
                'taxonomy' => array( 'type' => 'string',  'description' => 'The REST base of the taxonomy (e.g. "categories", "tags", or a custom taxonomy slug).' ),
            ),
            'required' => array( 'term_id', 'taxonomy' ),
        );
    }

    public function execute( array $arguments ) {
        if ( ! class_exists( 'ACF' ) ) {
            throw new \RuntimeException( 'Advanced Custom Fields (ACF) is not active on this site. Note: Secure Custom Fields (SCF) uses the same ACF class name so this check covers both.' );
        }
        $this->validate_required( $arguments, array( 'term_id', 'taxonomy' ) );
        $term_id  = $this->parse_required_id( $arguments['term_id'], 'term_id' );
        $taxonomy = $this->validate_rest_route_segment( $arguments['taxonomy'], 'taxonomy' );
        $data     = $this->rest_request( 'GET', '/wp/v2/' . $taxonomy . '/' . $term_id );
        return array(
            'term_id'    => $term_id,
            'taxonomy'   => $taxonomy,
            'acf_fields' => $data['acf'] ?? array(),
        );
    }
}
