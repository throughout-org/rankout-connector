<?php
namespace RankOut_Connector\Tools\GEO;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scores posts on Generative Engine Optimisation (GEO) readiness.
 *
 * Scoring rubric (100 pts total):
 *  25  — JSON-LD schema present (from content, our meta, Yoast, or Rank Math)
 *  20  — Author entity: display name + bio description
 *  15  — Heading structure (H2/H3 present)
 *  15  — External citations (links to authoritative sources)
 *  10  — Internal links (connects to other content on the site)
 *  10  — Minimum content depth (≥ 300 words)
 *   5  — Featured image with alt text
 */
class Audit_Geo_Readiness extends Base_Tool {

	const SCORE_SCHEMA         = 25;
	const SCORE_AUTHOR_ENTITY  = 20;
	const SCORE_HEADINGS       = 15;
	const SCORE_EXTERNAL_CITES = 15;
	const SCORE_INTERNAL_LINKS = 10;
	const SCORE_WORD_COUNT     = 10;
	const SCORE_IMAGE_ALT      = 5;

	public function get_name() {
		return 'wp_audit_geo_readiness';
	}

	public function get_description() {
		return 'Scores published posts on Generative Engine Optimisation (GEO) readiness out of 100. Checks: JSON-LD schema (25 pts), author entity with bio (20 pts), H2/H3 heading structure (15 pts), external citations (15 pts), internal links (10 pts), word count ≥ 300 (10 pts), featured image with alt text (5 pts). Parameters: post_type (default "post"), limit (default 50, max 200), offset (default 0). Returns { site_score, total_checked, posts: [{ id, title, score, max: 100, signals }] }.';
	}

	public function get_category() {
		return 'geo';
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
					'description' => 'Number of posts to check (1–200). Default: 50.',
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
		$post_type       = ! empty( $arguments['post_type'] ) ? sanitize_key( $arguments['post_type'] ) : 'post';
		$limit           = isset( $arguments['limit'] ) ? min( (int) $arguments['limit'], 200 ) : 50;
		$limit           = max( $limit, 1 );
		$offset          = isset( $arguments['offset'] ) ? max( (int) $arguments['offset'], 0 ) : 0;

		$yoast_active    = class_exists( 'WPSEO_Options' );
		$rankmath_active = function_exists( 'rank_math' );
		$site_host       = wp_parse_url( home_url(), PHP_URL_HOST );

		$post_ids = get_posts( array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'offset'         => $offset,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		) );

		$results    = array();
		$score_sum  = 0;

		foreach ( $post_ids as $post_id ) {
			$post    = get_post( $post_id );
			$html    = preg_replace( '/<!--.*?-->/s', '', $post->post_content );
			$signals = array();
			$score   = 0;

			// 1. JSON-LD schema (25 pts).
			$has_schema = $this->has_schema( $post_id, $html, $yoast_active, $rankmath_active );
			$signals['schema'] = array(
				'pass'    => $has_schema,
				'points'  => $has_schema ? self::SCORE_SCHEMA : 0,
				'max'     => self::SCORE_SCHEMA,
				'label'   => 'JSON-LD structured data',
			);
			if ( $has_schema ) {
				$score += self::SCORE_SCHEMA;
			}

			// 2. Author entity (20 pts).
			$author_data   = $this->author_has_entity( (int) $post->post_author );
			$has_author    = $author_data['pass'];
			$signals['author_entity'] = array(
				'pass'    => $has_author,
				'points'  => $has_author ? self::SCORE_AUTHOR_ENTITY : 0,
				'max'     => self::SCORE_AUTHOR_ENTITY,
				'label'   => 'Author with bio',
				'detail'  => $author_data['detail'],
			);
			if ( $has_author ) {
				$score += self::SCORE_AUTHOR_ENTITY;
			}

			// 3. Heading structure (15 pts).
			$has_headings    = (bool) preg_match( '/<h[2-4][^>]*>/i', $html );
			$signals['headings'] = array(
				'pass'   => $has_headings,
				'points' => $has_headings ? self::SCORE_HEADINGS : 0,
				'max'    => self::SCORE_HEADINGS,
				'label'  => 'H2/H3 heading structure',
			);
			if ( $has_headings ) {
				$score += self::SCORE_HEADINGS;
			}

			// 4. External citations (15 pts).
			$ext_count        = $this->count_external_links( $html, $site_host );
			$has_ext          = $ext_count > 0;
			$signals['external_citations'] = array(
				'pass'   => $has_ext,
				'points' => $has_ext ? self::SCORE_EXTERNAL_CITES : 0,
				'max'    => self::SCORE_EXTERNAL_CITES,
				'label'  => 'External citations',
				'count'  => $ext_count,
			);
			if ( $has_ext ) {
				$score += self::SCORE_EXTERNAL_CITES;
			}

			// 5. Internal links (10 pts).
			$int_count        = $this->count_internal_links( $html, $site_host );
			$has_int          = $int_count > 0;
			$signals['internal_links'] = array(
				'pass'   => $has_int,
				'points' => $has_int ? self::SCORE_INTERNAL_LINKS : 0,
				'max'    => self::SCORE_INTERNAL_LINKS,
				'label'  => 'Internal links',
				'count'  => $int_count,
			);
			if ( $has_int ) {
				$score += self::SCORE_INTERNAL_LINKS;
			}

			// 6. Word count ≥ 300 (10 pts).
			$words      = $this->word_count( $html );
			$has_depth  = $words >= 300;
			$signals['word_count'] = array(
				'pass'   => $has_depth,
				'points' => $has_depth ? self::SCORE_WORD_COUNT : 0,
				'max'    => self::SCORE_WORD_COUNT,
				'label'  => 'Content depth (≥ 300 words)',
				'count'  => $words,
			);
			if ( $has_depth ) {
				$score += self::SCORE_WORD_COUNT;
			}

			// 7. Featured image with alt text (5 pts).
			$img_signal        = $this->featured_image_alt( $post_id );
			$signals['image_alt'] = array(
				'pass'   => $img_signal,
				'points' => $img_signal ? self::SCORE_IMAGE_ALT : 0,
				'max'    => self::SCORE_IMAGE_ALT,
				'label'  => 'Featured image with alt text',
			);
			if ( $img_signal ) {
				$score += self::SCORE_IMAGE_ALT;
			}

			$score_sum += $score;

			$results[] = array(
				'id'      => $post_id,
				'title'   => get_the_title( $post_id ),
				'url'     => get_permalink( $post_id ),
				'score'   => $score,
				'max'     => 100,
				'grade'   => $this->grade( $score ),
				'signals' => $signals,
			);
		}

		$total       = count( $results );
		$site_score  = $total > 0 ? round( $score_sum / $total, 1 ) : 0;

		// Sort worst-first so the AI can prioritise fixes.
		usort( $results, function ( $a, $b ) {
			return $a['score'] <=> $b['score'];
		} );

		return array(
			'post_type'     => $post_type,
			'site_score'    => $site_score,
			'site_grade'    => $this->grade( (int) round( $site_score ) ),
			'total_checked' => $total,
			'offset'        => $offset,
			'scoring'       => array(
				'schema'             => self::SCORE_SCHEMA,
				'author_entity'      => self::SCORE_AUTHOR_ENTITY,
				'headings'           => self::SCORE_HEADINGS,
				'external_citations' => self::SCORE_EXTERNAL_CITES,
				'internal_links'     => self::SCORE_INTERNAL_LINKS,
				'word_count'         => self::SCORE_WORD_COUNT,
				'image_alt'          => self::SCORE_IMAGE_ALT,
			),
			'posts'         => $results,
		);
	}

	private function has_schema( int $post_id, string $html, bool $yoast, bool $rankmath ): bool {
		if ( get_post_meta( $post_id, '_rankout_connector_schema', true ) ) {
			return true;
		}
		if ( preg_match( '/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>/i', $html ) ) {
			return true;
		}
		if ( $yoast ) {
			$type = get_post_meta( $post_id, '_yoast_wpseo_schema_page_type', true )
				 ?: get_post_meta( $post_id, '_yoast_wpseo_schema_article_type', true );
			if ( $type ) {
				return true;
			}
		}
		if ( $rankmath && get_post_meta( $post_id, 'rank_math_schema_type', true ) ) {
			return true;
		}
		return false;
	}

	private function author_has_entity( int $user_id ): array {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return array( 'pass' => false, 'detail' => 'Author not found.' );
		}
		$bio = get_user_meta( $user_id, 'description', true );
		if ( empty( $bio ) ) {
			return array( 'pass' => false, 'detail' => sprintf( 'Author "%s" has no bio/description.', $user->display_name ) );
		}
		return array( 'pass' => true, 'detail' => sprintf( 'Author "%s" has a bio.', $user->display_name ) );
	}

	private function count_external_links( string $html, string $site_host ): int {
		if ( ! preg_match_all( '/href=["\']([^"\'#][^"\']*)["\']/', $html, $matches ) ) {
			return 0;
		}
		$count = 0;
		foreach ( $matches[1] as $url ) {
			$host = wp_parse_url( $url, PHP_URL_HOST );
			if ( $host && $host !== $site_host && false === strpos( $host, $site_host ) ) {
				$count++;
			}
		}
		return $count;
	}

	private function count_internal_links( string $html, string $site_host ): int {
		if ( ! preg_match_all( '/href=["\']([^"\'#][^"\']*)["\']/', $html, $matches ) ) {
			return 0;
		}
		$count = 0;
		foreach ( $matches[1] as $url ) {
			$host = wp_parse_url( $url, PHP_URL_HOST );
			$is_internal = ( '' === (string) $host ) || ( $host === $site_host ) || ( false !== strpos( (string) $host, $site_host ) );
			if ( $is_internal ) {
				$count++;
			}
		}
		return $count;
	}

	private function word_count( string $html ): int {
		$text = wp_strip_all_tags( $html );
		$text = preg_replace( '/\s+/', ' ', trim( $text ) );
		if ( '' === $text ) {
			return 0;
		}
		return count( explode( ' ', $text ) );
	}

	private function featured_image_alt( int $post_id ): bool {
		$thumb_id = get_post_thumbnail_id( $post_id );
		if ( ! $thumb_id ) {
			return false;
		}
		$alt = get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
		return ! empty( $alt );
	}

	private function grade( int $score ): string {
		if ( $score >= 90 ) return 'A';
		if ( $score >= 75 ) return 'B';
		if ( $score >= 60 ) return 'C';
		if ( $score >= 40 ) return 'D';
		return 'F';
	}
}
