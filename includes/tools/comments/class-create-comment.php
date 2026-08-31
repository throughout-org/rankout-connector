<?php
namespace RankOut_Connector\Tools\Comments;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Create_Comment extends Base_Tool {

    public function get_name() {
        return 'wp_create_comment';
    }

    public function get_description() {
        return 'Creates a new WordPress comment. Required: `post` (the post/page ID to comment on), `content`. Optional: `author_name`, `author_email`, `status` (approve/hold/spam/trash — default "hold" pending moderation; authenticated users may be auto-approved per site settings). Returns { id, post_id, status, date }. Use `wp_get_comment` to fetch the full comment record after creation.';
    }

    public function get_category() {
        return 'comments';
    }

    public function get_required_capability() {
        return 'moderate_comments';
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
                'post'         => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the post to comment on.',
                ),
                'content'      => array(
                    'type'        => 'string',
                    'description' => 'The content for the comment. HTML is allowed and will be sanitized.',
                ),
                'author_name'  => array(
                    'type'        => 'string',
                    'description' => 'The name of the comment author.',
                ),
                'author_email' => array(
                    'type'        => 'string',
                    'description' => 'The email of the comment author.',
                ),
                'status'       => array(
                    'type'        => 'string',
                    'description' => 'The status for the comment.',
                    'enum'        => array( 'approve', 'hold', 'spam', 'trash' ),
                ),
            ),
            'required'   => array( 'post', 'content' ),
        );
    }

    public function execute( array $arguments ) {
        $this->validate_required( $arguments, array( 'post', 'content' ) );

        $params = array(
            'post'    => absint( $arguments['post'] ),
            'content' => wp_kses_post( $arguments['content'] ),
        );

        if ( ! empty( $arguments['author_name'] ) ) {
            $params['author_name'] = sanitize_text_field( $arguments['author_name'] );
        }

        if ( ! empty( $arguments['author_email'] ) ) {
            $params['author_email'] = sanitize_email( $arguments['author_email'] );
        }

        if ( ! empty( $arguments['status'] ) ) {
            $params['status'] = sanitize_text_field( $arguments['status'] );
        }

        $data = $this->rest_request( 'POST', '/wp/v2/comments', $params );

        return array(
            'id'      => $data['id'],
            'post_id' => $data['post'],
            'status'  => $data['status'],
            'date'    => $data['date'],
        );
    }
}
