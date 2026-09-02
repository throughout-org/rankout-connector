<?php
namespace RankOut_Connector\Tools\AEO;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Create_Faq_Block extends Base_Tool {

	public function get_name() {
		return 'wp_create_faq_block';
	}

	public function get_description() {
		return 'Appends a FAQ block to a post and saves a FAQPage JSON-LD schema. Generates a Yoast-compatible wp:yoast/faq-block (works with or without Yoast active) and stores FAQPage structured data in _rankout_connector_schema post meta so it renders in <head>. Parameters: post_id (required), faqs (required, array of { question, answer } objects), append (bool, default true — set false to prepend). Returns { post_id, faq_count, updated }.';
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
			'readOnlyHint'    => false,
			'destructiveHint' => false,
			'openWorldHint'   => false,
		);
	}

	public function get_input_schema() {
		return array(
			'type'       => 'object',
			'required'   => array( 'post_id', 'faqs' ),
			'properties' => array(
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'ID of the post to update.',
				),
				'faqs'    => array(
					'type'        => 'array',
					'description' => 'Array of FAQ items. Each item must have "question" and "answer" strings.',
					'items'       => array(
						'type'       => 'object',
						'required'   => array( 'question', 'answer' ),
						'properties' => array(
							'question' => array( 'type' => 'string' ),
							'answer'   => array( 'type' => 'string' ),
						),
					),
					'minItems'    => 1,
				),
				'append'  => array(
					'type'        => 'boolean',
					'description' => 'Append block to end of post (default true). Pass false to prepend.',
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
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return array( 'error' => 'Permission denied.' );
		}

		$raw_faqs = $arguments['faqs'] ?? array();
		if ( empty( $raw_faqs ) || ! is_array( $raw_faqs ) ) {
			return array( 'error' => 'faqs must be a non-empty array.' );
		}

		$faqs   = array();
		$append = isset( $arguments['append'] ) ? (bool) $arguments['append'] : true;

		foreach ( $raw_faqs as $idx => $item ) {
			$q = sanitize_text_field( $item['question'] ?? '' );
			$a = wp_kses_post( $item['answer'] ?? '' );
			if ( '' === $q ) {
				continue;
			}
			$faqs[] = array( 'id' => 'faq-question-' . ( $idx + 1 ), 'question' => $q, 'answer' => $a );
		}

		if ( empty( $faqs ) ) {
			return array( 'error' => 'No valid FAQ items provided.' );
		}

		// --- Build Yoast-compatible block ---
		$block_data = array( 'questions' => array_map( function ( $f ) {
			return array( 'id' => $f['id'], 'question' => array( $f['question'] ), 'answer' => array( $f['answer'] ) );
		}, $faqs ) );

		$block_json = wp_json_encode( $block_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		$block_html = '<!-- wp:yoast/faq-block ' . $block_json . ' -->' . "\n";
		$block_html .= '<div class="schema-faq wp-block-yoast-faq-block">' . "\n";
		foreach ( $faqs as $f ) {
			$block_html .= '<div class="schema-faq-section" id="' . esc_attr( $f['id'] ) . '">'
				. '<strong class="schema-faq-question">' . esc_html( $f['question'] ) . '</strong>'
				. '<p class="schema-faq-answer">' . wp_kses_post( $f['answer'] ) . '</p>'
				. '</div>' . "\n";
		}
		$block_html .= "</div>\n<!-- /wp:yoast/faq-block -->";

		$new_content = $append
			? $post->post_content . "\n\n" . $block_html
			: $block_html . "\n\n" . $post->post_content;

		wp_update_post( array( 'ID' => $post_id, 'post_content' => $new_content ) );

		// --- FAQPage JSON-LD schema ---
		$faq_entities = array_map( function ( $f ) {
			return array(
				'@type'          => 'Question',
				'name'           => $f['question'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $f['answer'] ),
				),
			);
		}, $faqs );

		$existing_raw = get_post_meta( $post_id, '_rankout_connector_schema', true );
		$existing     = $existing_raw ? json_decode( $existing_raw, true ) : null;

		if ( is_array( $existing ) && ( $existing['@type'] ?? '' ) === 'FAQPage' ) {
			// Merge with existing FAQPage.
			$existing_items         = is_array( $existing['mainEntity'] ?? null ) ? $existing['mainEntity'] : array();
			$existing['mainEntity'] = array_merge( $existing_items, $faq_entities );
			$schema                 = $existing;
		} else {
			$schema = array(
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => $faq_entities,
			);
		}

		update_post_meta( $post_id, '_rankout_connector_schema', wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );

		return array(
			'post_id'   => $post_id,
			'faq_count' => count( $faqs ),
			'position'  => $append ? 'appended' : 'prepended',
			'schema'    => 'FAQPage JSON-LD saved to _rankout_connector_schema',
			'updated'   => true,
		);
	}
}
