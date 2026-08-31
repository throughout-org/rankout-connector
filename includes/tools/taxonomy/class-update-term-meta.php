<?php
namespace RankOut_Connector\Tools\Taxonomy;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Update_Term_Meta extends Base_Tool {

    public function get_name() {
        return 'wp_update_term_meta';
    }

    public function get_description() {
        return 'Updates a single term meta key/value. Required: `term_id`, `key`, `value`. Returns { updated: bool, term_id, key }. Uses the taxonomy\'s `edit_terms` capability for authorization. Common SEO plugin keys: Yoast _yoast_wpseo_metadesc, Rank Math rank_math_description, AIOSEO _aioseo_description.';
    }

    public function get_category() {
        return 'taxonomy';
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
                'term_id' => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the term.',
                ),
                'key'     => array(
                    'type'        => 'string',
                    'description' => 'Meta key to update.',
                ),
                'value'   => array(
                    'type'        => array( 'string', 'number', 'boolean' ),
                    'description' => 'Meta value to set.',
                ),
            ),
            'required'   => array( 'term_id', 'key', 'value' ),
        );
    }

    public function execute( array $arguments ) {
        $this->validate_required( $arguments, array( 'term_id', 'key' ) );
        if ( ! array_key_exists( 'value', $arguments ) ) {
            throw new \InvalidArgumentException( 'Missing required parameter: value' );
        }
        $term_id = $this->parse_required_id( $arguments['term_id'], 'term_id' );
        $key     = sanitize_text_field( (string) $arguments['key'] );
        if ( '' === $key ) {
            throw new \InvalidArgumentException( 'Key cannot be empty.' );
        }

        $term = get_term( $term_id );
        if ( ! $term || is_wp_error( $term ) ) {
            throw new \InvalidArgumentException( 'Term not found.' );
        }
        $tax_obj = get_taxonomy( $term->taxonomy );
        if ( ! $tax_obj ) {
            throw new \InvalidArgumentException( 'Invalid taxonomy.' );
        }
        if ( ! current_user_can( $tax_obj->cap->edit_terms ) ) {
            throw new \RuntimeException( sprintf( 'Insufficient capability for taxonomy %s.', $term->taxonomy ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
        if ( is_protected_meta( $key, 'term' ) && ! current_user_can( 'manage_options' ) ) {
            throw new \RuntimeException( 'Protected meta keys require administrator privileges.' );
        }

        
        $blocked_patterns = apply_filters( 'rankout_connector_term_meta_blocked_key_patterns', array() );
        foreach ( $blocked_patterns as $pattern ) {
            if ( fnmatch( $pattern, $key ) ) {
                throw new \RuntimeException( 'This meta key cannot be modified via MCP.' ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            }
        }

        $result = update_term_meta( $term_id, $key, $arguments['value'] );
        if ( is_wp_error( $result ) ) {
            throw new \RuntimeException( $result->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        return array(
            'updated'  => ( false !== $result ),
            'term_id'  => $term_id,
            'taxonomy' => $term->taxonomy,
            'key'      => $key,
        );
    }
}
