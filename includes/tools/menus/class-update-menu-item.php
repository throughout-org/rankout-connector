<?php
namespace RankOut_Connector\Tools\Menus;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Update_Menu_Item extends Base_Tool {

    public function get_name() {
        return 'wp_update_menu_item';
    }

    public function get_description() {
        return 'Updates an existing menu item. Required: `item_id` (the menu_item id — get it from `wp_list_menu_items`; NOT the linked post/term id). Only fields you provide are changed. Common updates: `title`, `parent` (move under a different parent — 0 = top-level), `menu_order` (re-position; sibling order is by menu_order ascending), `url` (for custom URL items), `target` ("_blank" / ""), `classes`, `description`. To re-link an item to a different post/term, set `object_type` + `object` + `object_id` together. Returns the updated item object.';
    }

    public function get_category() {
        return 'menus';
    }

    public function get_required_capability() {
        return 'edit_theme_options';
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
                'item_id'  => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the menu item to update.',
                ),
                'title'    => array(
                    'type'        => 'string',
                    'description' => 'New title for the menu item.',
                ),
                'url'      => array(
                    'type'        => 'string',
                    'description' => 'New URL for the menu item.',
                ),
                'position' => array(
                    'type'        => 'integer',
                    'description' => 'New position (menu order) for the item.',
                ),
                'parent'   => array(
                    'type'        => 'integer',
                    'description' => 'New parent menu item ID.',
                ),
                'menu_id'  => array(
                    'type'        => 'integer',
                    'description' => 'Move the item to a different menu.',
                ),
            ),
            'required'   => array( 'item_id' ),
        );
    }

    public function execute( array $arguments ) {
        $this->validate_required( $arguments, array( 'item_id' ) );
        $this->validate_title_length( isset( $arguments['title'] ) ? $arguments['title'] : null );

        $item_id = $this->parse_required_id( $arguments['item_id'], 'item_id' );
        $request = new \WP_REST_Request( 'POST', '/wp/v2/menu-items/' . $item_id );

        if ( isset( $arguments['title'] ) ) {
            $request->set_param( 'title', sanitize_text_field( $arguments['title'] ) );
        }
        if ( isset( $arguments['url'] ) ) {
            $request->set_param( 'url', esc_url_raw( $arguments['url'] ) );
        }
        if ( isset( $arguments['position'] ) ) {
            $request->set_param( 'menu_order', absint( $arguments['position'] ) );
        }
        if ( isset( $arguments['parent'] ) ) {
            $request->set_param( 'parent', absint( $arguments['parent'] ) );
        }
        if ( isset( $arguments['menu_id'] ) ) {
            $request->set_param( 'menus', absint( $arguments['menu_id'] ) );
        }

        $response = rest_do_request( $request );

        if ( $response->is_error() ) {
            $wp_error = $response->as_error();
            if ( 'rest_no_route' === $wp_error->get_error_code() ) {
                throw new \RuntimeException(
                    'Menu endpoints are not available. This requires WordPress 5.9 or later with navigation menu support.'
                );
            }
            throw new \RuntimeException( $wp_error->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        $data = $response->get_data();

        return array(
            'id'    => $data['id'],
            'title' => $data['title']['raw'] ?? wp_strip_all_tags( $data['title']['rendered'] ?? '' ),
            'url'   => $data['url'] ?? '',
        );
    }
}
