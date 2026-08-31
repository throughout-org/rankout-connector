<?php
namespace RankOut_Connector\Tools\Templates;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Update_Template extends Base_Tool {

    public function get_name() {
        return 'wp_update_template';
    }

    public function get_description() {
        return 'Updates a block template\'s content. Requires an active block theme (Full Site Editing).';
    }

    public function get_category() {
        return 'templates';
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
                'template_id' => array(
                    'type'        => 'string',
                    'description' => 'The template ID (e.g. theme-slug//template-slug).',
                ),
                'content'     => array(
                    'type'        => 'string',
                    'description' => 'New block markup content for the template.',
                ),
            ),
            'required'   => array( 'template_id', 'content' ),
        );
    }

    public function execute( array $arguments ) {
        if ( ! wp_is_block_theme() ) {
            throw new \RuntimeException( 'Templates are not available. This requires an active block theme (Full Site Editing). The current theme is a classic theme.' );
        }

        $this->validate_required( $arguments, array( 'template_id', 'content' ) );

        
        
        
        $template_id = sanitize_text_field( $arguments['template_id'] );
        if ( ! preg_match( '/^[A-Za-z0-9_-]+\/\/[A-Za-z0-9_-]+$/', $template_id ) ) {
            throw new \InvalidArgumentException( 'Invalid template_id format. Expected: theme-slug//template-slug (letters, numbers, hyphens, underscores only in each segment).' );
        }

        $request = new \WP_REST_Request( 'POST', '/wp/v2/templates/' . $template_id );
        $request->set_param( 'content', $arguments['content'] );

        $response = rest_do_request( $request );

        if ( $response->is_error() ) {
            $wp_error          = $response->as_error();
            $block_theme_codes = array( 'rest_no_route', 'rest_cannot_manage_templates' );
            if ( in_array( $wp_error->get_error_code(), $block_theme_codes, true ) ) {
                throw new \RuntimeException(
                    'Templates endpoint is not available. This requires an active block theme (Full Site Editing). The current theme appears to be a classic theme.'
                );
            }
            throw new \RuntimeException( $wp_error->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        $data = $response->get_data();

        return array(
            'id'     => $data['id'],
            'slug'   => $data['slug'],
            'title'  => $data['title']['raw'] ?? wp_strip_all_tags( $data['title']['rendered'] ?? '' ),
            'status' => $data['status'],
        );
    }
}
