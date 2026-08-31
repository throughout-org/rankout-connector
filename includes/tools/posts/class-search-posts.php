<?php
namespace RankOut_Connector\Tools\Posts;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Search_Posts extends Base_Tool {

    public function get_name() {
        return 'wp_search_posts';
    }

    public function get_description() {
        return 'Searches WordPress posts by keyword using the WP REST search API. Required: `query`. Optional: `subtype` (post type slug to search within — default "post"; use "page" to search pages), `per_page` (default 10), `page`. Returns { results: [{ id, title, url, type, subtype }], total, query }. Note: `url` is the permalink, not `link`. For cross-type search including terms use `wp_search` instead.';
    }

    public function get_category() {
        return 'posts';
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
                'query'     => array(
                    'type'        => 'string',
                    'description' => 'The search query string.',
                ),
                'subtype'   => array(
                    'type'        => 'string',
                    'description' => 'Post type subtype to search within (e.g. post, page). Maps to the WP REST API /wp/v2/search "subtype" parameter.',
                    'default'     => 'post',
                ),
                'per_page'  => array(
                    'type'        => 'integer',
                    'description' => 'Number of results per page (1-100).',
                    'default'     => 10,
                    'minimum'     => 1,
                    'maximum'     => 100,
                ),
            ),
            'required'   => array( 'query' ),
        );
    }

    public function execute( array $arguments ) {
        $this->validate_required( $arguments, array( 'query' ) );

        $query  = sanitize_text_field( $arguments['query'] );
        $params = array(
            'search'   => $query,
            'type'     => 'post',
            'per_page' => isset( $arguments['per_page'] ) ? min( 100, max( 1, absint( $arguments['per_page'] ) ) ) : 10,
        );

        if ( ! empty( $arguments['subtype'] ) ) {
            $params['subtype'] = sanitize_text_field( $arguments['subtype'] );
        }

        $request = new \WP_REST_Request( 'GET', '/wp/v2/search' );
        foreach ( $params as $key => $value ) {
            $request->set_param( $key, $value );
        }

        $response = rest_do_request( $request );

        if ( $response->is_error() ) {
            $error = $response->as_error();
            throw new \RuntimeException( $error->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        $items = $response->get_data();
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
            'total'   => (int) $total,
            'query'   => $query,
        );
    }
}
