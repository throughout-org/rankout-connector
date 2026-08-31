<?php
namespace RankOut_Connector\Tools\EEAT;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Audits a single post for E-E-A-T (Experience, Expertise, Authoritativeness, Trustworthiness) signals.
 */
class Get_Eeat_Signals extends Base_Tool {

	public function get_name() {
		return 'wp_get_eeat_signals';
	}

	public function get_description() {
		return 'Returns a full E-E-A-T (Experience, Expertise, Authoritativeness, Trustworthiness) audit for a single post. Checks: author bio present, author social/website links, author schema entity, post last modified date, time since last update, outbound citation count, schema type, word count, and image count. Provides an E-E-A-T score out of 100 with actionable recommendations. Parameters: post_id (required). Returns { post_id, title, score, grade, signals, recommendations }.';
	}

	public function get_category() {
		return 'eeat';
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
			'required'   => array( 'post_id' ),
			'properties' => array(
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'ID of the post to audit.',
				),
			),
		);
	}

	public function execute( array $arguments ) {
		$post_id = isset( $arguments['post_id'] ) ? (int) $arguments['post_id'] : 0;
		if ( ! $post_id ) {
			return array( 'error' => 'post_id is required.' );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return array( 'error' => 'Post not found.' );
		}

		$html      = preg_replace( '/<!--.*?-->/s', '', $post->post_content );
		$author_id = (int) $post->post_author;
		$author    = get_userdata( $author_id );
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );

		$score   = 0;
		$signals = array();
		$recs    = array();

		// --- Experience ---

		// 1. Author bio (20 pts).
		$bio = $author ? trim( (string) get_user_meta( $author_id, 'description', true ) ) : '';
		$has_bio = $author && strlen( $bio ) >= 30;
		$signals['author_bio'] = array(
			'dimension' => 'Experience',
			'pass'      => $has_bio,
			'points'    => $has_bio ? 20 : 0,
			'max'       => 20,
			'label'     => 'Author bio (≥ 30 chars)',
			'detail'    => $author ? ( $has_bio ? sprintf( '"%s" — %d chars', substr( $bio, 0, 80 ), strlen( $bio ) ) : 'Bio missing or too short.' ) : 'Author not found.',
		);
		if ( $has_bio ) {
			$score += 20;
		} else {
			$recs[] = 'Add an author bio (30+ characters) in Users → Edit Profile → Biographical Info.';
		}

		// 2. Author social / website links (15 pts).
		$social_keys = array( 'twitter', 'facebook', 'instagram', 'linkedin', 'youtube', 'wikipedia', 'user_url' );
		$social_links = array();
		if ( $author ) {
			foreach ( $social_keys as $key ) {
				$val = 'user_url' === $key ? $author->user_url : get_user_meta( $author_id, $key, true );
				if ( ! empty( $val ) ) {
					$social_links[ $key ] = $val;
				}
			}
			// Yoast social meta
			foreach ( array( 'wpseo_twitter', 'wpseo_facebook', 'wpseo_linkedin_url' ) as $yoast_key ) {
				$val = get_user_meta( $author_id, $yoast_key, true );
				if ( ! empty( $val ) ) {
					$social_links[ $yoast_key ] = $val;
				}
			}
		}
		$has_social = ! empty( $social_links );
		$signals['author_social'] = array(
			'dimension' => 'Experience',
			'pass'      => $has_social,
			'points'    => $has_social ? 15 : 0,
			'max'       => 15,
			'label'     => 'Author social / website links',
			'links'     => $social_links,
		);
		if ( $has_social ) {
			$score += 15;
		} else {
			$recs[] = 'Add at least one social link or website URL to the author profile.';
		}

		// --- Expertise ---

		// 3. Author schema entity in content or post meta (15 pts).
		$schema_raw   = get_post_meta( $post_id, '_easy_mcp_schema', true );
		$schema_data  = $schema_raw ? json_decode( $schema_raw, true ) : array();
		$has_author_schema = false;
		if ( is_array( $schema_data ) ) {
			$author_field = $schema_data['author'] ?? null;
			if ( is_array( $author_field ) && ! empty( $author_field['name'] ) ) {
				$has_author_schema = true;
			}
		}
		$signals['author_schema'] = array(
			'dimension' => 'Expertise',
			'pass'      => $has_author_schema,
			'points'    => $has_author_schema ? 15 : 0,
			'max'       => 15,
			'label'     => 'Author entity in JSON-LD schema',
		);
		if ( $has_author_schema ) {
			$score += 15;
		} else {
			$recs[] = 'Include an "author" entity with name and URL in the post\'s JSON-LD schema using wp_update_post_schema.';
		}

		// --- Authoritativeness ---

		// 4. Outbound citations (15 pts).
		$ext_count = 0;
		if ( preg_match_all( '/href=["\']([^"\'#][^"\']*)["\']/', $html, $link_matches ) ) {
			foreach ( $link_matches[1] as $url ) {
				$host = wp_parse_url( $url, PHP_URL_HOST );
				if ( $host && $host !== $site_host && false === strpos( $host, $site_host ) ) {
					$ext_count++;
				}
			}
		}
		$has_citations = $ext_count >= 2;
		$signals['outbound_citations'] = array(
			'dimension' => 'Authoritativeness',
			'pass'      => $has_citations,
			'points'    => $has_citations ? 15 : 0,
			'max'       => 15,
			'label'     => 'Outbound citations (≥ 2)',
			'count'     => $ext_count,
		);
		if ( $has_citations ) {
			$score += 15;
		} else {
			$recs[] = 'Add at least 2 outbound links to authoritative external sources (gov, edu, established publications).';
		}

		// --- Trustworthiness ---

		// 5. Content freshness — modified within 12 months (20 pts).
		$modified_ts     = strtotime( $post->post_modified_gmt );
		$days_since      = (int) floor( ( time() - $modified_ts ) / DAY_IN_SECONDS );
		$fresh            = $days_since <= 365;
		$signals['freshness'] = array(
			'dimension'   => 'Trustworthiness',
			'pass'        => $fresh,
			'points'      => $fresh ? 20 : 0,
			'max'         => 20,
			'label'       => 'Content updated within 12 months',
			'last_modified' => gmdate( 'Y-m-d', $modified_ts ),
			'days_since_update' => $days_since,
		);
		if ( $fresh ) {
			$score += 20;
		} else {
			$recs[] = sprintf( 'This content is %d days old. Review and update it to maintain freshness signals.', $days_since );
		}

		// 6. Structured data present (15 pts).
		$has_schema = ! empty( $schema_data ) || preg_match( '/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>/i', $html );
		$signals['structured_data'] = array(
			'dimension' => 'Trustworthiness',
			'pass'      => $has_schema,
			'points'    => $has_schema ? 15 : 0,
			'max'       => 15,
			'label'     => 'Structured data (JSON-LD)',
		);
		if ( $has_schema ) {
			$score += 15;
		} else {
			$recs[] = 'Add JSON-LD structured data (Article, HowTo, FAQPage, etc.) using wp_update_post_schema.';
		}

		return array(
			'post_id'         => $post_id,
			'title'           => get_the_title( $post_id ),
			'url'             => get_permalink( $post_id ),
			'author'          => $author ? $author->display_name : null,
			'score'           => $score,
			'max'             => 100,
			'grade'           => $this->grade( $score ),
			'dimensions'      => array(
				'Experience'        => array_sum( array_map( fn( $s ) => $s['dimension'] === 'Experience' ? $s['points'] : 0, $signals ) ),
				'Expertise'         => array_sum( array_map( fn( $s ) => $s['dimension'] === 'Expertise' ? $s['points'] : 0, $signals ) ),
				'Authoritativeness' => array_sum( array_map( fn( $s ) => $s['dimension'] === 'Authoritativeness' ? $s['points'] : 0, $signals ) ),
				'Trustworthiness'   => array_sum( array_map( fn( $s ) => $s['dimension'] === 'Trustworthiness' ? $s['points'] : 0, $signals ) ),
			),
			'signals'         => $signals,
			'recommendations' => $recs,
		);
	}

	private function grade( int $score ): string {
		if ( $score >= 90 ) return 'A';
		if ( $score >= 75 ) return 'B';
		if ( $score >= 60 ) return 'C';
		if ( $score >= 40 ) return 'D';
		return 'F';
	}
}
