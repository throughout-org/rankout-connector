<?php
namespace RankOut_Connector\Tools\BuddyPress;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Create_Activity extends Base_Tool {

    public function get_name() {
        return 'wp_bp_create_activity';
    }

    public function get_description() {
        return 'Posts a new activity item to the BuddyPress activity stream. Required: `content`. Optional: `type` (activity type slug — default "activity_update"; other values include new_blog_post, new_blog_comment, friendship_created), `component` (default "activity"), `item_id` (primary object ID, e.g. group ID for group-scoped activity), `secondary_item_id`, `primary_link` (canonical URL the activity points to), `hide_sitewide` (boolean — hides from sitewide stream, shows only in group/member streams). Returns { id, type, content }. Requires BuddyPress Activity component enabled.';
    }

    public function get_category() {
        return 'buddypress';
    }

    public function get_required_capability() {
        return 'edit_posts';
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
                'content'           => array(
                    'type' => 'string',
                ),
                'type'              => array(
                    'type'    => 'string',
                    'default' => 'activity_update',
                ),
                'component'         => array(
                    'type'    => 'string',
                    'default' => 'activity',
                ),
                'item_id'           => array(
                    'type'        => 'integer',
                    'description' => 'ID of the item being acted on (e.g. group ID). Maps to the BuddyPress API item_id field.',
                ),
                'secondary_item_id' => array(
                    'type'        => 'integer',
                    'description' => 'ID of a secondary item.',
                ),
                'primary_link'      => array(
                    'type'        => 'string',
                    'description' => 'The canonical URL this activity item points to.',
                ),
                'hide_sitewide'     => array(
                    'type'        => 'boolean',
                    'description' => 'Whether to hide the activity from the global sitewide stream.',
                ),
            ),
            'required'   => array( 'content' ),
        );
    }

    public function execute( array $arguments ) {
        if ( ! class_exists( 'BuddyPress' ) ) {
            throw new \RuntimeException( 'BuddyPress is not active on this site. Please install and activate BuddyPress to use this tool.' );
        }

        if ( function_exists( 'bp_is_active' ) && ! bp_is_active( 'activity' ) ) {
            throw new \RuntimeException( 'The BuddyPress Activity component is not enabled. Go to Settings → BuddyPress → Components and enable Activity Streams.' );
        }

        $this->validate_required( $arguments, array( 'content' ) );

        $params = array(
            'content'   => wp_kses_post( $arguments['content'] ),
            'type'      => sanitize_text_field( $arguments['type'] ?? 'activity_update' ),
            'component' => sanitize_text_field( $arguments['component'] ?? 'activity' ),
        );

        if ( isset( $arguments['item_id'] ) && absint( $arguments['item_id'] ) > 0 ) {
            $params['item_id'] = absint( $arguments['item_id'] );
        }
        if ( isset( $arguments['secondary_item_id'] ) && absint( $arguments['secondary_item_id'] ) > 0 ) {
            $params['secondary_item_id'] = absint( $arguments['secondary_item_id'] );
        }
        if ( isset( $arguments['primary_link'] ) ) {
            $params['primary_link'] = esc_url_raw( $arguments['primary_link'] );
        }
        if ( isset( $arguments['hide_sitewide'] ) ) {
            $params['hide_sitewide'] = (bool) $arguments['hide_sitewide'];
        }

        $data = $this->rest_request( 'POST', '/buddypress/v1/activity', $params );

        
        $item = isset( $data[0] ) ? $data[0] : $data;

        return array(
            'id'      => $item['id'] ?? null,
            'type'    => $item['type'] ?? null,
            'content' => $item['content']['rendered'] ?? $item['content'] ?? null,
        );
    }
}
