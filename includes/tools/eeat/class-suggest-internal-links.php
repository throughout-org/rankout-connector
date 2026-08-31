<?php
namespace RankOut_Connector\Tools\EEAT;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Suggest_Internal_Links extends Base_Tool {

	public function get_name() {
		return 'wp_suggest_internal_links';
	}

	public function get_description() {
		return 'Finds related posts that either (a) should link TO the given post, or (b) the given post should link TO. Matches by shared taxonomy terms (categories, tags) and word overlap between titles. Does not modify any content — returns suggestions only. Parameters: post_id (required), limit (default 10, max 50). Returns { post_id, title, suggestions_to_link_here: [...], suggestions_to_link_from_here: [...] }.';
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
					'description' => 'ID of the post to find linking opportunities for.',
				),
				'limit'   => array(
					'type'        => 'integer',
					'description' => 'Max suggestions per direction (1–50). Default: 10.',
					'minimum'     => 1,
					'maximum'     => 50,
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

		$limit     = isset( $arguments['limit'] ) ? min( max( 1, (int) $arguments['limit'] ), 50 ) : 10;
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );

		// Collect term IDs attached to this post.
		$taxonomies = get_object_taxonomies( $post->post_type );
		$term_ids   = array();
		foreach ( $taxonomies as $tax ) {
			$terms = get_the_terms( $post_id, $tax );
			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$term_ids[] = $term->term_id;
				}
			}
		}

		// Find posts sharing terms (potential linkers / link targets).
		$related_ids = array();
		if ( ! empty( $term_ids ) ) {
			$related = get_posts( array(
				'post_type'      => $post->post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'post__not_in'   => array( $post_id ),
				'tax_query'      => array(
					array(
						'taxonomy' => count( $taxonomies ) === 1 ? $taxonomies[0] : 'category',
						'field'    => 'term_id',
						'terms'    => $term_ids,
					),
				),
				'fields'         => 'ids',
			) );
			if ( ! is_wp_error( $related ) ) {
				$related_ids = $related;
			}
		}

		// Also find recent posts from same post type for title overlap matching.
		$recent_ids = get_posts( array(
			'post_type'      => $post->post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'post__not_in'   => array( $post_id ),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		) );
		if ( is_wp_error( $recent_ids ) ) {
			$recent_ids = array();
		}

		$candidate_ids = array_unique( array_merge( $related_ids, $recent_ids ) );

		$source_title_words = $this->title_words( get_the_title( $post_id ) );
		$source_content     = preg_replace( '/<!--.*?-->/s', '', $post->post_content );

		// Links already in source post.
		$existing_links = array();
		if ( preg_match_all( '/href=["\']([^"\'#][^"\']*)["\']/', $source_content, $lm ) ) {
			foreach ( $lm[1] as $url ) {
				$tid = url_to_postid( $url );
				if ( $tid ) {
					$existing_links[ $tid ] = true;
				}
			}
		}

		$to_link_here    = array();
		$to_link_from_here = array();

		foreach ( $candidate_ids as $cid ) {
			$cpost         = get_post( $cid );
			$ctitle        = get_the_title( $cid );
			$ctitle_words  = $this->title_words( $ctitle );
			$overlap       = count( array_intersect( $source_title_words, $ctitle_words ) );
			$score         = $overlap;

			if ( $overlap === 0 && ! in_array( $cid, $related_ids, true ) ) {
				continue;
			}

			$candidate_content = preg_replace( '/<!--.*?-->/s', '', $cpost->post_content );

			// Does the candidate already link TO our post?
			$candidate_already_links = false;
			if ( preg_match_all( '/href=["\']([^"\'#][^"\']*)["\']/', $candidate_content, $clm ) ) {
				foreach ( $clm[1] as $url ) {
					if ( url_to_postid( $url ) === $post_id ) {
						$candidate_already_links = true;
						break;
					}
				}
			}

			if ( ! $candidate_already_links ) {
				// Does candidate content mention any source title words? Boost score.
				foreach ( $source_title_words as $word ) {
					if ( strlen( $word ) >= 4 && false !== stripos( $candidate_content, $word ) ) {
						$score++;
					}
				}
				if ( $score > 0 ) {
					$to_link_here[] = array(
						'id'          => $cid,
						'title'       => $ctitle,
						'url'         => get_permalink( $cid ),
						'score'       => $score,
						'reason'      => 'Shares topics with this post and doesn\'t link to it yet.',
					);
				}
			}

			// Does our source post already link TO candidate?
			if ( ! isset( $existing_links[ $cid ] ) ) {
				$candidate_score = $overlap;
				foreach ( $ctitle_words as $word ) {
					if ( strlen( $word ) >= 4 && false !== stripos( $source_content, $word ) ) {
						$candidate_score++;
					}
				}
				if ( $candidate_score > 0 ) {
					$to_link_from_here[] = array(
						'id'     => $cid,
						'title'  => $ctitle,
						'url'    => get_permalink( $cid ),
						'score'  => $candidate_score,
						'reason' => 'Related content not yet linked from this post.',
					);
				}
			}
		}

		// Sort by score descending and cap at limit.
		usort( $to_link_here, fn( $a, $b ) => $b['score'] <=> $a['score'] );
		usort( $to_link_from_here, fn( $a, $b ) => $b['score'] <=> $a['score'] );

		return array(
			'post_id'                  => $post_id,
			'title'                    => get_the_title( $post_id ),
			'url'                      => get_permalink( $post_id ),
			'suggestions_to_link_here' => array_slice( $to_link_here, 0, $limit ),
			'suggestions_to_link_from_here' => array_slice( $to_link_from_here, 0, $limit ),
		);
	}

	private function title_words( string $title ): array {
		$stop = array( 'a', 'an', 'the', 'in', 'on', 'at', 'to', 'for', 'of', 'and', 'or', 'is', 'are', 'was', 'be', 'as', 'by', 'it', 'its', 'with', 'from', 'that', 'this', 'how', 'what', 'when', 'where', 'which', 'who', 'do', 'does', 'did', 'not', 'no', 'vs', 'you', 'your', 'we', 'our', 'my' );
		$words = preg_split( '/\W+/', strtolower( $title ), -1, PREG_SPLIT_NO_EMPTY );
		return array_values( array_filter( $words, fn( $w ) => strlen( $w ) >= 3 && ! in_array( $w, $stop, true ) ) );
	}
}
