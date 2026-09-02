<?php
namespace RankOut_Connector\Tools\Reporting;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Master site-wide SEO audit aggregating Schema, GEO, AEO, and E-E-A-T signals.
 */
class Seo_Audit_Site extends Base_Tool {

	public function get_name() {
		return 'wp_seo_audit_site';
	}

	public function get_description() {
		return 'Runs a master site-wide SEO/GEO/AEO/E-E-A-T audit across your published content. Reports: schema coverage %, posts missing SEO title/meta, stale content count, E-E-A-T score distribution, AEO-ready count, GEO-ready count, top issues, and a prioritised fix list. Parameters: post_type (default "post"), limit (default 100, max 500). Returns { summary, distributions, top_issues, fix_priority }.';
	}

	public function get_category() {
		return 'reporting';
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
					'description' => 'Post type to audit. Default: "post".',
				),
				'limit'     => array(
					'type'        => 'integer',
					'description' => 'Max posts to scan (1–500). Default: 100.',
					'minimum'     => 1,
					'maximum'     => 500,
				),
			),
		);
	}

	public function execute( array $arguments ) {
		$post_type = ! empty( $arguments['post_type'] ) ? sanitize_key( $arguments['post_type'] ) : 'post';
		$limit     = isset( $arguments['limit'] ) ? min( max( 1, (int) $arguments['limit'] ), 500 ) : 100;

		$post_ids = get_posts( array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		) );

		$total = count( $post_ids );
		if ( 0 === $total ) {
			return array( 'error' => 'No published posts found for post_type: ' . $post_type );
		}

		$yoast_active    = class_exists( 'WPSEO_Options' );
		$rankmath_active = function_exists( 'rank_math' );
		$site_host       = wp_parse_url( home_url(), PHP_URL_HOST );
		$stale_threshold = time() - ( 365 * DAY_IN_SECONDS );

		// Counters.
		$schema_count         = 0;
		$missing_seo_title    = 0;
		$missing_seo_desc     = 0;
		$stale_count          = 0;
		$has_author_bio_count = 0;
		$aeo_ready_count      = 0;
		$geo_ready_count      = 0;
		$eeat_scores          = array();
		$grade_dist           = array( 'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0 );
		$word_counts          = array();

		// Issues accumulator: issue_key => [ label, count, post_ids[] ].
		$issues = array();

		foreach ( $post_ids as $pid ) {
			$post    = get_post( $pid );
			$html    = preg_replace( '/<!--.*?-->/s', '', $post->post_content );
			$author_id = (int) $post->post_author;

			// ---- Schema ----
			$has_schema = false;
			if ( get_post_meta( $pid, '_rankout_connector_schema', true ) ) {
				$has_schema = true;
			} elseif ( preg_match( '/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>/i', $html ) ) {
				$has_schema = true;
			} elseif ( $yoast_active && ( get_post_meta( $pid, '_yoast_wpseo_schema_page_type', true ) || get_post_meta( $pid, '_yoast_wpseo_schema_article_type', true ) ) ) {
				$has_schema = true;
			} elseif ( $rankmath_active && get_post_meta( $pid, 'rank_math_schema_type', true ) ) {
				$has_schema = true;
			}
			if ( $has_schema ) {
				$schema_count++;
			} else {
				$this->add_issue( $issues, 'missing_schema', 'Missing JSON-LD structured data', $pid );
			}

			// ---- SEO title / description ----
			$seo_title = '';
			$seo_desc  = '';
			if ( $yoast_active ) {
				$seo_title = get_post_meta( $pid, '_yoast_wpseo_title', true );
				$seo_desc  = get_post_meta( $pid, '_yoast_wpseo_metadesc', true );
			} elseif ( $rankmath_active ) {
				$seo_title = get_post_meta( $pid, 'rank_math_title', true );
				$seo_desc  = get_post_meta( $pid, 'rank_math_description', true );
			} else {
				$seo_title = get_the_title( $pid );
				$seo_desc  = has_excerpt( $pid ) ? get_the_excerpt( $pid ) : get_post_meta( $pid, '_yoast_wpseo_metadesc', true );
			}
			if ( empty( $seo_title ) ) {
				$missing_seo_title++;
				$this->add_issue( $issues, 'missing_seo_title', 'Missing SEO title', $pid );
			}
			if ( empty( $seo_desc ) ) {
				$missing_seo_desc++;
				$this->add_issue( $issues, 'missing_seo_desc', 'Missing meta description', $pid );
			}

			// ---- Content freshness ----
			if ( strtotime( $post->post_modified_gmt ) < $stale_threshold ) {
				$stale_count++;
				$this->add_issue( $issues, 'stale_content', 'Stale content (> 12 months old)', $pid );
			}

			// ---- Author bio ----
			$bio = trim( (string) get_user_meta( $author_id, 'description', true ) );
			if ( strlen( $bio ) >= 30 ) {
				$has_author_bio_count++;
			} else {
				$this->add_issue( $issues, 'missing_author_bio', 'Author missing bio', $pid );
			}

			// ---- AEO: FAQ content ----
			$has_faq = false !== strpos( $post->post_content, 'wp:yoast/faq-block' )
				|| false !== strpos( $post->post_content, 'wp:rank-math/faq-block' )
				|| false !== strpos( $post->post_content, 'FAQPage' )
				|| preg_match( '/<details[^>]*>/i', $html );
			if ( $has_faq ) {
				$aeo_ready_count++;
			}

			// ---- GEO: headings + schema + word count ----
			$words     = $this->word_count( $html );
			$word_counts[] = $words;
			$has_h2    = (bool) preg_match( '/<h[2-4][^>]*>/i', $html );
			$geo_ready = $has_schema && $has_h2 && $words >= 300;
			if ( $geo_ready ) {
				$geo_ready_count++;
			}

			// ---- E-E-A-T mini-score ----
			$eeat = 0;
			if ( strlen( $bio ) >= 30 )      { $eeat += 25; }
			if ( $has_schema )               { $eeat += 25; }
			$ext = 0;
			if ( preg_match_all( '/href=["\']([^"\'#][^"\']*)["\']/', $html, $lm ) ) {
				foreach ( $lm[1] as $url ) {
					$host = wp_parse_url( $url, PHP_URL_HOST );
					if ( $host && $host !== $site_host && false === strpos( $host, $site_host ) ) {
						$ext++;
					}
				}
			}
			if ( $ext >= 2 )                 { $eeat += 25; }
			if ( strtotime( $post->post_modified_gmt ) >= $stale_threshold ) { $eeat += 25; }
			$eeat_scores[] = $eeat;
			$grade = $this->grade( $eeat );
			$grade_dist[ $grade ]++;
		}

		// Sort issues by count descending.
		uasort( $issues, fn( $a, $b ) => $b['count'] <=> $a['count'] );

		$avg_eeat  = $total > 0 ? round( array_sum( $eeat_scores ) / $total, 1 ) : 0;
		$avg_words = $total > 0 ? (int) round( array_sum( $word_counts ) / $total ) : 0;

		// Fix priority: top 5 issues with highest counts.
		$fix_priority = array();
		$rank         = 1;
		foreach ( $issues as $key => $issue ) {
			if ( $rank > 5 ) {
				break;
			}
			$fix_priority[] = array(
				'rank'       => $rank++,
				'issue'      => $issue['label'],
				'count'      => $issue['count'],
				'impact'     => $rank <= 2 ? 'high' : ( $rank <= 4 ? 'medium' : 'low' ),
				'sample_ids' => array_slice( $issue['post_ids'], 0, 5 ),
			);
		}

		return array(
			'post_type'     => $post_type,
			'total_scanned' => $total,
			'summary'       => array(
				'schema_coverage_pct'   => round( ( $schema_count / $total ) * 100, 1 ),
				'schema_count'          => $schema_count,
				'missing_seo_title'     => $missing_seo_title,
				'missing_meta_desc'     => $missing_seo_desc,
				'stale_content_count'   => $stale_count,
				'author_bio_pct'        => round( ( $has_author_bio_count / $total ) * 100, 1 ),
				'aeo_ready_count'       => $aeo_ready_count,
				'aeo_ready_pct'         => round( ( $aeo_ready_count / $total ) * 100, 1 ),
				'geo_ready_count'       => $geo_ready_count,
				'geo_ready_pct'         => round( ( $geo_ready_count / $total ) * 100, 1 ),
				'avg_eeat_score'        => $avg_eeat,
				'avg_word_count'        => $avg_words,
			),
			'eeat_grade_distribution' => $grade_dist,
			'top_issues'    => array_values( array_map( fn( $i ) => array(
				'issue'      => $i['label'],
				'count'      => $i['count'],
				'sample_ids' => array_slice( $i['post_ids'], 0, 3 ),
			), $issues ) ),
			'fix_priority'  => $fix_priority,
		);
	}

	private function add_issue( array &$issues, string $key, string $label, int $pid ): void {
		if ( ! isset( $issues[ $key ] ) ) {
			$issues[ $key ] = array( 'label' => $label, 'count' => 0, 'post_ids' => array() );
		}
		$issues[ $key ]['count']++;
		$issues[ $key ]['post_ids'][] = $pid;
	}

	private function word_count( string $html ): int {
		$text = preg_replace( '/\s+/', ' ', trim( wp_strip_all_tags( $html ) ) );
		return '' === $text ? 0 : count( explode( ' ', $text ) );
	}

	private function grade( int $score ): string {
		if ( $score >= 90 ) return 'A';
		if ( $score >= 75 ) return 'B';
		if ( $score >= 60 ) return 'C';
		if ( $score >= 40 ) return 'D';
		return 'F';
	}
}
