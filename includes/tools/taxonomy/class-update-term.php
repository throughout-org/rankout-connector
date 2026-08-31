<?php
namespace RankOut_Connector\Tools\Taxonomy;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}






class Update_Term extends Base_Tool {

    public function get_name() {
        return 'wp_update_term';
    }

    public function get_description() {
        return 'Updates a term in any taxonomy. Required: `term_id`, `taxonomy`. Optional updateable fields: `name`, `slug`, `description`, `parent`. Returns { id, name, slug, taxonomy }. Capability resolved dynamically via taxonomy cap->edit_terms.';
    }

    public function get_category() {
        return 'taxonomy';
    }

    public function get_required_capability() {
        
        
        
        return 'read';
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
                'term_id'     => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the term to update.',
                ),
                'taxonomy'    => array(
                    'type'        => 'string',
                    'description' => 'The taxonomy slug.',
                ),
                'name'        => array(
                    'type'        => 'string',
                    'description' => 'New name for the term.',
                ),
                'slug'        => array(
                    'type'        => 'string',
                    'description' => 'New slug for the term.',
                ),
                'description' => array(
                    'type'        => 'string',
                    'description' => 'New description.',
                ),
                'parent'      => array(
                    'type'        => 'integer',
                    'description' => 'New parent term ID.',
                ),
            ),
            'required'   => array( 'term_id', 'taxonomy' ),
        );
    }

    public function execute( array $arguments ) {
        $this->validate_required( $arguments, array( 'term_id', 'taxonomy' ) );
        $term_id  = $this->parse_required_id( $arguments['term_id'], 'term_id' );
        $taxonomy = $this->validate_rest_route_segment( $arguments['taxonomy'], 'taxonomy' );

        $tax_obj = get_taxonomy( $taxonomy );
        if ( ! $tax_obj ) {
            throw new \InvalidArgumentException(
                sprintf( 'Unknown taxonomy: %s', $taxonomy ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }

        $params = array();
        if ( isset( $arguments['name'] ) ) {
            $params['name'] = sanitize_text_field( (string) $arguments['name'] );
            
            
            if ( '' === $params['name'] ) {
                throw new \InvalidArgumentException( 'name cannot be empty.' );
            }
        }
        if ( isset( $arguments['slug'] ) ) {
            $params['slug'] = sanitize_title( (string) $arguments['slug'] );
            if ( '' === $params['slug'] ) {
                throw new \InvalidArgumentException( 'slug cannot be empty.' );
            }
        }
        if ( isset( $arguments['description'] ) ) {
            $params['description'] = wp_kses_post( (string) $arguments['description'] );
        }
        if ( array_key_exists( 'parent', $arguments ) ) {
            
            
            
            $params['parent'] = absint( $arguments['parent'] );
        }
        if ( empty( $params ) ) {
            throw new \InvalidArgumentException( 'At least one updateable field (name, slug, description, parent) must be provided.' );
        }

        
        
        $rest_base = ! empty( $tax_obj->rest_base ) ? $tax_obj->rest_base : $taxonomy;
        $rest_base = $this->validate_rest_route_segment( $rest_base, 'rest_base' );

        $data = $this->rest_request( 'POST', '/wp/v2/' . $rest_base . '/' . $term_id, $params );

        return array(
            'id'          => (int) ( $data['id'] ?? $term_id ),
            'name'        => (string) ( $data['name'] ?? '' ),
            'slug'        => (string) ( $data['slug'] ?? '' ),
            'description' => (string) ( $data['description'] ?? '' ),
            'parent'      => (int) ( $data['parent'] ?? 0 ),
            'count'       => (int) ( $data['count'] ?? 0 ),
            'taxonomy'    => $taxonomy,
        );
    }
}
