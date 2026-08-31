<?php
namespace RankOut_Connector\Tools\Pages;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Update_Page extends Base_Tool {

    public function get_name() {
        return 'wp_update_page';
    }

    public function get_description() {
        return 'Updates an existing WordPress page (PATCH semantics — only supplied fields change). Required: `page_id`. Optional: `title`, `content` (HTML/Gutenberg blocks), `status` (publish/draft/pending/private/future/trash), `date` (ISO 8601, use with status="future" to reschedule), `excerpt`, `parent` (re-parent the page — 0 for top-level), `template`, `menu_order`, `slug`, `author`, `featured_media`, `comment_status`. Returns { id, title, status, modified, link }.';
    }

    public function get_category() {
        return 'pages';
    }

    public function get_required_capability() {
        return 'edit_pages';
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
                'page_id'    => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the page to update.',
                ),
                'title'      => array(
                    'type'        => 'string',
                    'description' => 'The new title for the page.',
                ),
                'content'    => array(
                    'type'        => 'string',
                    'description' => 'The new content for the page. HTML is allowed and will be sanitized.',
                ),
                'status'     => array(
                    'type'        => 'string',
                    'description' => 'The new status for the page.',
                    'enum'        => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ),
                ),
                'parent'     => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the new parent page.',
                ),
                'template'   => array(
                    'type'        => 'string',
                    'description' => 'The page template to use.',
                ),
                'menu_order' => array(
                    'type'        => 'integer',
                    'description' => 'The order of the page in menus and lists.',
                ),
                'slug'       => array(
                    'type'        => 'string',
                    'description' => 'URL-friendly slug for the page.',
                ),
                'date'       => array(
                    'type'        => 'string',
                    'description' => 'Publication date in ISO 8601 format. Useful for rescheduling.',
                ),
                'excerpt'    => array(
                    'type'        => 'string',
                    'description' => 'Page excerpt.',
                ),
                'author'     => array(
                    'type'        => 'integer',
                    'description' => 'Reassign the page to this user ID.',
                ),
                'featured_media' => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the featured media attachment.',
                ),
                'comment_status' => array(
                    'type'        => 'string',
                    'description' => 'Whether comments are open or closed.',
                    'enum'        => array( 'open', 'closed' ),
                ),
                'ping_status' => array(
                    'type'        => 'string',
                    'description' => 'Whether pingbacks/trackbacks are open or closed.',
                    'enum'        => array( 'open', 'closed' ),
                ),
            ),
            'required'   => array( 'page_id' ),
        );
    }

    public function execute( array $arguments ) {
        $this->validate_required( $arguments, array( 'page_id' ) );

        $this->validate_title_length( $arguments['title'] ?? null );

        $page_id = $this->parse_required_id( $arguments['page_id'], 'page_id' );
        $params  = array();

        if ( isset( $arguments['title'] ) ) {
            $params['title'] = sanitize_text_field( $arguments['title'] );
        }

        if ( isset( $arguments['content'] ) ) {
            
            
            
            
            $params['content'] = $arguments['content'];
        }

        if ( isset( $arguments['status'] ) ) {
            $params['status'] = sanitize_text_field( $arguments['status'] );
        }

        if ( isset( $arguments['parent'] ) ) {
            $params['parent'] = absint( $arguments['parent'] );
        }

        if ( isset( $arguments['template'] ) ) {
            $params['template'] = sanitize_text_field( $arguments['template'] );
        }

        if ( isset( $arguments['menu_order'] ) ) {
            $params['menu_order'] = intval( $arguments['menu_order'] );
        }

        if ( ! empty( $arguments['slug'] ) ) {
            $params['slug'] = sanitize_title( $arguments['slug'] );
        }

        if ( ! empty( $arguments['date'] ) ) {
            $params['date'] = sanitize_text_field( $arguments['date'] );
        }

        if ( isset( $arguments['excerpt'] ) ) {
            $params['excerpt'] = sanitize_text_field( $arguments['excerpt'] );
        }

        if ( isset( $arguments['author'] ) ) {
            $params['author'] = absint( $arguments['author'] );
        }

        if ( isset( $arguments['featured_media'] ) ) {
            $params['featured_media'] = absint( $arguments['featured_media'] );
        }

        if ( isset( $arguments['comment_status'] ) ) {
            $params['comment_status'] = sanitize_text_field( $arguments['comment_status'] );
        }

        if ( isset( $arguments['ping_status'] ) ) {
            $params['ping_status'] = sanitize_text_field( $arguments['ping_status'] );
        }

        $data = $this->rest_request( 'PUT', '/wp/v2/pages/' . $page_id, $params );

        return array(
            'id'       => $data['id'],
            'title'    => $data['title']['raw'] ?? $data['title']['rendered'],
            'status'   => $data['status'],
            'modified' => $data['modified'],
            'link'     => $data['link'],
        );
    }
}
