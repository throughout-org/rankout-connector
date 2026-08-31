<?php




namespace RankOut_Connector\Tools\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use RankOut_Connector\Tools\Base_Tool;

class Yoast_Update_Post_Seo extends Base_Tool {

	public function get_name() {
		return 'wp_yoast_update_post_seo';
	}

	public function get_description() {
		return 'Updates Yoast SEO metadata on a post or page by writing to the Yoast postmeta keys directly. Fields: seo_title, meta_description, focus_keyword, is_cornerstone, og_title, og_description, og_image, twitter_title, twitter_description, twitter_image.';
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
			'readOnlyHint'    => false,
			'destructiveHint' => false,
			'openWorldHint'   => false,
		);
	}

	public function get_input_schema() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id'             => array(
					'type'        => 'integer',
					'description' => 'The ID of the post or page.',
				),
				'seo_title'           => array(
					'type'        => 'string',
					'description' => 'The SEO title (_yoast_wpseo_title).',
				),
				'meta_description'    => array(
					'type'        => 'string',
					'description' => 'The meta description (_yoast_wpseo_metadesc).',
				),
				'focus_keyword'       => array(
					'type'        => 'string',
					'description' => 'The focus keyword (_yoast_wpseo_focuskw).',
				),
				'is_cornerstone'      => array(
					'type'        => 'boolean',
					'description' => 'Whether this is cornerstone content (_yoast_wpseo_is_cornerstone).',
				),
				'og_title'            => array(
					'type'        => 'string',
					'description' => 'The Open Graph title (_yoast_wpseo_opengraph-title).',
				),
				'og_description'      => array(
					'type'        => 'string',
					'description' => 'The Open Graph description (_yoast_wpseo_opengraph-description).',
				),
				'twitter_title'       => array(
					'type'        => 'string',
					'description' => 'The Twitter card title (_yoast_wpseo_twitter-title).',
				),
				'twitter_description' => array(
					'type'        => 'string',
					'description' => 'The Twitter card description (_yoast_wpseo_twitter-description).',
				),
				'og_image'            => array(
					'type'        => 'string',
					'description' => 'The Open Graph image URL (_yoast_wpseo_opengraph-image).',
				),
				'twitter_image'       => array(
					'type'        => 'string',
					'description' => 'The Twitter card image URL (_yoast_wpseo_twitter-image).',
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
			throw new \InvalidArgumentException( 'Post not found.' );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			throw new \RuntimeException( 'You do not have permission to edit this post.' );
		}

		$meta_map = array(
			'seo_title'           => '_yoast_wpseo_title',
			'meta_description'    => '_yoast_wpseo_metadesc',
			'focus_keyword'       => '_yoast_wpseo_focuskw',
			'og_title'            => '_yoast_wpseo_opengraph-title',
			'og_description'      => '_yoast_wpseo_opengraph-description',
			'twitter_title'       => '_yoast_wpseo_twitter-title',
			'twitter_description' => '_yoast_wpseo_twitter-description',
		);

		$url_meta_map = array(
			'og_image'      => '_yoast_wpseo_opengraph-image',
			'twitter_image' => '_yoast_wpseo_twitter-image',
		);

		$updated = array();

		foreach ( $meta_map as $arg_key => $meta_key ) {
			if ( isset( $arguments[ $arg_key ] ) ) {
				if ( false !== update_post_meta( $post_id, $meta_key, sanitize_text_field( $arguments[ $arg_key ] ) ) ) {
					$updated[] = $arg_key;
				}
			}
		}

		foreach ( $url_meta_map as $arg_key => $meta_key ) {
			if ( isset( $arguments[ $arg_key ] ) ) {
				if ( false !== update_post_meta( $post_id, $meta_key, esc_url_raw( $arguments[ $arg_key ] ) ) ) {
					$updated[] = $arg_key;
				}
			}
		}

		if ( isset( $arguments['is_cornerstone'] ) ) {
			update_post_meta( $post_id, '_yoast_wpseo_is_cornerstone', (bool) $arguments['is_cornerstone'] ? '1' : '' );
			$updated[] = 'is_cornerstone';
		}

		return array(
			'post_id'        => $post_id,
			'updated_fields' => $updated,
		);
	}
}
