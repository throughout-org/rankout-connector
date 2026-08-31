<?php
namespace RankOut_Connector\Tools\Events_Calendar;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class List_Events extends Base_Tool {

    public function get_name() {
        return 'wp_tec_list_events';
    }

    public function get_description() {
        return 'Lists events from The Events Calendar with optional filtering by date range, venue, organizer, category, and search. Returns id, title, start_date, end_date, venue, organizer, and permalink.';
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
                    'description' => 'Number of events per page.',
                    'default'     => 10,
                    'minimum'     => 1,
                    'maximum'     => 100,
                ),
                'page'       => array(
                    'type'        => 'integer',
                    'description' => 'Page number.',
                    'default'     => 1,
                ),
                'search'     => array(
                    'type'        => 'string',
                    'description' => 'Search term to filter events.',
                ),
                'start_date' => array(
                    'type'        => 'string',
                    'description' => 'Filter events starting on or after this date (YYYY-MM-DD).',
                ),
                'end_date'   => array(
                    'type'        => 'string',
                    'description' => 'Filter events ending on or before this date (YYYY-MM-DD).',
                ),
                'venue'      => array(
                    'type'        => 'integer',
                    'description' => 'Filter by venue ID.',
                ),
                'organizer'  => array(
                    'type'        => 'integer',
                    'description' => 'Filter by organizer ID.',
                ),
                'categories' => array(
                    'type'        => 'array',
                    'description' => 'Filter by event category term IDs.',
                    'items'       => array( 'type' => 'integer' ),
                ),
                'tags'       => array(
                    'type'        => 'array',
                    'description' => 'Filter by event tag term IDs.',
                    'items'       => array( 'type' => 'integer' ),
                ),
                'featured'   => array(
                    'type'        => 'boolean',
                    'description' => 'Limit results to featured events only.',
                ),
                'status'     => array(
                    'type'        => 'string',
                    'description' => 'Filter by post status (publish, draft, pending, etc.).',
                ),
            ),
            'required'   => array(),
        );
    }

    public function execute( array $arguments ) {
        if ( ! class_exists( 'Tribe__Events__Main' ) ) {
            throw new \RuntimeException( 'The Events Calendar is not active on this site. Please install and activate The Events Calendar plugin.' );
        }

        $page     = isset( $arguments['page'] ) ? absint( $arguments['page'] ) : 1;
        $per_page = isset( $arguments['per_page'] ) ? min( 100, max( 1, absint( $arguments['per_page'] ) ) ) : 10;

        
        
        $use_wp_query = ! empty( $arguments['categories'] ) || ! empty( $arguments['tags'] ) || isset( $arguments['status'] );

        if ( ! $use_wp_query ) {
            $params = array(
                'per_page' => $per_page,
                'page'     => $page,
            );
            if ( isset( $arguments['search'] ) )     $params['search']     = sanitize_text_field( $arguments['search'] );
            if ( isset( $arguments['start_date'] ) ) $params['start_date'] = sanitize_text_field( $arguments['start_date'] );
            if ( isset( $arguments['end_date'] ) )   $params['end_date']   = sanitize_text_field( $arguments['end_date'] );
            if ( isset( $arguments['venue'] ) )      $params['venue']      = absint( $arguments['venue'] );
            if ( isset( $arguments['organizer'] ) )  $params['organizer']  = absint( $arguments['organizer'] );
            if ( isset( $arguments['featured'] ) )   $params['featured']   = (bool) $arguments['featured'];

            $data   = $this->rest_request( 'GET', '/tribe/events/v1/events', $params );
            $events = $data['events'] ?? $data;

            if ( ! is_array( $events ) ) {
                return array( 'events' => array(), 'page' => $page );
            }

            return array(
                'events' => array_map( function ( $e ) {
                    return array(
                        'id'         => $e['id'],
                        'title'      => $e['title'],
                        'start_date' => $e['start_date'],
                        'end_date'   => $e['end_date'],
                        'all_day'    => $e['all_day'],
                        'venue'      => $e['venue']['venue'] ?? null,
                        'organizer'  => $e['organizer'][0]['organizer'] ?? null,
                        'permalink'  => $e['url'],
                    );
                }, $events ),
                'page' => $page,
            );
        }

        
        $args = array(
            'post_type'      => \Tribe__Events__Main::POSTTYPE,
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'post_status'    => isset( $arguments['status'] ) ? sanitize_key( $arguments['status'] ) : 'publish',
        );

        $tax_query = array();
        if ( ! empty( $arguments['categories'] ) ) {
            $tax_query[] = array(
                'taxonomy' => \Tribe__Events__Main::TAXONOMY,
                'field'    => 'term_id',
                'terms'    => array_map( 'absint', $this->parse_json_param( $arguments['categories'], 'categories' ) ),
            );
        }
        if ( ! empty( $arguments['tags'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'post_tag',
                'field'    => 'term_id',
                'terms'    => array_map( 'absint', $this->parse_json_param( $arguments['tags'], 'tags' ) ),
            );
        }
        if ( ! empty( $tax_query ) ) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required to filter events by category/tag via WP_Query.
            $args['tax_query'] = $tax_query;
        }
        if ( isset( $arguments['search'] ) ) {
            $args['s'] = sanitize_text_field( $arguments['search'] );
        }

        $query  = new \WP_Query( $args );
        $result = array();
        foreach ( $query->posts as $post ) {
            $venue_id = get_post_meta( $post->ID, '_EventVenueID', true );
            $org_id   = get_post_meta( $post->ID, '_EventOrganizerID', true );
            $result[] = array(
                'id'         => $post->ID,
                'title'      => $post->post_title,
                'start_date' => get_post_meta( $post->ID, '_EventStartDate', true ),
                'end_date'   => get_post_meta( $post->ID, '_EventEndDate', true ),
                'all_day'    => (bool) get_post_meta( $post->ID, '_EventAllDay', true ),
                'venue'      => $venue_id ? get_the_title( (int) $venue_id ) : null,
                'organizer'  => $org_id ? get_the_title( (int) $org_id ) : null,
                'permalink'  => get_permalink( $post->ID ),
            );
        }

        return array( 'events' => $result, 'page' => $page );
    }
}
