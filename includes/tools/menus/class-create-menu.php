<?php
namespace RankOut_Connector\Tools\Menus;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Create_Menu extends Base_Tool {

    public function get_name() {
        return 'wp_create_menu';
    }

    public function get_description() {
        return 'Creates a new WordPress navigation menu (a `nav_menu` taxonomy term). Required: `name` (display name). Optional: `slug` (auto-generated from name if omitted), `description`. Returns { id, name, slug, description }. The new menu has zero items — use `wp_create_menu_item` to add items afterward, then assign the menu to a theme location separately. Creating two menus with the same name produces two distinct menus with disambiguated slugs. Requires WordPress 5.9+.';
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
                'name'        => array(
                    'type'        => 'string',
                    'description' => 'The name for the menu.',
                ),
                'slug'        => array(
                    'type'        => 'string',
                    'description' => 'The slug for the menu.',
                ),
                'description' => array(
                    'type'        => 'string',
                    'description' => 'The description for the menu.',
                ),
            ),
            'required'   => array( 'name' ),
        );
    }

    public function execute( array $arguments ) {
        $this->validate_required( $arguments, array( 'name' ) );

        $request = new \WP_REST_Request( 'POST', '/wp/v2/menus' );
        $request->set_param( 'name', sanitize_text_field( $arguments['name'] ) );

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
