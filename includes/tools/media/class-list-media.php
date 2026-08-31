<?php
namespace RankOut_Connector\Tools\Media;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class List_Media extends Base_Tool {

    public function get_name() {
        return 'wp_list_media';
    }

    public function get_description() {
        return 'Lists WordPress media library items. Optional: `search`, `media_type` (filter by type: image/video/audio/application), `mime_type` (e.g. "image/jpeg", "image/png", "application/pdf"), `per_page` (default 10, max 100), `page`, `orderby` (date/id/title/modified), `order` (asc/desc). Returns array of { id, title, alt_text, mime_type, source_url, date }.';
    }

    public function get_category() {
        return 'media';
    }

    public function get_required_capability() {
        return 'read'; 
    }

    public function get_annotations() {
        return array(
            'title'           => $this->get_title(),
            'readOnlyHint'    => true,
            'destructiveHint' => false,
            'openWorldHint'   => false,
        );
    }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'per_page'   => array(
                    'type'        => 'integer',
                    'description' => 'Number of media items per page (1-100).',
                    'default'     => 10,
                    'minimum'     => 1,
                    'maximum'     => 100,
                ),
                'page'       => array(
                    'type'        => 'integer',
                    'description' => 'Page number for pagination.',
                    'default'     => 1,
                ),
                'search'     => array(
                    'type'        => 'string',
                    'description' => 'Search query to filter media items.',
                ),
                'media_type' => array(
                    'type'        => 'string',
                    'description' => 'Media type to filter by.',
                    'enum'        => array( 'image', 'video', 'audio', 'application' ),
                ),
                'mime_type'  => array(
                    'type'        => 'string',
                    'description' => 'MIME type to filter by (e.g. image/jpeg, application/pdf).',
                ),
            ),
        );
    }

    public function execute( array $arguments ) {
        $params = array();

        $params['per_page'] = isset( $arguments['per_page'] ) ? min( 100, max( 1, absint( $arguments['per_page'] ) ) ) : 10;
        $params['page']     = isset( $arguments['page'] ) ? absint( $arguments['page'] ) : 1;

        if ( ! empty( $arguments['search'] ) ) {
            $params['search'] = sanitize_text_field( $arguments['search'] );
        }

        if ( ! empty( $arguments['media_type'] ) ) {
            $params['media_type'] = $arguments['media_type'];
        }

        if ( ! empty( $arguments['mime_type'] ) ) {
            $params['mime_type'] = sanitize_text_field( $arguments['mime_type'] );
        }

        $request = new \WP_REST_Request( 'GET', '/wp/v2/media' );
        foreach ( $params as $key => $value ) {
            $request->set_param( $key, $value );
        }

        $response = rest_do_request( $request );

        if ( $response->is_error() ) {
            $error = $response->as_error();
            throw new \RuntimeException( $error->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        $media = $response->get_data();
        $headers = $response->get_headers();
        $total   = isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : count( $media );

        $result = array();
        foreach ( $media as $item ) {
            $result[] = array(
                'id'         => $item['id'],
                'title'      => wp_strip_all_tags( $item['title']['rendered'] ),
                'mime_type'  => $item['mime_type'],
                'source_url' => $item['source_url'],
                'alt_text'   => $item['alt_text'] ?? '',
                'date'       => $item['date'],
                'media_type' => $item['media_type'],
            );
        }

        return array(
            'media' => $result,
            'total' => (int) $total,
            'page'  => $params['page'],
        );
    }
}
