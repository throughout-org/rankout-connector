<?php
namespace RankOut_Connector\Tools\Revisions;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Delete_Revision extends Base_Tool {

    public function get_name() {
        return 'wp_delete_revision';
    }

    public function get_description() {
        return 'Permanently deletes a specific post revision. Required: `post_id` (the parent post ID) AND `revision_id` (the specific revision ID — get both from `wp_list_revisions`). Revisions do not support trashing — deletion is immediate and irreversible. Returns { deleted, revision_id, post_id }. Deleting a revision does not affect the live post.';
    }

    public function get_category() {
        return 'revisions';
    }

    public function get_required_capability() {
        return 'delete_posts';
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
                'post_id'     => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the parent post.',
                ),
                'revision_id' => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the revision to delete.',
                ),
            ),
            'required'   => array( 'post_id', 'revision_id' ),
        );
    }

    public function execute( array $arguments ) {
        $this->validate_required( $arguments, array( 'post_id', 'revision_id' ) );

        $post_id     = $this->parse_required_id( $arguments['post_id'], 'post_id' );
        $rest_base   = $this->resolve_post_rest_base( $post_id );
        $revision_id = $this->parse_required_id( $arguments['revision_id'], 'revision_id' );

        $data = $this->rest_request(
            'DELETE',
            '/wp/v2/' . $rest_base . '/' . $post_id . '/revisions/' . $revision_id,
            array( 'force' => true )
        );

        $deleted = is_array( $data ) && ! empty( $data['deleted'] );

        return array(
            'deleted'     => $deleted,
            'revision_id' => $revision_id,
            'post_id'     => $post_id,
        );
    }
}
