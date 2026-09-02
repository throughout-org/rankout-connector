<?php
namespace RankOut_Connector\Tools\GEO;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extracts structured entity signals from a post without requiring external NLP.
 *
 * Returns the author entity, topic entities (categories/tags), heading structure,
 * internal links, external citations, and media entities — the signals generative
 * engines use to decide whether a piece of content is authoritative and citable.
 */
class Get_Entity_Context extends Base_Tool {

	public function get_name() {
		return 'wp_get_entity_context';
	}

	public function get_description() {
		return 'Extracts structured entity signals from a post — the building blocks that generative engines (ChatGPT, Perplexity, Claude) use to decide whether content is authoritative and worth citing. Returns: author entity (name, bio, social links), topic entities (categories, tags), heading structure (H2/H3), internal links, external citations, images with alt text, word count, and any schema.org entities already set. Parameters: post_id.';
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
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'The ID of the post or page to analyse.',
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

		$content = $post->post_content;
		// Strip block editor comments to get clean HTML.
		$html = preg_replace( '/<!--.*?-->/s', '', $content );

		return array(
			'post_id'            => $post_id,
			'title'              => get_the_title( $post_id ),
			'url'                => get_permalink( $post_id ),
			'post_type'          => $post->post_type,
			'author'             => $this->extract_author( (int) $post->post_author ),
			'topics'             => $this->extract_topics( $post_id ),
			'headings'           => $this->extract_headings( $html ),
			'internal_links'     => $this->extract_links( $html, true ),
			'external_citations' => $this->extract_links( $html, false ),
			'images'             => $this->extract_images( $post_id, $html ),
			'schema_entity'      => $this->extract_schema_entity( $post_id ),
			'word_count'         => $this->word_count( $html ),
			'published'          => $post->post_date,
			'modified'           => $post->post_modified,
		);
	}

	private function extract_author( int $user_id ): array {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return array( 'id' => $user_id, 'found' => false );
		}

		$social_keys = array(
			'twitter'   => array( 'twitter', 'twitter_url', '_twitter' ),
			'linkedin'  => array( 'linkedin', 'linkedin_url', '_linkedin' ),
			'facebook'  => array( 'facebook', 'facebook_url' ),
			'instagram' => array( 'instagram', 'instagram_url' ),
			'youtube'   => array( 'youtube', 'youtube_url' ),
		);

		$social = array();
		foreach ( $social_keys as $platform => $meta_keys ) {
			foreach ( $meta_keys as $key ) {
				$val = get_user_meta( $user_id, $key, true );
				if ( $val ) {
					$social[ $platform ] = $val;
					break;
				}
			}
		}

		// Also check Yoast/RankMath author social.
		$yoast_social_keys = array(
			'twitter'  => 'wpseo_twitter',
			'facebook' => 'wpseo_facebook',
			'linkedin' => 'wpseo_linkedin_url',
		);
		foreach ( $yoast_social_keys as $platform => $key ) {
			if ( empty( $social[ $platform ] ) ) {
				$val = get_user_meta( $user_id, $key, true );
				if ( $val ) {
					$social[ $platform ] = $val;
				}
			}
		}

		$bio = get_user_meta( $user_id, 'description', true );

		return array(
			'id'          => $user_id,
			'found'       => true,
			'name'        => $user->display_name,
			'email'       => $user->user_email,
			'url'         => $user->user_url,
			'bio'         => $bio,
			'has_bio'     => ! empty( $bio ),
			'social'      => $social,
			'has_social'  => ! empty( $social ),
			'author_url'  => get_author_posts_url( $user_id ),
		);
	}

	private function extract_topics( int $post_id ): array {
		$topics = array();

		$categories = wp_get_post_categories( $post_id, array( 'fields' => 'all' ) );
		foreach ( $categories as $cat ) {
			$topics[] = array(
				'type'        => 'category',
				'name'        => $cat->name,
				'slug'        => $cat->slug,
				'url'         => get_category_link( $cat->term_id ),
				'description' => $cat->description,
			);
		}

		$tags = wp_get_post_tags( $post_id );
		foreach ( $tags as $tag ) {
			$topics[] = array(
				'type'        => 'tag',
				'name'        => $tag->name,
				'slug'        => $tag->slug,
				'url'         => get_tag_link( $tag->term_id ),
				'description' => $tag->description,
			);
		}

		// Custom taxonomies.
		$post      = get_post( $post_id );
		$tax_names = get_object_taxonomies( $post->post_type );
		$skip      = array( 'category', 'post_tag', 'post_format' );
		foreach ( $tax_names as $tax_name ) {
			if ( in_array( $tax_name, $skip, true ) ) {
				continue;
			}
			$terms = wp_get_post_terms( $post_id, $tax_name );
			foreach ( $terms as $term ) {
				$topics[] = array(
					'type'        => $tax_name,
					'name'        => $term->name,
					'slug'        => $term->slug,
					'url'         => get_term_link( $term ),
					'description' => $term->description,
				);
			}
		}

		return $topics;
	}

	private function extract_headings( string $html ): array {
		$headings = array();
		if ( preg_match_all( '/<h([2-4])[^>]*>(.*?)<\/h[2-4]>/si', $html, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$headings[] = array(
					'level' => 'h' . $match[1],
					'text'  => wp_strip_all_tags( $match[2] ),
				);
			}
		}
		return $headings;
	}

	private function extract_links( string $html, bool $internal ): array {
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$links     = array();

		if ( ! preg_match_all( '/<a[^>]+href=["\']([^"\'#][^"\']*)["\'][^>]*>(.*?)<\/a>/si', $html, $matches, PREG_SET_ORDER ) ) {
			return $links;
		}

		$seen = array();
		foreach ( $matches as $match ) {
			$url  = trim( $match[1] );
			$text = wp_strip_all_tags( $match[2] );

			if ( empty( $url ) || isset( $seen[ $url ] ) ) {
				continue;
			}
			$seen[ $url ] = true;

			$parsed = wp_parse_url( $url );
			$host   = isset( $parsed['host'] ) ? $parsed['host'] : '';

			$is_internal = ( '' === $host ) || ( $host === $site_host ) || ( false !== strpos( $host, $site_host ) );

			if ( $internal === $is_internal ) {
				$entry = array( 'text' => $text, 'url' => $url );
				if ( ! $internal && $host ) {
					$entry['domain'] = $host;
				}
				$links[] = $entry;
			}
		}

		return $links;
	}

	private function extract_images( int $post_id, string $html ): array {
		$images = array();

		// Featured image.
		$thumb_id = get_post_thumbnail_id( $post_id );
		if ( $thumb_id ) {
			$src = wp_get_attachment_image_url( $thumb_id, 'full' );
			$alt = get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
			$images[] = array(
				'url'         => $src,
				'alt'         => $alt,
				'has_alt'     => ! empty( $alt ),
				'is_featured' => true,
			);
		}

		// Inline images.
		if ( preg_match_all( '/<img[^>]+>/i', $html, $matches ) ) {
			foreach ( $matches[0] as $img_tag ) {
				$src = '';
				$alt = '';

				if ( preg_match( '/src=["\']([^"\']+)["\']/', $img_tag, $m ) ) {
					$src = $m[1];
				}
				if ( preg_match( '/alt=["\']([^"\']*)["\']/', $img_tag, $m ) ) {
					$alt = $m[1];
				}

				if ( ! $src ) {
					continue;
				}

				// Skip duplicates (featured image already added).
				$already = false;
				foreach ( $images as $existing ) {
					if ( $existing['url'] === $src ) {
						$already = true;
						break;
					}
				}
				if ( $already ) {
					continue;
				}

				$images[] = array(
					'url'         => $src,
					'alt'         => $alt,
					'has_alt'     => '' !== $alt,
					'is_featured' => false,
				);
			}
		}

		return $images;
	}

	private function extract_schema_entity( int $post_id ): ?array {
		$json = get_post_meta( $post_id, '_rankout_connector_schema', true );
		if ( empty( $json ) ) {
			return null;
		}
		$decoded = json_decode( $json, true );
		return is_array( $decoded ) ? $decoded : null;
	}

	private function word_count( string $html ): int {
		$text = wp_strip_all_tags( $html );
		$text = preg_replace( '/\s+/', ' ', trim( $text ) );
		if ( '' === $text ) {
			return 0;
		}
		return count( explode( ' ', $text ) );
	}
}
