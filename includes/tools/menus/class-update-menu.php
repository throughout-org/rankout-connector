<?php
namespace RankOut_Connector\Tools\Menus;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Update_Menu extends Base_Tool {

    public function get_name() {
        return 'wp_update_menu';
    }

    public function get_description() {
        return 'Updates an existing WordPress navigation menu (the `nav_menu` term itself — name/slug/description). Required: `menu_id`. Optional: `name`, `slug`, `description` (only fields you provide are changed). Does NOT add or modify items in the menu — use `wp_create_menu_item` / `wp_update_menu_item` / `wp_delete_menu_item` for that. Returns the updated { id, name, slug, description }. Requires WordPress 5.9+.';
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
                'menu_id'     => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the menu to update.',
                ),
                'name'        => array(
                    'type'        => 'string',
                    'description' => 'New name for the menu.',
                ),
                'slug'        => array(
                    'type'        => 'string',
                    'description' => 'New slug for the menu.',
                ),
                'description' => array(
                    'type'        => 'string',
                    'description' => 'New description for the menu.',
                ),
            ),
            'required'   => array( 'menu_id' ),
        );
    }

    public function execute( array $arguments ) {
        $this->validate_required( $arguments, array( 'menu_id' ) );

        $menu_id = $this->parse_required_id( $arguments['menu_id'], 'menu_id' );
        $request = new \WP_REST_Request( 'POST', '/wp/v2/menus/' . $menu_id );

        if ( isset( $arguments['name'] ) ) {
            $request->set_param( 'name', sanitize_text_field( $arguments['name'] ) );
        }
        if ( ! empty( $arguments['slug'] ) ) {
            $request->set_param( 'slug', sanitize_title( $arguments['slug'] ) );
        }
        if ( isset( $arguments['description'] ) ) {
            $request->set_param( 'description', sanitize_text_field( $arguments['description'] ) );
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
            'id'          => $data['id'],
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'description' => $data['description'] ?? '',
        );
    }
}
