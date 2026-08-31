<?php
namespace RankOut_Connector\Tools\Taxonomy;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Delete_Category extends Base_Tool {

    public function get_name() {
        return 'wp_delete_category';
    }

    public function get_description() {
        return 'Permanently deletes a WordPress category. Required: `category_id`. Posts assigned to the deleted category are moved to the default "Uncategorized" category. Child categories are NOT deleted — they become top-level (parent becomes 0). There is no trash for categories; deletion is irreversible. Use `wp_list_categories` to find the category_id first.';
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
                'category_id' => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the category to delete.',
                ),
                'reassign'    => array(
                    'type'        => 'integer',
                    'description' => 'Optional: ID of the category to reassign posts to before deletion.',
                ),
            ),
            'required'   => array( 'category_id' ),
        );
    }

    public function execute( array $arguments ) {
        $this->validate_required( $arguments, array( 'category_id' ) );

        $category_id = $this->parse_required_id( $arguments['category_id'], 'category_id' );

        
        $default_category_id = get_option( 'default_category' );
        if ( (int) $category_id === (int) $default_category_id ) {
            throw new \Exception( 'WordPress does not allow deleting the default category.' );
        }

        
        
        $args = array();
        if ( isset( $arguments['reassign'] ) ) {
            $reassign_id = absint( $arguments['reassign'] );
            if ( $reassign_id > 0 ) {
                $args['default'] = $reassign_id;
            }
        }

        $result = wp_delete_term( $category_id, 'category', $args );

        if ( is_wp_error( $result ) ) {
            throw new \Exception( $result->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        if ( false === $result ) {
            throw new \Exception( 'Category not found or could not be deleted.' );
        }

        return array(
            'deleted' => true,
            'id'      => $category_id,
        );
    }
}
