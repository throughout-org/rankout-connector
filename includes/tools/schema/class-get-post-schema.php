<?php
namespace RankOut_Connector\Tools\Schema;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Get_Post_Schema extends Base_Tool {

	public function get_name() {
		return 'wp_get_post_schema';
	}

	public function get_description() {
		return 'Extracts all JSON-LD structured data (schema.org) attached to a post or page. Checks three sources: (1) inline <script type="application/ld+json"> blocks inside post content, (2) schema stored via wp_update_post_schema in post meta, (3) schema injected by active SEO plugins (Yoast, Rank Math). Parameters: post_id. Returns { post_id, sources: { content: [], meta: object|null, yoast: object|null, rank_math: string|null }, all_schemas: [] }.';
	}

	public function get_category() {
		return 'schema';
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
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'The ID of the post or page to inspect.',
				),
			),
			'required'   => array( 'post_id' ),
		);
	}

	public function execute( array $arguments ) {
		$post_id = $this->parse_required_id( $arguments['post_id'], 'post_id' );

		$post = get_post( $post_id );
		if ( ! $post ) {
			throw new \RuntimeException( 'Post not found.' );
		}

		$all_schemas = array();
		$sources     = array(
			'content'   => array(),
			'meta'      => null,
			'yoast'     => null,
			'rank_math' => null,
		);

		// 1. Inline JSON-LD in post content.
		$content = $post->post_content;
		if ( preg_match_all( '/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $content, $matches ) ) {
			foreach ( $matches[1] as $raw ) {
				$decoded = json_decode( trim( $raw ), true );
				if ( $decoded ) {
					$sources['content'][] = $decoded;
					$all_schemas[]        = $decoded;
				}
			}
		}

		// 2. Schema stored via wp_update_post_schema (our own meta).
		$meta_raw = get_post_meta( $post_id, '_easy_mcp_schema', true );
		if ( ! empty( $meta_raw ) ) {
			$decoded = json_decode( $meta_raw, true );
			if ( $decoded ) {
				$sources['meta'] = $decoded;
				$all_schemas[]   = $decoded;
			}
		}

		// 3. Yoast SEO schema (if active).
		if ( class_exists( 'WPSEO_Options' ) ) {
			try {
				$pt_obj    = get_post_type_object( $post->post_type );
				$rest_base = ( $pt_obj && ! empty( $pt_obj->rest_base ) ) ? $pt_obj->rest_base : null;
				if ( $rest_base ) {
					$data = $this->rest_request( 'GET', '/wp/v2/' . $rest_base . '/' . $post_id );
					if ( ! empty( $data['yoast_head_json']['schema'] ) ) {
						$sources['yoast'] = $data['yoast_head_json']['schema'];
						$all_schemas[]    = $data['yoast_head_json']['schema'];
					}
				}
			} catch ( \Exception $e ) {
				// Yoast not exposing schema via REST — skip.
			}
		}

		// 4. Rank Math schema type (if active).
		if ( function_exists( 'rank_math' ) ) {
			$rm_type = get_post_meta( $post_id, 'rank_math_schema_type', true );
			if ( $rm_type ) {
				$sources['rank_math'] = $rm_type;
			}
		}

		return array(
			'post_id'     => $post_id,
			'post_type'   => $post->post_type,
			'title'       => get_the_title( $post_id ),
			'sources'     => $sources,
			'all_schemas' => $all_schemas,
			'has_schema'  => ! empty( $all_schemas ) || ! empty( $sources['rank_math'] ),
		);
	}
}
