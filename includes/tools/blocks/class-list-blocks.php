<?php
namespace RankOut_Connector\Tools\Blocks;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class List_Blocks extends Base_Tool {

    public function get_name() {
        return 'wp_list_blocks';
    }

    public function get_description() {
        return 'Lists all reusable blocks (synced patterns / wp_block post type). Optional: `search`, `status` (publish/draft/trash — default publish), `per_page` (default 10, max 100), `page`. Returns { blocks: [{ id, title, content, status, date }], total, page }. Reusable blocks are shared across posts — a single block can be embedded in many posts simultaneously.';
    }

    public function get_category() {
        return 'blocks';
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
                'status'   => array(
                    'type'        => 'string',
                    'description' => 'Filter by status.',
                    'enum'        => array( 'publish', 'draft', 'trash' ),
                    'default'     => 'publish',
                ),
            ),
        );
    }

    public function execute( array $arguments ) {
        $request = new \WP_REST_Request( 'GET', '/wp/v2/blocks' );
        $request->set_param( 'per_page', isset( $arguments['per_page'] ) ? min( 100, max( 1, absint( $arguments['per_page'] ) ) ) : 10 );
        $request->set_param( 'page', isset( $arguments['page'] ) ? absint( $arguments['page'] ) : 1 );
        $request->set_param( 'status', isset( $arguments['status'] ) ? sanitize_text_field( $arguments['status'] ) : 'publish' );
        $request->set_param( 'context', 'edit' );

        if ( ! empty( $arguments['search'] ) ) {
            $request->set_param( 'search', sanitize_text_field( $arguments['search'] ) );
        }

        $response = rest_do_request( $request );

        if ( $response->is_error() ) {
            $error = $response->as_error();
            throw new \RuntimeException( $error->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        $blocks  = $response->get_data();
        $headers = $response->get_headers();
        $total   = isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : count( $blocks );

        $result = array();
        foreach ( $blocks as $block ) {
            $result[] = array(
                'id'      => $block['id'],
                'title'   => $block['title']['raw'] ?? wp_strip_all_tags( $block['title']['rendered'] ?? '' ),
                'content' => $block['content']['raw'] ?? $block['content']['rendered'] ?? '',
                'status'  => $block['status'],
                'date'    => $block['date'],
            );
        }

        return array(
            'blocks' => $result,
            'total'  => $total,
            'page'   => isset( $arguments['page'] ) ? absint( $arguments['page'] ) : 1,
        );
    }
}
