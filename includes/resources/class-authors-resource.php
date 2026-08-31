<?php
namespace RankOut_Connector\Resources;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Authors_Resource extends Base_Resource {

    public function get_uri() {
        return 'wp://users/authors';
    }

    public function get_name() {
        return 'Authors';
    }

    public function get_description() {
        return 'All users who can create posts, with display names, logins, and roles.';
    }

    public function read() {
        $users   = get_users( array(
            'capability' => array( 'edit_posts' ),
            'orderby'    => 'display_name',
            'order'      => 'ASC',
        ) );
        $authors = array();

        $can_list_users = current_user_can( 'list_users' );

        foreach ( $users as $user ) {
            $authors[] = array(
                'id'           => (int) $user->ID,
                'display_name' => $user->display_name,
                'login'        => $user->user_login,
                'email'        => $can_list_users ? $user->user_email : null,
                'roles'        => array_values( (array) $user->roles ),
            );
        }

        return array( 'authors' => $authors );
    }
}
