<?php
namespace RankOut_Connector\Tools\EEAT;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Get_Internal_Links extends Base_Tool {

	public function get_name() {
		return 'wp_get_internal_links';
	}

	public function get_description() {
		return 'Lists all internal links within a post\'s content, showing the anchor text and the resolved post/page title + ID for each link. Useful for auditing link equity and spotting broken or orphaned internal links. Parameters: post_id (required). Returns { post_id, title, internal_link_count, links: [{ href, anchor_text, target_post_id, target_title, target_status }] }.';
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

		$html      = preg_replace( '/<!--.*?-->/s', '', $post->post_content );
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( ! preg_match_all( '/<a\s[^>]*href=["\']([^"\'#][^"\']*)["\'][^>]*>(.*?)<\/a>/si', $html, $matches ) ) {
			return array(
				'post_id'             => $post_id,
				'title'               => get_the_title( $post_id ),
				'internal_link_count' => 0,
				'links'               => array(),
			);
		}

		$links = array();
		for ( $i = 0; $i < count( $matches[1] ); $i++ ) {
			$href   = $matches[1][ $i ];
			$anchor = trim( wp_strip_all_tags( $matches[2][ $i ] ) );
			$host   = wp_parse_url( $href, PHP_URL_HOST );

			$is_internal = ( '' === (string) $host )
				|| ( $host === $site_host )
				|| ( false !== strpos( (string) $host, $site_host ) );

			if ( ! $is_internal ) {
				continue;
			}

			// Resolve href to a post.
			$full_url      = $host ? $href : home_url( $href );
			$target_post_id = url_to_postid( $full_url );
			$target_title   = '';
			$target_status  = 'unknown';

			if ( $target_post_id ) {
				$target_post   = get_post( $target_post_id );
				$target_title  = get_the_title( $target_post_id );
				$target_status = $target_post ? $target_post->post_status : 'not_found';
			}

			$links[] = array(
				'href'           => $href,
				'anchor_text'    => $anchor,
				'target_post_id' => $target_post_id ?: null,
				'target_title'   => $target_title ?: null,
				'target_status'  => $target_status,
			);
		}

		return array(
			'post_id'             => $post_id,
			'title'               => get_the_title( $post_id ),
			'internal_link_count' => count( $links ),
			'links'               => $links,
		);
	}
}
