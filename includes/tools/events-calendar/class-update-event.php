<?php
namespace RankOut_Connector\Tools\Events_Calendar;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Update_Event extends Base_Tool {

    public function get_name() {
        return 'wp_tec_update_event';
    }

    public function get_description() {
        return 'Updates an existing event in The Events Calendar (PUT semantics — supply all fields you want to keep, not just the changed ones). Required: `id` (event post ID). Optional: `title`, `start_date` (YYYY-MM-DD HH:MM:SS), `end_date` (YYYY-MM-DD HH:MM:SS), `description`, `all_day` (boolean), `venue` (venue ID — use `wp_tec_list_venues`), `organizer` (array of organizer IDs — use `wp_tec_list_organizers`), `cost` (string, e.g. "10.00"), `url` (event website URL), `status` (publish/draft/private). Returns { id, title, start_date, end_date, url }. Requires The Events Calendar plugin active.';
    }

    public function get_category() {
        return 'events-calendar';
    }

    public function get_required_capability() {
        return 'edit_tribe_events';
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
                'id'          => array(
                    'type'        => 'integer',
                    'description' => 'The event post ID.',
                ),
                'title'       => array(
                    'type'        => 'string',
                    'description' => 'The event title.',
                ),
                'start_date'  => array(
                    'type'        => 'string',
                    'description' => 'Event start date and time (YYYY-MM-DD HH:MM:SS).',
                ),
                'end_date'    => array(
                    'type'        => 'string',
                    'description' => 'Event end date and time (YYYY-MM-DD HH:MM:SS).',
                ),
                'description' => array(
                    'type'        => 'string',
                    'description' => 'Event description.',
                ),
                'all_day'     => array(
                    'type'        => 'boolean',
                    'description' => 'Whether the event is an all-day event.',
                ),
                'venue'       => array(
                    'type'        => 'integer',
                    'description' => 'Venue post ID.',
                ),
                'organizer'   => array(
                    'type'        => 'array',
                    'description' => 'Array of organizer post IDs. Pass a single ID as a one-element array.',
                    'items'       => array( 'type' => 'integer' ),
                ),
                'cost'        => array(
                    'type'        => 'string',
                    'description' => 'Event cost.',
                ),
                'url'     => array(
                    'type'        => 'string',
                    'description' => 'Event website URL.',
                ),
                'status'      => array(
                    'type'        => 'string',
                    'description' => 'Event post status.',
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
        $id     = $this->parse_required_id( $arguments['id'], 'id' );
        $params = array();

        if ( isset( $arguments['title'] ) ) {
            $params['title'] = sanitize_text_field( $arguments['title'] );
        }
        if ( isset( $arguments['start_date'] ) ) {
            $params['start_date'] = sanitize_text_field( $arguments['start_date'] );
        }
        if ( isset( $arguments['end_date'] ) ) {
            $params['end_date'] = sanitize_text_field( $arguments['end_date'] );
        }
        if ( isset( $arguments['description'] ) ) {
            $params['description'] = sanitize_textarea_field( $arguments['description'] );
        }
        if ( isset( $arguments['all_day'] ) ) {
            $params['all_day'] = (bool) $arguments['all_day'];
        }
        if ( isset( $arguments['venue'] ) ) {
            $params['venue'] = absint( $arguments['venue'] );
        }
        if ( isset( $arguments['organizer'] ) ) {
            $raw_organizer = is_string( $arguments['organizer'] ) ? json_decode( $arguments['organizer'], true ) : $arguments['organizer'];
            $organizer_ids = is_array( $raw_organizer )
                ? array_values( array_filter( array_map( 'absint', $raw_organizer ) ) )
                : array_filter( array( absint( $raw_organizer ) ) );
            if ( ! empty( $organizer_ids ) ) {
                $params['organizer'] = $organizer_ids;
            }
        }
        if ( isset( $arguments['cost'] ) ) {
            $params['cost'] = sanitize_text_field( $arguments['cost'] );
        }
        if ( isset( $arguments['url'] ) ) {
            $params['url'] = sanitize_url( $arguments['url'] );
        }
        if ( isset( $arguments['status'] ) ) {
            $params['status'] = sanitize_text_field( $arguments['status'] );
        }

        $data = $this->rest_request( 'PUT', '/tribe/events/v1/events/' . $id, $params );

        return array(
            'id'         => $data['id'],
            'title'      => $data['title'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'url'        => $data['url'],
        );
    }
}
