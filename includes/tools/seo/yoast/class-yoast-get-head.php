<?php




namespace RankOut_Connector\Tools\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use RankOut_Connector\Tools\Base_Tool;

class Yoast_Get_Head extends Base_Tool {

	public function get_name() {
		return 'wp_yoast_get_head';
	}

	public function get_description() {
		return 'Gets the full rendered SEO head HTML and JSON-LD for any URL via the Yoast SEO REST endpoint. Useful for auditing SEO or populating headless CMS metadata. Returns the rendered head HTML and parsed JSON-LD schema.';
	}

	public function get_category() {
		return 'yoast-seo';
	}

	public function get_required_capability() {
		return 'edit_posts';
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
				'url' => array(
					'type'        => 'string',
					'description' => 'The URL to get SEO head data for.',
				),
			),
			'required'   => array( 'url' ),
		);
	}

	public function execute( array $arguments ) {
		if ( ! class_exists( 'WPSEO_Options' ) ) {
			throw new \RuntimeException( 'Yoast SEO is not active on this site. Please install and activate Yoast SEO to use this tool.' );
		}

		$this->validate_required( $arguments, array( 'url' ) );

		$params = array( 'url' => esc_url_raw( $arguments['url'] ) );

		return $this->rest_request( 'GET', '/yoast/v1/get_head', $params );
	}
}
