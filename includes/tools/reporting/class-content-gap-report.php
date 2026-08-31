<?php
namespace RankOut_Connector\Tools\Reporting;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Content_Gap_Report extends Base_Tool {

	public function get_name() {
		return 'wp_content_gap_report';
	}

	public function get_description() {
		return 'Compares a list of target topics/keywords against your existing published content to surface gaps — topics you have not covered yet. Scores existing posts by relevance to each topic (title + category + tag word overlap). Parameters: topics (required, array of strings), post_type (default "post"), threshold (0–100, minimum match score to count as "covered", default 30). Returns { covered: [{ topic, best_match_post_id, best_match_title, score }], gaps: [{ topic, reason }], coverage_pct }.';
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
			'required'   => array( 'topics' ),
			'properties' => array(
				'topics'    => array(
					'type'        => 'array',
					'description' => 'List of target topics or keywords to check coverage for.',
					'items'       => array( 'type' => 'string' ),
					'minItems'    => 1,
					'maxItems'    => 100,
				),
				'post_type' => array(
					'type'        => 'string',
					'description' => 'Post type to check against. Default: "post".',
				),
				'threshold' => array(
					'type'        => 'integer',
					'description' => 'Minimum relevance score (0–100) for a topic to be considered "covered". Default: 30.',
					'minimum'     => 0,
					'maximum'     => 100,
				),
			),
		);
	}

	public function execute( array $arguments ) {
		if ( empty( $arguments['topics'] ) || ! is_array( $arguments['topics'] ) ) {
			return array( 'error' => 'topics must be a non-empty array of strings.' );
		}

		$topics    = array_map( 'sanitize_text_field', $arguments['topics'] );
		$post_type = ! empty( $arguments['post_type'] ) ? sanitize_key( $arguments['post_type'] ) : 'post';
		$threshold = isset( $arguments['threshold'] ) ? min( 100, max( 0, (int) $arguments['threshold'] ) ) : 30;

		// Fetch all published posts with their terms.
		$posts = get_posts( array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		if ( is_wp_error( $posts ) || empty( $posts ) ) {
			return array(
				'post_type'    => $post_type,
				'total_posts'  => 0,
				'covered'      => array(),
				'gaps'         => array_map( fn( $t ) => array( 'topic' => $t, 'reason' => 'No published content found.' ), $topics ),
				'coverage_pct' => 0,
			);
		}

		// Build a searchable index: post_id => { title, term_string }.
		$index = array();
		foreach ( $posts as $pid ) {
			$title       = strtolower( get_the_title( $pid ) );
			$term_string = '';
			$taxonomies  = get_object_taxonomies( $post_type );
			foreach ( $taxonomies as $tax ) {
				$terms = get_the_terms( $pid, $tax );
				if ( $terms && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$term_string .= ' ' . strtolower( $term->name ) . ' ' . strtolower( $term->slug );
					}
				}
			}
			$index[ $pid ] = array(
				'searchable' => $title . ' ' . $term_string,
				'title'      => get_the_title( $pid ),
				'url'        => get_permalink( $pid ),
			);
		}

		$covered = array();
		$gaps    = array();

		foreach ( $topics as $topic ) {
			$topic_words = $this->tokenise( $topic );
			if ( empty( $topic_words ) ) {
				continue;
			}

			$best_pid   = null;
			$best_score = 0;

			foreach ( $index as $pid => $data ) {
				$score = $this->relevance_score( $topic_words, $data['searchable'] );
				if ( $score > $best_score ) {
					$best_score = $score;
					$best_pid   = $pid;
				}
			}

			if ( $best_score >= $threshold && $best_pid ) {
				$covered[] = array(
					'topic'             => $topic,
					'best_match_post_id' => $best_pid,
					'best_match_title'  => $index[ $best_pid ]['title'],
					'best_match_url'    => $index[ $best_pid ]['url'],
					'score'             => $best_score,
				);
			} else {
				$reason = $best_pid
					? sprintf( 'Closest match "%s" scored only %d (threshold: %d).', $index[ $best_pid ]['title'], $best_score, $threshold )
					: 'No matching content found.';
				$gaps[] = array(
					'topic'              => $topic,
					'reason'             => $reason,
					'closest_match'      => $best_pid ? $index[ $best_pid ]['title'] : null,
					'closest_score'      => $best_score,
				);
			}
		}

		$total        = count( $topics );
		$covered_cnt  = count( $covered );

		return array(
			'post_type'    => $post_type,
			'total_posts'  => count( $posts ),
			'total_topics' => $total,
			'threshold'    => $threshold,
			'coverage_pct' => $total > 0 ? round( ( $covered_cnt / $total ) * 100, 1 ) : 0,
			'covered_count' => $covered_cnt,
			'gap_count'    => count( $gaps ),
			'covered'      => $covered,
			'gaps'         => $gaps,
		);
	}

	private function tokenise( string $text ): array {
		$stop  = array( 'a', 'an', 'the', 'in', 'on', 'at', 'to', 'for', 'of', 'and', 'or', 'is', 'are', 'was', 'be', 'as', 'by', 'it', 'with', 'from', 'that', 'this', 'how', 'what', 'why', 'when', 'where', 'do', 'does', 'not', 'no' );
		$words = preg_split( '/\W+/', strtolower( $text ), -1, PREG_SPLIT_NO_EMPTY );
		return array_values( array_filter( $words, fn( $w ) => strlen( $w ) >= 3 && ! in_array( $w, $stop, true ) ) );
	}

	private function relevance_score( array $topic_words, string $searchable ): int {
		if ( empty( $topic_words ) ) {
			return 0;
		}
		$hits = 0;
		foreach ( $topic_words as $word ) {
			if ( false !== strpos( $searchable, $word ) ) {
				$hits++;
			}
		}
		return (int) round( ( $hits / count( $topic_words ) ) * 100 );
	}
}
