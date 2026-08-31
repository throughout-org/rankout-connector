<?php
namespace RankOut_Connector\Tools\Events_Calendar;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Create_Organizer extends Base_Tool {

    public function get_name() {
        return 'wp_tec_create_organizer';
    }

    public function get_description() {
        return 'Creates a new organizer in The Events Calendar. Required: `organizer` (the organizer name). Optional: `email`, `url` (website URL — stored as the website field internally), `phone`. Returns { id, organizer (name), website }. Use the returned `id` when creating or updating events via the `organizer` parameter. Requires The Events Calendar plugin active.';
    }

    public function get_category() {
        return 'events-calendar';
    }

    public function get_required_capability() {
        return 'edit_tribe_organizers';
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
                'organizer' => array(
                    'type'        => 'string',
                    'description' => 'The organizer name.',
                ),
                'email'     => array(
                    'type'        => 'string',
                    'description' => 'Email address of the organizer.',
                ),
                'url'   => array(
                    'type'        => 'string',
                    'description' => 'Website URL of the organizer.',
                ),
                'phone'     => array(
                    'type'        => 'string',
                    'description' => 'Phone number of the organizer.',
                ),
            ),
            'required'   => array( 'organizer' ),
        );
    }

    public function execute( array $arguments ) {
        if ( ! class_exists( 'Tribe__Events__Main' ) ) {
            throw new \RuntimeException( 'The Events Calendar is not active on this site. Please install and activate The Events Calendar plugin.' );
        }

        $this->validate_required( $arguments, array( 'organizer' ) );

        $params = array(
            'organizer' => sanitize_text_field( $arguments['organizer'] ),
        );

        if ( isset( $arguments['email'] ) ) {
            $params['email'] = sanitize_text_field( $arguments['email'] );
        }
        if ( isset( $arguments['url'] ) ) {
            $params['website'] = sanitize_text_field( $arguments['url'] );
        }
        if ( isset( $arguments['phone'] ) ) {
            $params['phone'] = sanitize_text_field( $arguments['phone'] );
        }

        $data = $this->rest_request( 'POST', '/tribe/events/v1/organizers', $params );

        return array(
            'id'        => $data['id'],
            'organizer' => $data['organizer'],
            'website'   => $data['website'] ?? null,
        );
    }
}
