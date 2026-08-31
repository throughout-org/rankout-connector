<?php
namespace RankOut_Connector\Tools\ACF;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Update_User_Fields extends Base_Tool {

    public function get_name() {
        return 'wp_acf_update_user_fields';
    }

    public function get_description() {
        return 'Updates ACF field values attached to a WordPress user. Pass field keys and values as an object in the "fields" parameter. Field groups must have "Show in REST API" enabled.';
    }

    public function get_category() {
        return 'acf';
    }

    public function get_required_capability() {
        return 'edit_users';
    }

    public function get_annotations() {
        return array( 'title' => $this->get_title(), 'readOnlyHint' => false, 'destructiveHint' => false, 'openWorldHint' => false );
    }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'user_id' => array( 'type' => 'integer', 'description' => 'The ID of the WordPress user to update ACF fields on.' ),
                'fields'  => array( 'type' => 'object',  'description' => 'Key-value pairs of ACF field keys and their new values.' ),
            ),
            'required' => array( 'user_id', 'fields' ),
        );
    }

    public function execute( array $arguments ) {
        if ( ! class_exists( 'ACF' ) ) {
            throw new \RuntimeException( 'Advanced Custom Fields (ACF) is not active on this site. Note: Secure Custom Fields (SCF) uses the same ACF class name so this check covers both.' );
        }
        $this->validate_required( $arguments, array( 'user_id', 'fields' ) );
        $user_id = $this->parse_required_id( $arguments['user_id'], 'user_id' );
        $fields  = $this->parse_json_param( $arguments['fields'], 'fields' );
        $data    = $this->rest_request( 'POST', '/wp/v2/users/' . $user_id, array( 'acf' => $fields ) );
        return array(
            'user_id'    => $user_id,
            'acf_fields' => $data['acf'] ?? array(),
        );
    }
}
