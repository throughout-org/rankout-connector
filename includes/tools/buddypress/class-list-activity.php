<?php
namespace RankOut_Connector\Tools\BuddyPress;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class List_Activity extends Base_Tool {

    public function get_name() {
        return 'wp_bp_list_activity';
    }

    public function get_description() {
        return 'Lists the BuddyPress sitewide activity stream. Optional: `type` (filter by activity type slug, e.g. activity_update/new_blog_post/friendship_created), `component` (filter by component, e.g. activity/groups/friends), `user_id` (filter by author), `item_id` (filter by primary object ID, e.g. group ID), `per_page` (default 20), `page`. Returns { activity: [...], page } where each item includes id, type, component, content, date_recorded, user_id, link. Requires BuddyPress Activity component enabled.';
    }

    public function get_category() {
        return 'buddypress';
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
                'per_page'  => array(
                    'type'    => 'integer',
                    'default' => 20,
                    'minimum'     => 1,
                    'maximum'     => 100,
                ),
                'page'      => array(
                    'type'    => 'integer',
                    'default' => 1,
                ),
                'type'      => array(
                    'type'        => 'string',
                    'description' => 'Filter by activity type (e.g. activity_update, new_blog_post).',
                ),
                'component' => array(
                    'type'        => 'string',
                    'description' => 'Filter by BuddyPress component (e.g. activity, groups, friends).',
                ),
                'user_id'   => array(
                    'type'        => 'integer',
                    'description' => 'Filter activity by author user ID.',
                ),
                'item_id'   => array(
                    'type'        => 'integer',
                    'description' => 'Filter by primary item ID (e.g. group ID for group activity).',
                ),
                'secondary_item_id' => array(
                    'type'        => 'integer',
                    'description' => 'Filter by secondary item ID.',
                ),
                'search'    => array(
                    'type'        => 'string',
                    'description' => 'Search term to filter activity content.',
                ),
            ),
            'required'   => array(),
        );
    }

    public function execute( array $arguments ) {
        if ( ! class_exists( 'BuddyPress' ) ) {
            throw new \RuntimeException( 'BuddyPress is not active on this site. Please install and activate BuddyPress to use this tool.' );
        }

        if ( function_exists( 'bp_is_active' ) && ! bp_is_active( 'activity' ) ) {
            throw new \RuntimeException( 'The BuddyPress Activity component is not enabled. Go to Settings → BuddyPress → Components and enable Activity Streams.' );
        }

        $page   = isset( $arguments['page'] ) ? absint( $arguments['page'] ) : 1;
        $params = array(
            'per_page' => isset( $arguments['per_page'] ) ? min( 100, max( 1, absint( $arguments['per_page'] ) ) ) : 20,
            'page'     => $page,
        );

        if ( isset( $arguments['type'] ) ) {
            $params['type'] = sanitize_text_field( $arguments['type'] );
        }
        if ( isset( $arguments['component'] ) ) {
            $params['component'] = sanitize_text_field( $arguments['component'] );
        }
        if ( isset( $arguments['user_id'] ) ) {
            $params['user_id'] = absint( $arguments['user_id'] );
        }
        if ( isset( $arguments['item_id'] ) ) {
            $params['item_id'] = absint( $arguments['item_id'] );
        }
        if ( isset( $arguments['secondary_item_id'] ) ) {
            $params['secondary_item_id'] = absint( $arguments['secondary_item_id'] );
        }
        if ( isset( $arguments['search'] ) ) {
            $params['search'] = sanitize_text_field( $arguments['search'] );
        }

        $data = $this->rest_request( 'GET', '/buddypress/v1/activity', $params );

        return array(
            'activity' => $data,
            'page'     => $page,
        );
    }
}
