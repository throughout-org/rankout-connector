<?php




namespace RankOut_Connector\Tools\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use RankOut_Connector\Tools\Base_Tool;

class Aioseo_Update_Post_Seo extends Base_Tool {

	public function get_name() {
		return 'wp_aioseo_update_post_seo';
	}

	public function get_description() {
		return 'Updates AIOSEO SEO metadata on a post or page via the WP REST API aioseo_meta_data field. Requires AIOSEO Plus plan or higher with the REST API addon active. Pass fields as an object: title, description, og_title, og_description, twitter_title, twitter_description, no_index (boolean), canonical_url.';
	}

	public function get_category() {
		return 'aioseo';
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
				'post_id'   => array(
					'type'        => 'integer',
					'description' => 'The ID of the post or page.',
				),
				'post_type' => array(
					'type'        => 'string',
					'description' => 'The REST API base for the post type (e.g. posts, pages).',
					'default'     => 'posts',
				),
				'fields'    => array(
					'type'        => 'object',
					'description' => 'AIOSEO fields to update. Supported: title, description, og_title, og_description, twitter_title, twitter_description, no_index (boolean), canonical_url.',
				),
			),
			'required'   => array( 'post_id', 'fields' ),
		);
	}

	public function execute( array $arguments ) {
		if ( ! function_exists( 'aioseo' ) ) {
			throw new \RuntimeException( 'All in One SEO (AIOSEO) is not active on this site. Please install and activate AIOSEO to use this tool.' );
		}

		$aioseo_instance = aioseo();
		$addon_active    = $aioseo_instance
			&& isset( $aioseo_instance->addons )
			&& method_exists( $aioseo_instance->addons, 'isAddonActive' )
			&& $aioseo_instance->addons->isAddonActive( 'aioseo-rest-api' );

		if ( ! $addon_active ) {
			
			$plan = 'free';
			if ( $aioseo_instance && isset( $aioseo_instance->license ) ) {
				$license = $aioseo_instance->license;
				if ( method_exists( $license, 'getLicenseLevel' ) ) {
					$plan = $license->getLicenseLevel() ?: 'free';
				} elseif ( isset( $license->level ) ) {
					$plan = $license->level ?: 'free';
				}
			}

			return array(
				'error'          => 'AIOSEO REST API write support requires AIOSEO Plus plan or higher with the REST API addon active. Read-only access is available on the free plan via wp_aioseo_get_post_seo.',
				'current_plan'   => strtolower( (string) $plan ),
				'requires_plan'  => 'plus',
				'requires_addon' => 'aioseo-rest-api',
				'skip_reason'    => 'plan_limitation',
			);
		}

		$this->validate_required( $arguments, array( 'post_id', 'fields' ) );

		$post_id   = $this->parse_required_id( $arguments['post_id'], 'post_id' );
		$post_type = ! empty( $arguments['post_type'] ) ? $this->validate_rest_route_segment( $arguments['post_type'], 'post_type' ) : 'posts';
		$fields    = $this->parse_json_param( $arguments['fields'], 'fields' );

		$data = $this->rest_request( 'POST', '/wp/v2/' . $post_type . '/' . $post_id, array( 'aioseo_meta_data' => $fields ) );

		return array(
			'post_id'          => $post_id,
			'aioseo_meta_data' => $data['aioseo_meta_data'] ?? array(),
		);
	}
}
