<?php




namespace RankOut_Connector\Tools\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use RankOut_Connector\Tools\Base_Tool;

class Yoast_Get_Post_Seo extends Base_Tool {

	public function get_name() {
		return 'wp_yoast_get_post_seo';
	}

	public function get_description() {
		return 'Gets structured Yoast SEO metadata for a specific post or page: SEO title, meta description, focus keyword, robots settings, canonical URL, Open Graph fields, Twitter card fields, and schema data. Returns data from the yoast_head_json field appended by Yoast to WP REST responses.';
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
				'post_id'   => array(
					'type'        => 'integer',
					'description' => 'The ID of the post or page.',
				),
				'post_type' => array(
					'type'        => 'string',
					'description' => 'The REST API base for the post type (e.g. posts, pages).',
					'default'     => 'posts',
				),
			),
			'required'   => array( 'post_id' ),
		);
	}

	public function execute( array $arguments ) {
		if ( ! class_exists( 'WPSEO_Options' ) ) {
			throw new \RuntimeException( 'Yoast SEO is not active on this site. Please install and activate Yoast SEO to use this tool.' );
		}

		$post_id = $this->parse_required_id( $arguments['post_id'], 'post_id' );

		$post = get_post( $post_id );
		if ( ! $post ) {
			throw new \RuntimeException( 'Invalid post ID.' );
		}

		if ( ! empty( $arguments['post_type'] ) ) {
			$rest_base = $this->validate_rest_route_segment( $arguments['post_type'], 'post_type' );
		} else {
			$pt_obj    = get_post_type_object( $post->post_type );
			$rest_base = ( $pt_obj && ! empty( $pt_obj->rest_base ) ) ? $pt_obj->rest_base : null;
		}

		
		if ( $rest_base ) {
			try {
				$data = $this->rest_request( 'GET', '/wp/v2/' . $rest_base . '/' . $post_id );
				if ( ! empty( $data['yoast_head_json'] ) ) {
					return array(
						'post_id'         => $post_id,
						'yoast_head_json' => $data['yoast_head_json'],
					);
				}
			} catch ( \Exception $e ) {
				
			}
		}

		
		
		$meta_map = array(
			'seo_title'           => '_yoast_wpseo_title',
			'meta_description'    => '_yoast_wpseo_metadesc',
			'focus_keyword'       => '_yoast_wpseo_focuskw',
			'og_title'            => '_yoast_wpseo_opengraph-title',
			'og_description'      => '_yoast_wpseo_opengraph-description',
			'og_image'            => '_yoast_wpseo_opengraph-image',
			'twitter_title'       => '_yoast_wpseo_twitter-title',
			'twitter_description' => '_yoast_wpseo_twitter-description',
			'twitter_image'       => '_yoast_wpseo_twitter-image',
			'is_cornerstone'      => '_yoast_wpseo_is_cornerstone',
		);

		$yoast_data = array();
		foreach ( $meta_map as $field => $meta_key ) {
			$value = get_post_meta( $post_id, $meta_key, true );
			if ( '' !== $value ) {
				$yoast_data[ $field ] = $value;
			}
		}

		return array(
			'post_id'         => $post_id,
			'yoast_head_json' => $yoast_data,
		);
	}
}
