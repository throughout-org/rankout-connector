<?php
namespace RankOut_Connector\Tools\Templates;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class List_Templates extends Base_Tool {

    public function get_name() {
        return 'wp_list_templates';
    }

    public function get_description() {
        return 'Lists all block templates. Requires an active block theme (Full Site Editing).';
    }

    public function get_category() {
        return 'templates';
    }

    public function get_required_capability() {
        return 'edit_theme_options';
    }

    public function get_annotations() {
        return array(
            'title'           => $this->get_title(),
            'readOnlyHint'    => true,
            'destructiveHint' => false,
            'openWorldHint'   => false,
        );
    }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'per_page' => array(
                    'type'        => 'integer',
                    'description' => 'Items per page (1-100).',
                    'default'     => 10,
                    'minimum'     => 1,
                    'maximum'     => 100,
                ),
                'page'     => array(
                    'type'        => 'integer',
                    'description' => 'Page number for pagination.',
                    'default'     => 1,
                ),
                'search'   => array(
                    'type'        => 'string',
                    'description' => 'Search by keyword.',
                ),
            ),
        );
    }

    public function execute( array $arguments ) {
        if ( ! wp_is_block_theme() ) {
            throw new \RuntimeException( 'Templates are not available. This requires an active block theme (Full Site Editing). The current theme is a classic theme.' );
        }

        $request = new \WP_REST_Request( 'GET', '/wp/v2/templates' );
        $request->set_param( 'per_page', isset( $arguments['per_page'] ) ? min( 100, max( 1, absint( $arguments['per_page'] ) ) ) : 10 );
        $request->set_param( 'page', isset( $arguments['page'] ) ? absint( $arguments['page'] ) : 1 );
        $request->set_param( 'context', 'edit' );

        if ( ! empty( $arguments['search'] ) ) {
            $request->set_param( 'search', sanitize_text_field( $arguments['search'] ) );
        }

        $response = rest_do_request( $request );

        if ( $response->is_error() ) {
            $wp_error     = $response->as_error();
            $block_theme_codes = array( 'rest_no_route', 'rest_cannot_manage_templates' );
            if ( in_array( $wp_error->get_error_code(), $block_theme_codes, true ) ) {
                throw new \RuntimeException(
                    'Templates endpoint is not available. This requires an active block theme (Full Site Editing). The current theme appears to be a classic theme.'
                );
            }
            throw new \RuntimeException( $wp_error->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        $templates = $response->get_data();
        $headers   = $response->get_headers();
        $total     = isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : count( $templates );

        $result = array();
        foreach ( $templates as $template ) {
            $result[] = array(
                'id'             => $template['id'],
                'slug'           => $template['slug'],
                'title'          => $template['title']['raw'] ?? wp_strip_all_tags( $template['title']['rendered'] ?? '' ),
                'description'    => $template['description'] ?? '',
                'type'           => $template['type'] ?? '',
                'status'         => $template['status'],
                'has_theme_file' => $template['has_theme_file'] ?? false,
            );
        }

        return array(
            'templates' => $result,
            'total'     => $total,
            'page'      => isset( $arguments['page'] ) ? absint( $arguments['page'] ) : 1,
        );
    }
}
