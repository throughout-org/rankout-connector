<?php
namespace RankOut_Connector\Tools\Posts;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class List_Posts extends Base_Tool {

    public function get_name() {
        return 'wp_list_posts';
    }

    public function get_description() {
        return 'Lists WordPress posts. Optional filters: `status` (publish/draft/pending/private/future/trash — default publish; use "any" to get all statuses), `search`, `categories` (array of category IDs), `tags` (array of tag IDs), `author` (user ID), `per_page` (max 100, default 10), `page`, `orderby` (date/id/title/slug/modified/author — default date), `order` (asc/desc). Returns array of { id, title, status, date, modified, slug, link, excerpt, author }. For CPT items use `wp_list_cpt_items`.';
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
                'status'     => array(
                    'type'        => 'string',
                    'description' => 'Post status filter (e.g. publish, draft, pending, private, future).',
                    'enum'        => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash', 'any' ),
                ),
                'per_page'   => array(
                    'type'        => 'integer',
                    'description' => 'Number of posts per page (1-100).',
                    'default'     => 10,
                    'minimum'     => 1,
                    'maximum'     => 100,
                ),
                'page'       => array(
                    'type'        => 'integer',
                    'description' => 'Page number for pagination.',
                    'default'     => 1,
                ),
                'search'     => array(
                    'type'        => 'string',
                    'description' => 'Search query to filter posts.',
                ),
                'categories' => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'integer' ),
                    'description' => 'Array of category IDs to filter by.',
                ),
                'tags'       => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'integer' ),
                    'description' => 'Array of tag IDs to filter by.',
                ),
                'author'     => array(
                    'type'        => 'integer',
                    'description' => 'Author user ID to filter by.',
                ),
                'orderby'    => array(
                    'type'        => 'string',
                    'description' => 'Field to order results by.',
                    'enum'        => array( 'date', 'id', 'title', 'slug', 'modified', 'author' ),
                    'default'     => 'date',
                ),
                'order'      => array(
                    'type'        => 'string',
                    'description' => 'Order direction.',
                    'enum'        => array( 'asc', 'desc' ),
                    'default'     => 'desc',
                ),
            ),
        );
    }

    public function execute( array $arguments ) {
        $params = array();

        if ( ! empty( $arguments['status'] ) ) {
            $params['status'] = $arguments['status'];
        }

        $params['per_page'] = isset( $arguments['per_page'] ) ? min( 100, max( 1, absint( $arguments['per_page'] ) ) ) : 10;
        $params['page']     = isset( $arguments['page'] ) ? absint( $arguments['page'] ) : 1;

        if ( ! empty( $arguments['search'] ) ) {
            $params['search'] = sanitize_text_field( $arguments['search'] );
        }

        if ( ! empty( $arguments['categories'] ) ) {
            $params['categories'] = array_map( 'absint', $this->parse_json_param( $arguments['categories'], 'categories' ) );
        }

        if ( ! empty( $arguments['tags'] ) ) {
            $params['tags'] = array_map( 'absint', $this->parse_json_param( $arguments['tags'], 'tags' ) );
        }

        if ( ! empty( $arguments['author'] ) ) {
            $params['author'] = absint( $arguments['author'] );
        }

        if ( ! empty( $arguments['orderby'] ) ) {
            $params['orderby'] = $arguments['orderby'];
        }

        if ( ! empty( $arguments['order'] ) ) {
            $params['order'] = $arguments['order'];
        }

        $request = new \WP_REST_Request( 'GET', '/wp/v2/posts' );
        foreach ( $params as $key => $value ) {
            $request->set_param( $key, $value );
        }

        $response = rest_do_request( $request );

        if ( $response->is_error() ) {
            $error = $response->as_error();
            
            if ( 'rest_post_invalid_page_number' === $error->get_error_code() ) {
                return array(
                    'posts' => array(),
                    'total' => 0,
                    'page'  => $params['page'],
                );
            }
            throw new \RuntimeException( $error->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        $posts = $response->get_data();
        $headers = $response->get_headers();
        $total   = isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : count( $posts );

        $result = array();
        foreach ( $posts as $post ) {
            $result[] = array(
                'id'             => $post['id'],
                'title'          => wp_strip_all_tags( $post['title']['rendered'] ),
                'status'         => $post['status'],
                'date'           => $post['date'],
                'modified'       => $post['modified'],
                'slug'           => $post['slug'],
                'excerpt'        => wp_strip_all_tags( $post['excerpt']['rendered'] ),
                'author'         => $post['author'],
                'categories'     => $post['categories'],
                'tags'           => $post['tags'],
                'featured_media' => $post['featured_media'],
                'link'           => $post['link'],
            );
        }

        return array(
            'posts' => $result,
            'total' => (int) $total,
            'page'  => $params['page'],
        );
    }
}
