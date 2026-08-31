<?php
namespace RankOut_Connector\Tools\Taxonomy;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Delete_Tag extends Base_Tool {

    public function get_name() {
        return 'wp_delete_tag';
    }

    public function get_description() {
        return 'Permanently deletes a WordPress tag. Required: `tag_id`. Posts are untagged (the tag is removed from posts, but the posts themselves are unaffected). There is no trash for tags; deletion is irreversible. Use `wp_list_tags` to find the tag_id first.';
    }

    public function get_category() {
        return 'taxonomy';
    }

    public function get_required_capability() {
        return 'manage_categories';
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
                'tag_id'   => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the tag to delete.',
                ),
                'reassign' => array(
                    'type'        => 'integer',
                    'description' => 'Optional: ID of the tag to reassign posts to before deletion.',
                ),
            ),
            'required'   => array( 'tag_id' ),
        );
    }

    public function execute( array $arguments ) {
        $this->validate_required( $arguments, array( 'tag_id' ) );

        $tag_id = $this->parse_required_id( $arguments['tag_id'], 'tag_id' );

        
        
        $args = array();
        if ( isset( $arguments['reassign'] ) ) {
            $reassign_id = absint( $arguments['reassign'] );
            if ( $reassign_id > 0 ) {
                $args['default'] = $reassign_id;
            }
        }

        $result = wp_delete_term( $tag_id, 'post_tag', $args );

        if ( is_wp_error( $result ) ) {
            throw new \Exception( $result->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        if ( false === $result ) {
            throw new \Exception( 'Tag not found or could not be deleted.' );
        }

        return array(
            'deleted' => true,
            'id'      => $tag_id,
        );
    }
}
