<?php
namespace RankOut_Connector\Tools\ACF;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Get_User_Fields extends Base_Tool {

    public function get_name() {
        return 'wp_acf_get_user_fields';
    }

    public function get_description() {
        return 'Gets ACF (Advanced Custom Fields) field values attached to a WordPress user. Field groups must have "Show in REST API" enabled and location rules targeting Users.';
    }

    public function get_category() {
        return 'acf';
    }

    public function get_required_capability() {
        return 'edit_users';
    }

    public function get_annotations() {
        return array( 'title' => $this->get_title(), 'readOnlyHint' => true, 'destructiveHint' => false, 'openWorldHint' => false );
    }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'user_id' => array( 'type' => 'integer', 'description' => 'The ID of the WordPress user to retrieve ACF fields from.' ),
            ),
            'required' => array( 'user_id' ),
        );
    }

    public function execute( array $arguments ) {
        if ( ! class_exists( 'ACF' ) ) {
            throw new \RuntimeException( 'Advanced Custom Fields (ACF) is not active on this site. Note: Secure Custom Fields (SCF) uses the same ACF class name so this check covers both.' );
        }
        $user_id = $this->parse_required_id( $arguments['user_id'], 'user_id' );
        $data    = $this->rest_request( 'GET', '/wp/v2/users/' . $user_id );
        return array(
            'user_id'    => $user_id,
            'acf_fields' => $data['acf'] ?? array(),
        );
    }
}
