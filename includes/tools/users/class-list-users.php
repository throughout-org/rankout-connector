<?php
namespace RankOut_Connector\Tools\Users;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class List_Users extends Base_Tool {

    public function get_name() {
        return 'wp_list_users';
    }

    public function get_description() {
        return 'Lists WordPress users. Optional: `search` (matches name/username/email), `roles` (array of role slugs — e.g. ["editor","author"] — OR operation), `per_page` (default 10, max 100), `page`, `orderby` (id/name/registered_date/email/include/slug/url — default id), `order` (asc/desc). Returns { users: [{ id, username, name, email, roles, registered_date }], total, page }. Requires `list_users` capability (administrators only by default).';
    }

    public function get_category() {
        return 'users';
    }

    public function get_required_capability() {
        return 'list_users';
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
                'per_page' => array(
                    'type'        => 'integer',
                    'description' => 'Number of users per page (1-100).',
                    'default'     => 10,
                    'minimum'     => 1,
                    'maximum'     => 100,
                ),
                'page'     => array(
                    'type'        => 'integer',
                    'description' => 'Page number for pagination.',
                    'default'     => 1,
                ),
                'search'   => array(
                    'type'        => 'string',
                    'description' => 'Search query to filter users.',
                ),
                'roles'    => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string' ),
                    'description' => 'Array of role slugs to filter by (e.g. administrator, editor, author).',
                ),
                'orderby'  => array(
                    'type'        => 'string',
                    'description' => 'Field to order results by.',
                    'enum'        => array( 'id', 'name', 'registered_date', 'email', 'include', 'slug', 'url' ),
                    'default'     => 'id',
                ),
                'order'    => array(
                    'type'        => 'string',
                    'description' => 'Sort direction.',
                    'enum'        => array( 'asc', 'desc' ),
                    'default'     => 'asc',
                ),
            ),
        );
    }

    public function execute( array $arguments ) {
        $params = array(
            'context' => 'edit',
        );

        $params['per_page'] = isset( $arguments['per_page'] ) ? min( 100, max( 1, absint( $arguments['per_page'] ) ) ) : 10;
        $params['page']     = isset( $arguments['page'] ) ? absint( $arguments['page'] ) : 1;

        if ( ! empty( $arguments['search'] ) ) {
            $params['search'] = sanitize_text_field( $arguments['search'] );
        }

        if ( ! empty( $arguments['roles'] ) ) {
            $params['roles'] = array_map( 'sanitize_text_field', $arguments['roles'] );
        }

        if ( ! empty( $arguments['orderby'] ) ) {
            $params['orderby'] = $arguments['orderby'];
        }

        if ( ! empty( $arguments['order'] ) ) {
            $params['order'] = $arguments['order'];
        }

        $request = new \WP_REST_Request( 'GET', '/wp/v2/users' );
        foreach ( $params as $key => $value ) {
            $request->set_param( $key, $value );
        }

        $response = rest_do_request( $request );

        if ( $response->is_error() ) {
            $error = $response->as_error();
            throw new \RuntimeException( $error->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        $users = $response->get_data();
        $headers = $response->get_headers();
        $total   = isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : count( $users );

        $result = array();
        foreach ( $users as $user ) {
            $result[] = array(
                'id'              => $user['id'],
                'username'        => $user['username'],
                'name'            => $user['name'],
                'email'           => $user['email'],
                'roles'           => $user['roles'],
                'registered_date' => $user['registered_date'],
            );
        }

        return array(
            'users' => $result,
            'total' => (int) $total,
            'page'  => $params['page'],
        );
    }
}
