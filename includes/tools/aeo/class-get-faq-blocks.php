<?php
namespace RankOut_Connector\Tools\AEO;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Get_Faq_Blocks extends Base_Tool {

	public function get_name() {
		return 'wp_get_faq_blocks';
	}

	public function get_description() {
		return 'Extracts FAQ / Q&A blocks from a post. Detects Yoast FAQ blocks, Rank Math FAQ blocks, JSON-LD FAQPage already embedded in content, and generic HTML Q&A patterns (details/summary, DL lists). Returns { post_id, post_title, source_counts, faqs: [{ source, question, answer }] }.';
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
			'required'   => array( 'post_id' ),
			'properties' => array(
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'ID of the post to inspect.',
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

		$content = $post->post_content;
		$faqs    = array();
		$counts  = array( 'yoast' => 0, 'rank_math' => 0, 'json_ld' => 0, 'html' => 0 );

		// --- Yoast FAQ block ---
		if ( preg_match_all( '/<!--\s*wp:yoast\/faq-block\s+({.*?})\s*-->/s', $content, $m ) ) {
			foreach ( $m[1] as $raw_json ) {
				$data = json_decode( $raw_json, true );
				if ( ! empty( $data['questions'] ) && is_array( $data['questions'] ) ) {
					foreach ( $data['questions'] as $q ) {
						$question = $this->flatten_rich_text( $q['question'] ?? '' );
						$answer   = $this->flatten_rich_text( $q['answer'] ?? '' );
						if ( $question ) {
							$faqs[]             = array( 'source' => 'yoast', 'question' => $question, 'answer' => $answer );
							$counts['yoast']++;
						}
					}
				}
			}
		}

		// --- Rank Math FAQ block ---
		if ( preg_match_all( '/<!--\s*wp:rank-math\/faq-block.*?-->(.+?)<!--\s*\/wp:rank-math\/faq-block\s*-->/s', $content, $m ) ) {
			foreach ( $m[1] as $block_html ) {
				preg_match_all( '/<h[23][^>]*class="rank-math-question"[^>]*>(.*?)<\/h[23]>/si', $block_html, $qs );
				preg_match_all( '/<div[^>]*class="rank-math-answer"[^>]*>(.*?)<\/div>/si', $block_html, $as );
				for ( $i = 0; $i < count( $qs[1] ); $i++ ) {
					$question = wp_strip_all_tags( $qs[1][ $i ] );
					$answer   = wp_strip_all_tags( $as[1][ $i ] ?? '' );
					if ( $question ) {
						$faqs[]               = array( 'source' => 'rank_math', 'question' => trim( $question ), 'answer' => trim( $answer ) );
						$counts['rank_math']++;
					}
				}
			}
		}

		// --- Inline JSON-LD FAQPage ---
		if ( preg_match_all( '/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $content, $m ) ) {
			foreach ( $m[1] as $json_str ) {
				$schema = json_decode( $json_str, true );
				if ( ! is_array( $schema ) ) {
					continue;
				}
				foreach ( $this->find_faq_entities( $schema ) as $entity ) {
					foreach ( (array) ( $entity['mainEntity'] ?? array() ) as $item ) {
						$q = wp_strip_all_tags( $item['name'] ?? '' );
						$a = wp_strip_all_tags( $item['acceptedAnswer']['text'] ?? '' );
						if ( $q ) {
							$faqs[]            = array( 'source' => 'json_ld', 'question' => $q, 'answer' => $a );
							$counts['json_ld']++;
						}
					}
				}
			}
		}

		// --- details/summary HTML ---
		if ( preg_match_all( '/<details[^>]*>\s*<summary[^>]*>(.*?)<\/summary>(.*?)<\/details>/si', $content, $m ) ) {
			foreach ( $m[1] as $k => $q_raw ) {
				$question = trim( wp_strip_all_tags( $q_raw ) );
				$answer   = trim( wp_strip_all_tags( $m[2][ $k ] ) );
				if ( $question ) {
					$faqs[]          = array( 'source' => 'html', 'question' => $question, 'answer' => $answer );
					$counts['html']++;
				}
			}
		}

		return array(
			'post_id'      => $post_id,
			'post_title'   => get_the_title( $post_id ),
			'faq_count'    => count( $faqs ),
			'source_counts' => $counts,
			'faqs'         => $faqs,
		);
	}

	private function flatten_rich_text( $value ): string {
		if ( is_string( $value ) ) {
			return wp_strip_all_tags( $value );
		}
		if ( is_array( $value ) ) {
			return wp_strip_all_tags( implode( ' ', array_map( function ( $v ) {
				return is_string( $v ) ? $v : ( $v['text'] ?? '' );
			}, $value ) ) );
		}
		return '';
	}

	private function find_faq_entities( array $schema ): array {
		$found = array();
		$type  = $schema['@type'] ?? '';
		if ( 'FAQPage' === $type ) {
			$found[] = $schema;
		}
		// @graph support
		if ( isset( $schema['@graph'] ) && is_array( $schema['@graph'] ) ) {
			foreach ( $schema['@graph'] as $node ) {
				if ( is_array( $node ) && ( $node['@type'] ?? '' ) === 'FAQPage' ) {
					$found[] = $node;
				}
			}
		}
		return $found;
	}
}
