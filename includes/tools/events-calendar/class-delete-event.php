<?php
namespace RankOut_Connector\Tools\Events_Calendar;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Delete_Event extends Base_Tool {

    public function get_name() {
        return 'wp_tec_delete_event';
    }

    public function get_description() {
        return 'Permanently deletes an event from The Events Calendar. Required: `id` (the event post ID — get it from `wp_tec_list_events`). Deletion is irreversible; the event post is removed from WordPress entirely. Returns { deleted: true, id }. To hide an event temporarily, use `wp_update_post` to set status="draft" on the event post instead. Requires The Events Calendar plugin active.';
    }

    public function get_category() {
        return 'events-calendar';
    }

    public function get_required_capability() {
        return 'delete_tribe_events';
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
                'id' => array(
                    'type'        => 'integer',
                    'description' => 'The event post ID to delete.',
                ),
            ),
            'required'   => array( 'id' ),
        );
    }

    public function execute( array $arguments ) {
        if ( ! class_exists( 'Tribe__Events__Main' ) ) {
            throw new \RuntimeException( 'The Events Calendar is not active on this site. Please install and activate The Events Calendar plugin.' );
        }

        $this->validate_required( $arguments, array( 'id' ) );
        $id = $this->parse_required_id( $arguments['id'], 'id' );
        $this->rest_request( 'DELETE', '/tribe/events/v1/events/' . $id, array() );

        return array(
            'deleted' => true,
            'id'      => $id,
        );
    }
}
