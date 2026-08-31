<?php
namespace RankOut_Connector\Tools\Comments;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Delete_Comment extends Base_Tool {

    public function get_name() {
        return 'wp_delete_comment';
    }

    public function get_description() {
        return 'Deletes a WordPress comment by ID. Required: `comment_id`. Optional: `force` (default false) — false moves to trash (status becomes "trash", recoverable via `wp_update_comment` setting status="approve"); true permanently deletes (irreversible). Child replies are also deleted when force=true. Returns { deleted, id }.';
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
            'destructiveHint' => true,
            'openWorldHint'   => false,
        );
    }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'comment_id' => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the comment to delete.',
                ),
                'force'      => array(
                    'type'        => 'boolean',
                    'description' => 'Whether to bypass the trash and force deletion. Default is false.',
                    'default'     => false,
                ),
            ),
            'required'   => array( 'comment_id' ),
        );
    }

    public function execute( array $arguments ) {
        $this->validate_required( $arguments, array( 'comment_id' ) );

        $comment_id = $this->parse_required_id( $arguments['comment_id'], 'comment_id' );
        $force      = isset( $arguments['force'] ) && (bool) $arguments['force'];
        $params     = array();

        if ( isset( $arguments['force'] ) ) {
            $params['force'] = $force;
        }

        $data = $this->rest_request( 'DELETE', '/wp/v2/comments/' . $comment_id, $params );

        
        $returned_id = $force ? ( $data['previous']['id'] ?? null ) : $data['id'];

        return array(
            'deleted' => true,
            'id'      => $returned_id,
        );
    }
}
