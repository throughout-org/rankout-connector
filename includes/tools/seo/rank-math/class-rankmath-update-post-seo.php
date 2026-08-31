<?php




namespace RankOut_Connector\Tools\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use RankOut_Connector\Tools\Base_Tool;

class Rankmath_Update_Post_Seo extends Base_Tool {

	public function get_name() {
		return 'wp_rm_update_post_seo';
	}

	public function get_description() {
		return 'Updates Rank Math SEO fields on a post by writing to the Rank Math postmeta keys. Fields: title (rank_math_title), description (rank_math_description), focus_keyword (rank_math_focus_keyword), canonical_url (rank_math_canonical_url), facebook_title, facebook_description, facebook_image, twitter_title, twitter_description, twitter_image. Note: rank_math_robots is not supported as it uses serialized array format.';
	}

	public function get_category() {
		return 'rank-math';
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
				'post_id'              => array(
					'type'        => 'integer',
					'description' => 'The ID of the post.',
				),
				'title'                => array(
					'type'        => 'string',
					'description' => 'The SEO title (rank_math_title).',
				),
				'description'          => array(
					'type'        => 'string',
					'description' => 'The meta description (rank_math_description).',
				),
				'focus_keyword'        => array(
					'type'        => 'string',
					'description' => 'The focus keyword (rank_math_focus_keyword).',
				),
				'canonical_url'        => array(
					'type'        => 'string',
					'description' => 'The canonical URL (rank_math_canonical_url).',
				),
				'facebook_title'       => array(
					'type'        => 'string',
					'description' => 'The Facebook/Open Graph title (rank_math_facebook_title).',
				),
				'facebook_description' => array(
					'type'        => 'string',
					'description' => 'The Facebook/Open Graph description (rank_math_facebook_description).',
				),
				'twitter_title'        => array(
					'type'        => 'string',
					'description' => 'The Twitter card title (rank_math_twitter_title).',
				),
				'twitter_description'  => array(
					'type'        => 'string',
					'description' => 'The Twitter card description (rank_math_twitter_description).',
				),
				'facebook_image'       => array(
					'type'        => 'string',
					'description' => 'The Facebook/Open Graph image URL (rank_math_facebook_image).',
				),
				'twitter_image'        => array(
					'type'        => 'string',
					'description' => 'The Twitter card image URL (rank_math_twitter_image).',
				),
			),
			'required'   => array( 'post_id' ),
		);
	}

	public function execute( array $arguments ) {
		if ( ! function_exists( 'rank_math' ) ) {
			throw new \RuntimeException( 'Rank Math SEO is not active on this site. Please install and activate Rank Math SEO to use this tool.' );
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
			'title'                => 'rank_math_title',
			'description'          => 'rank_math_description',
			'focus_keyword'        => 'rank_math_focus_keyword',
			'canonical_url'        => 'rank_math_canonical_url',
			'facebook_title'       => 'rank_math_facebook_title',
			'facebook_description' => 'rank_math_facebook_description',
			'twitter_title'        => 'rank_math_twitter_title',
			'twitter_description'  => 'rank_math_twitter_description',
			'facebook_image'       => 'rank_math_facebook_image',
			'twitter_image'        => 'rank_math_twitter_image',
		);

		$url_fields = array( 'canonical_url', 'facebook_image', 'twitter_image' );

		$updated = array();

		foreach ( $meta_map as $arg_key => $meta_key ) {
			if ( isset( $arguments[ $arg_key ] ) ) {
				$value = in_array( $arg_key, $url_fields, true )
					? esc_url_raw( $arguments[ $arg_key ] )
					: sanitize_text_field( $arguments[ $arg_key ] );
				if ( false !== update_post_meta( $post_id, $meta_key, $value ) ) {
					$updated[] = $arg_key;
				}
			}
		}

		return array(
			'post_id'        => $post_id,
			'updated_fields' => $updated,
		);
	}
}
