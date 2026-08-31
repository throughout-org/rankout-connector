<?php
namespace RankOut_Connector\Tools\AEO;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scores posts for Answer Engine Optimisation (AEO) readiness.
 *
 * Scoring rubric (100 pts total):
 *  25 — FAQ / Q&A content present
 *  20 — First paragraph is a concise direct answer (< 55 words)
 *  20 — Title is phrased as a question
 *  20 — H2/H3 headings phrased as questions
 *  15 — Bullet or numbered lists present (featured-snippet potential)
 */
class Audit_Answer_Readiness extends Base_Tool {

	const SCORE_FAQ          = 25;
	const SCORE_DIRECT_ANSWER = 20;
	const SCORE_QUESTION_TITLE = 20;
	const SCORE_QUESTION_HEADINGS = 20;
	const SCORE_LISTS        = 15;

	public function get_name() {
		return 'wp_audit_answer_readiness';
	}

	public function get_description() {
		return 'Scores posts on Answer Engine Optimisation (AEO) readiness out of 100. Checks: FAQ/Q&A content (25 pts), concise first paragraph ≤ 55 words (20 pts), title phrased as question (20 pts), H2/H3 headings as questions (20 pts), bullet/numbered lists (15 pts). Parameters: post_type (default "post"), limit (default 50, max 200), offset (default 0). Returns { site_score, total_checked, posts: [{ id, title, score, signals, quick_wins }] }.';
	}

	public function get_category() {
		return 'aeo';
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
					'description' => 'Number of posts to audit (1–200). Default: 50.',
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

		$post_ids = get_posts( array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'offset'         => $offset,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		) );

		$results   = array();
		$score_sum = 0;

		foreach ( $post_ids as $post_id ) {
			$post    = get_post( $post_id );
			$content = $post->post_content;
			$title   = get_the_title( $post_id );
			$html    = preg_replace( '/<!--.*?-->/s', '', $content );
			$signals = array();
			$score   = 0;

			// 1. FAQ / Q&A content (25 pts).
			$has_faq = $this->has_faq_content( $content, $html );
			$signals['faq_content'] = array(
				'pass'   => $has_faq,
				'points' => $has_faq ? self::SCORE_FAQ : 0,
				'max'    => self::SCORE_FAQ,
				'label'  => 'FAQ or Q&A blocks',
			);
			if ( $has_faq ) {
				$score += self::SCORE_FAQ;
			}

			// 2. Concise direct first paragraph ≤ 55 words (20 pts).
			$first_para_words = $this->first_paragraph_word_count( $html );
			$direct_answer    = $first_para_words > 0 && $first_para_words <= 55;
			$signals['direct_answer'] = array(
				'pass'        => $direct_answer,
				'points'      => $direct_answer ? self::SCORE_DIRECT_ANSWER : 0,
				'max'         => self::SCORE_DIRECT_ANSWER,
				'label'       => 'Concise opening paragraph (≤ 55 words)',
				'word_count'  => $first_para_words,
			);
			if ( $direct_answer ) {
				$score += self::SCORE_DIRECT_ANSWER;
			}

			// 3. Title phrased as a question (20 pts).
			$question_title = $this->is_question( $title );
			$signals['question_title'] = array(
				'pass'   => $question_title,
				'points' => $question_title ? self::SCORE_QUESTION_TITLE : 0,
				'max'    => self::SCORE_QUESTION_TITLE,
				'label'  => 'Title phrased as a question',
			);
			if ( $question_title ) {
				$score += self::SCORE_QUESTION_TITLE;
			}

			// 4. H2/H3 headings as questions (20 pts).
			$question_headings = $this->has_question_headings( $html );
			$signals['question_headings'] = array(
				'pass'   => $question_headings,
				'points' => $question_headings ? self::SCORE_QUESTION_HEADINGS : 0,
				'max'    => self::SCORE_QUESTION_HEADINGS,
				'label'  => 'H2/H3 headings phrased as questions',
			);
			if ( $question_headings ) {
				$score += self::SCORE_QUESTION_HEADINGS;
			}

			// 5. Bullet / numbered lists (15 pts).
			$has_lists = (bool) preg_match( '/<[ou]l[^>]*>/i', $html );
			$signals['lists'] = array(
				'pass'   => $has_lists,
				'points' => $has_lists ? self::SCORE_LISTS : 0,
				'max'    => self::SCORE_LISTS,
				'label'  => 'Bullet or numbered lists',
			);
			if ( $has_lists ) {
				$score += self::SCORE_LISTS;
			}

			$score_sum += $score;

			// Quick-win suggestions for failing signals.
			$quick_wins = array();
			if ( ! $has_faq ) {
				$quick_wins[] = 'Add a FAQ section using wp_create_faq_block.';
			}
			if ( ! $direct_answer ) {
				$quick_wins[] = 'Open with a concise 1–2 sentence direct answer (≤ 55 words) to capture featured snippets.';
			}
			if ( ! $question_title ) {
				$quick_wins[] = 'Rephrase the title as a question (e.g., "How to…?", "What is…?").';
			}
			if ( ! $question_headings ) {
				$quick_wins[] = 'Rephrase at least one H2/H3 as a question to target "People also ask" boxes.';
			}
			if ( ! $has_lists ) {
				$quick_wins[] = 'Add a bulleted list or numbered steps to improve featured-snippet eligibility.';
			}

			$results[] = array(
				'id'         => $post_id,
				'title'      => $title,
				'url'        => get_permalink( $post_id ),
				'score'      => $score,
				'max'        => 100,
				'grade'      => $this->grade( $score ),
				'signals'    => $signals,
				'quick_wins' => $quick_wins,
			);
		}

		$total      = count( $results );
		$site_score = $total > 0 ? round( $score_sum / $total, 1 ) : 0;

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
				'faq_content'       => self::SCORE_FAQ,
				'direct_answer'     => self::SCORE_DIRECT_ANSWER,
				'question_title'    => self::SCORE_QUESTION_TITLE,
				'question_headings' => self::SCORE_QUESTION_HEADINGS,
				'lists'             => self::SCORE_LISTS,
			),
			'posts'         => $results,
		);
	}

	private function has_faq_content( string $raw, string $html ): bool {
		if ( false !== strpos( $raw, 'wp:yoast/faq-block' ) ) {
			return true;
		}
		if ( false !== strpos( $raw, 'wp:rank-math/faq-block' ) ) {
			return true;
		}
		if ( preg_match( '/<details[^>]*>/i', $html ) ) {
			return true;
		}
		if ( preg_match( '/FAQPage/i', $raw ) ) {
			return true;
		}
		return false;
	}

	private function first_paragraph_word_count( string $html ): int {
		if ( ! preg_match( '/<p[^>]*>(.*?)<\/p>/si', $html, $m ) ) {
			return 0;
		}
		$text  = trim( wp_strip_all_tags( $m[1] ) );
		$text  = preg_replace( '/\s+/', ' ', $text );
		if ( '' === $text ) {
			return 0;
		}
		return count( explode( ' ', $text ) );
	}

	private function is_question( string $title ): bool {
		$lower = strtolower( trim( $title ) );
		if ( '?' === substr( $lower, -1 ) ) {
			return true;
		}
		foreach ( array( 'how ', 'what ', 'why ', 'when ', 'where ', 'which ', 'who ', 'can ', 'does ', 'is ', 'are ', 'do ', 'should ', 'could ', 'will ' ) as $kw ) {
			if ( 0 === strpos( $lower, $kw ) ) {
				return true;
			}
		}
		return false;
	}

	private function has_question_headings( string $html ): bool {
		if ( ! preg_match_all( '/<h[23][^>]*>(.*?)<\/h[23]>/si', $html, $m ) ) {
			return false;
		}
		foreach ( $m[1] as $text ) {
			if ( $this->is_question( wp_strip_all_tags( $text ) ) ) {
				return true;
			}
		}
		return false;
	}

	private function grade( int $score ): string {
		if ( $score >= 90 ) return 'A';
		if ( $score >= 75 ) return 'B';
		if ( $score >= 60 ) return 'C';
		if ( $score >= 40 ) return 'D';
		return 'F';
	}
}
