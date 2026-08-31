<?php
namespace RankOut_Connector\Tools\Styles;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Update_Global_Styles extends Base_Tool {

    public function get_name() {
        return 'wp_update_global_styles';
    }

    public function get_description() {
        return 'Updates the global styles (theme.json) settings and/or styles. At least one of `styles` (object — CSS custom properties, element styles) or `settings` (object — color palette, typography, spacing) must be provided. Returns { id, settings, styles, title }. Changes are user-level overrides — they persist across theme updates but can be reset by clearing the Global Styles post. Requires an active block theme (Full Site Editing).';
    }

    public function get_category() {
        return 'styles';
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
                'styles'   => array(
                    'type'        => 'object',
                    'description' => 'Styles object following theme.json structure (color, typography, spacing, etc.).',
                ),
                'settings' => array(
                    'type'        => 'object',
                    'description' => 'Settings object following theme.json structure.',
                ),
            ),
        );
    }

    public function execute( array $arguments ) {
        if ( ! isset( $arguments['styles'] ) && ! isset( $arguments['settings'] ) ) {
            throw new \InvalidArgumentException( 'At least one of "styles" or "settings" must be provided.' );
        }

        $global_styles_id = $this->discover_global_styles_id();

        $request = new \WP_REST_Request( 'POST', '/wp/v2/global-styles/' . $global_styles_id );

        $body = array();
        if ( isset( $arguments['styles'] ) ) {
            $body['styles'] = $this->parse_json_param( $arguments['styles'], 'styles' );
        }
        if ( isset( $arguments['settings'] ) ) {
            $body['settings'] = $this->parse_json_param( $arguments['settings'], 'settings' );
        }

        if ( ! empty( $body ) ) {
            $request->set_body( wp_json_encode( $body ) );
            $request->set_header( 'content-type', 'application/json' );
        }

        $response = rest_do_request( $request );

        if ( $response->is_error() ) {
            $wp_error = $response->as_error();
            if ( 'rest_no_route' === $wp_error->get_error_code() ) {
                throw new \RuntimeException(
                    'Global styles endpoint is not available. This requires an active block theme (Full Site Editing). The current theme appears to be a classic theme.'
                );
            }
            throw new \RuntimeException( $wp_error->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        $data = $response->get_data();

        return array(
            'id'       => $data['id'],
            'settings' => $data['settings'] ?? new \stdClass(),
            'styles'   => $data['styles'] ?? new \stdClass(),
            'title'    => $data['title']['raw'] ?? wp_strip_all_tags( $data['title']['rendered'] ?? '' ),
        );
    }
}
