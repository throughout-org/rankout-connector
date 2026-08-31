<?php
namespace RankOut_Connector\Tools\Search;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Search extends Base_Tool {

    public function get_name() {
        return 'wp_search';
    }

    public function get_description() {
        return 'Searches across all WordPress content types using a single query. Required: `query`. Optional: `type` (filter by type — "post", "term", or "post-format"; default: all), `subtype` (further filter by post type slug or taxonomy slug), `per_page` (default 10), `page`. Returns array of { id, title, url, type, subtype }. For post-only search with status filtering use `wp_search_posts` instead.';
    }

    public function get_category() {
        return 'search';
    }

    public function get_required_capability() {
        return 'read';
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
                'query'    => array(
                    'type'        => 'string',
                    'description' => 'The search query string.',
                ),
                'type'     => array(
                    'type'        => 'string',
                    'description' => 'Limit results to an object type.',
                    'enum'        => array( 'post', 'term', 'post-format' ),
                ),
                'subtype'  => array(
                    'type'        => 'string',
                    'description' => 'Limit results to a specific subtype (e.g. post, page, category, post_tag, or any custom post type/taxonomy slug).',
                ),
                'per_page' => array(
                    'type'        => 'integer',
                    'description' => 'Number of results per page (1-100).',
                    'default'     => 10,
                    'minimum'     => 1,
                    'maximum'     => 100,
                ),
                'page'     => array(
                    'type'        => 'integer',
                    'description' => 'Page number for pagination.',
                    'default'     => 1,
                ),
            ),
            'required'   => array( 'query' ),
        );
    }

    public function execute( array $arguments ) {
        $this->validate_required( $arguments, array( 'query' ) );

        $query   = sanitize_text_field( $arguments['query'] );
        $request = new \WP_REST_Request( 'GET', '/wp/v2/search' );
        $request->set_param( 'search', $query );
        $request->set_param( 'per_page', isset( $arguments['per_page'] ) ? min( 100, max( 1, absint( $arguments['per_page'] ) ) ) : 10 );
        $request->set_param( 'page', isset( $arguments['page'] ) ? absint( $arguments['page'] ) : 1 );

        if ( ! empty( $arguments['type'] ) ) {
            $request->set_param( 'type', sanitize_text_field( $arguments['type'] ) );
        }

        if ( ! empty( $arguments['subtype'] ) ) {
            $request->set_param( 'subtype', sanitize_text_field( $arguments['subtype'] ) );
        }

        $response = rest_do_request( $request );

        if ( $response->is_error() ) {
            $error = $response->as_error();
            throw new \RuntimeException( $error->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        $items   = $response->get_data();
        $headers = $response->get_headers();
        $total   = isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : count( $items );

        $results = array();
        foreach ( $items as $item ) {
            $results[] = array(
                'id'      => $item['id'],
                'title'   => $item['title'],
                'url'     => $item['url'],
                'type'    => $item['type'],
                'subtype' => isset( $item['subtype'] ) ? $item['subtype'] : '',
            );
        }

        return array(
            'results' => $results,
            'total'   => $total,
            'page'    => isset( $arguments['page'] ) ? absint( $arguments['page'] ) : 1,
            'query'   => $query,
        );
    }
}
