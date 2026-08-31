<?php
namespace RankOut_Connector\Tools\Comments;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Update_Comment extends Base_Tool {

    public function get_name() {
        return 'wp_update_comment';
    }

    public function get_description() {
        return 'Updates an existing WordPress comment (PATCH semantics). Required: `comment_id`. Optional: `content`, `status` (approve/hold/spam/trash — use "approve" to approve a pending comment, "hold" to return it to moderation, "spam" to mark as spam, "trash" to soft-delete). Returns { id, status }. Use `wp_get_comment` to retrieve the full updated comment. To permanently delete use `wp_delete_comment` with force=true.';
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
                'comment_id' => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the comment to update.',
                ),
                'content'    => array(
                    'type'        => 'string',
                    'description' => 'The new content for the comment. HTML is allowed and will be sanitized.',
                ),
                'status'     => array(
                    'type'        => 'string',
                    'description' => 'The new status for the comment.',
                    'enum'        => array( 'approve', 'hold', 'spam', 'trash' ),
                ),
            ),
            'required'   => array( 'comment_id' ),
        );
    }

    public function execute( array $arguments ) {
        $this->validate_required( $arguments, array( 'comment_id' ) );

        $comment_id = $this->parse_required_id( $arguments['comment_id'], 'comment_id' );
        $params     = array();

        if ( isset( $arguments['content'] ) ) {
            $params['content'] = wp_kses_post( $arguments['content'] );
        }

        if ( ! empty( $arguments['status'] ) ) {
            $params['status'] = sanitize_text_field( $arguments['status'] );
        }

        $data = $this->rest_request( 'POST', '/wp/v2/comments/' . $comment_id, $params );

        return array(
            'id'     => $data['id'],
            'status' => $data['status'],
        );
    }
}
