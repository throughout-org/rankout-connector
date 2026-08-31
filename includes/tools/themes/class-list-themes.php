<?php
namespace RankOut_Connector\Tools\Themes;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class List_Themes extends Base_Tool {

    public function get_name() {
        return 'wp_list_themes';
    }

    public function get_description() {
        return 'Lists all installed WordPress themes. Returns { themes: [{ stylesheet (theme folder name / identifier), name, version, status ("active"/"inactive"), author }], total }. The active theme has `status="active"`. Use `wp_get_active_theme` if you only need the current theme\'s details. Requires administrator access.';
    }

    public function get_category() {
        return 'themes';
    }

    public function get_required_capability() {
        return 'switch_themes';
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
            'properties' => new \stdClass(),
        );
    }

    public function execute( array $arguments ) {
        $data   = $this->rest_request( 'GET', '/wp/v2/themes' );
        $themes = array();

        foreach ( $data as $theme ) {
            $themes[] = array(
                'stylesheet' => isset( $theme['stylesheet'] ) ? $theme['stylesheet'] : '',
                'name'       => isset( $theme['name']['rendered'] ) ? wp_strip_all_tags( $theme['name']['rendered'] ) : '',
                'version'    => isset( $theme['version'] ) ? $theme['version'] : '',
                'status'     => isset( $theme['status'] ) ? $theme['status'] : '',
                'author'     => isset( $theme['author']['rendered'] ) ? wp_strip_all_tags( $theme['author']['rendered'] ) : '',
            );
        }

        return array( 'themes' => $themes, 'total' => count( $themes ) );
    }
}
