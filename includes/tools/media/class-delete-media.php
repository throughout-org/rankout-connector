<?php
namespace RankOut_Connector\Tools\Media;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Delete_Media extends Base_Tool {

    public function get_name() {
        return 'wp_delete_media';
    }

    public function get_description() {
        return 'Permanently deletes a WordPress media attachment by ID. Required: `attachment_id`. Media items bypass the trash — deletion is immediate and irreversible. The physical file on disk is also deleted along with all generated image sizes. Posts that reference this attachment via `featured_media` or inline `<img>` tags will show broken images. Returns { deleted: true, id }.';
    }

    public function get_category() {
        return 'media';
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
                'media_id' => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the media item to delete.',
                ),
                'force'    => array(
                    'type'        => 'boolean',
                    'description' => 'Whether to force deletion. Default is true since media items do not support the trash.',
                    'default'     => true,
                ),
            ),
            'required'   => array( 'media_id' ),
        );
    }

    public function execute( array $arguments ) {
        $this->validate_required( $arguments, array( 'media_id' ) );

        $media_id = $this->parse_required_id( $arguments['media_id'], 'media_id' );
        $force    = isset( $arguments['force'] ) ? (bool) $arguments['force'] : true;

        $data = $this->rest_request( 'DELETE', '/wp/v2/media/' . $media_id, array( 'force' => $force ) );

        
        
        
        $id = $data['previous']['id'] ?? null;

        return array(
            'deleted' => true,
            'id'      => $id,
        );
    }
}
