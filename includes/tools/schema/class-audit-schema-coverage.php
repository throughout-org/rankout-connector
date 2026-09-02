<?php
namespace RankOut_Connector\Tools\Schema;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Audit_Schema_Coverage extends Base_Tool {

	public function get_name() {
		return 'wp_audit_schema_coverage';
	}

	public function get_description() {
		return 'Audits structured data (schema.org) coverage across published posts. For each post, reports whether it has JSON-LD schema — from inline content, the plugin\'s own meta, Yoast SEO, or Rank Math. Parameters: post_type (default "post"), limit (default 50, max 200), offset (default 0). Returns { total_checked, with_schema, without_schema, coverage_percent, posts: [{ id, title, post_type, has_schema, schema_sources: [] }] }.';
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
				'post_type' => array(
					'type'        => 'string',
					'description' => 'Post type to audit (e.g. "post", "page"). Default: "post".',
				),
				'limit'     => array(
					'type'        => 'integer',
					'description' => 'Number of posts to check (max 200). Default: 50.',
					'minimum'     => 1,
					'maximum'     => 200,
				),
				'offset'    => array(
					'type'        => 'integer',
					'description' => 'Pagination offset. Default: 0.',
					'minimum'     => 0,
				),
			),
		);
	}

	public function execute( array $arguments ) {
		$post_type = ! empty( $arguments['post_type'] ) ? sanitize_key( $arguments['post_type'] ) : 'post';
		$limit     = isset( $arguments['limit'] ) ? min( (int) $arguments['limit'], 200 ) : 50;
		$limit     = max( $limit, 1 );
		$offset    = isset( $arguments['offset'] ) ? max( (int) $arguments['offset'], 0 ) : 0;

		$yoast_active     = class_exists( 'WPSEO_Options' );
		$rankmath_active  = function_exists( 'rank_math' );

		$posts = get_posts( array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'offset'         => $offset,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		) );

		$results      = array();
		$with_schema  = 0;

		foreach ( $posts as $post_id ) {
			$post    = get_post( $post_id );
			$sources = array();

			// Check inline content JSON-LD.
			if ( $post && preg_match( '/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>/i', $post->post_content ) ) {
				$sources[] = 'content';
			}

			// Check our own meta.
			if ( get_post_meta( $post_id, '_rankout_connector_schema', true ) ) {
				$sources[] = 'rankout_connector_schema';
			}

			// Check Yoast.
			if ( $yoast_active ) {
				$yoast_type = get_post_meta( $post_id, '_yoast_wpseo_schema_page_type', true );
				if ( ! $yoast_type ) {
					$yoast_type = get_post_meta( $post_id, '_yoast_wpseo_schema_article_type', true );
				}
				if ( $yoast_type ) {
					$sources[] = 'yoast';
				}
			}

			// Check Rank Math.
			if ( $rankmath_active ) {
				$rm_type = get_post_meta( $post_id, 'rank_math_schema_type', true );
				if ( $rm_type ) {
					$sources[] = 'rank_math';
				}
			}

			$has_schema = ! empty( $sources );
			if ( $has_schema ) {
				$with_schema++;
			}

			$results[] = array(
				'id'             => $post_id,
				'title'          => get_the_title( $post_id ),
				'url'            => get_permalink( $post_id ),
				'has_schema'     => $has_schema,
				'schema_sources' => $sources,
			);
		}

		$total_checked   = count( $results );
		$without_schema  = $total_checked - $with_schema;
		$coverage_pct    = $total_checked > 0 ? round( ( $with_schema / $total_checked ) * 100, 1 ) : 0;

		return array(
			'post_type'        => $post_type,
			'total_checked'    => $total_checked,
			'with_schema'      => $with_schema,
			'without_schema'   => $without_schema,
			'coverage_percent' => $coverage_pct,
			'offset'           => $offset,
			'posts'            => $results,
		);
	}
}
