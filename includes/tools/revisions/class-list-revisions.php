<?php
namespace RankOut_Connector\Tools\Revisions;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class List_Revisions extends Base_Tool {

    public function get_name() {
        return 'wp_list_revisions';
    }

    public function get_description() {
        return 'Lists all saved revisions of a WordPress post. Required: `post_id`. Optional: `per_page` (default 10, max 100), `page`. Returns { revisions: [{ id, author, date, title, excerpt, content_length }], post_id, total, page } — `content_length` is the character count of the revision content (full content is omitted for performance; use `wp_get_revision` with both `post_id` + `revision_id` to read full content). To restore, pass title, content, and excerpt to `wp_update_post`.';
    }

    public function get_category() {
        return 'revisions';
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
                'post_id'  => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the post to retrieve revisions for.',
                ),
                'per_page' => array(
                    'type'        => 'integer',
                    'description' => 'Number of revisions to return (1-100).',
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
            'required'   => array( 'post_id' ),
        );
    }

    public function execute( array $arguments ) {
        $this->validate_required( $arguments, array( 'post_id' ) );

        $post_id   = $this->parse_required_id( $arguments['post_id'], 'post_id' );
        $rest_base = $this->resolve_post_rest_base( $post_id );
        $per_page  = isset( $arguments['per_page'] ) ? min( 100, max( 1, absint( $arguments['per_page'] ) ) ) : 10;
        $page      = isset( $arguments['page'] ) ? absint( $arguments['page'] ) : 1;

        $request = new \WP_REST_Request( 'GET', '/wp/v2/' . $rest_base . '/' . $post_id . '/revisions' );
        $request->set_param( 'per_page', $per_page );
        $request->set_param( 'page', $page );
        $request->set_param( 'context', 'edit' );

        $response = rest_do_request( $request );

        if ( $response->is_error() ) {
            $error = $response->as_error();
            throw new \RuntimeException( $error->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        $revisions = $response->get_data();
        $headers   = $response->get_headers();
        $total     = isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : count( $revisions );

        $result = array();
        foreach ( $revisions as $rev ) {
            $result[] = array(
                'id'             => $rev['id'],
                'author'         => $rev['author'],
                'date'           => $rev['date'],
                'title'          => $rev['title']['raw'] ?? wp_strip_all_tags( $rev['title']['rendered'] ?? '' ),
                'excerpt'        => $rev['excerpt']['raw'] ?? wp_strip_all_tags( $rev['excerpt']['rendered'] ?? '' ),
                'content_length' => mb_strlen( $rev['content']['raw'] ?? wp_strip_all_tags( $rev['content']['rendered'] ?? '' ) ),
            );
        }

        return array(
            'revisions' => $result,
            'total'     => $total,
            'page'      => $page,
            'post_id'   => $post_id,
        );
    }
}
