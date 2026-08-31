<?php
namespace RankOut_Connector\Tools\GEO;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads or auto-generates the site's llms.txt file.
 *
 * llms.txt is an emerging standard (llmstxt.org) that tells AI crawlers and
 * generative engines what a site is about — the AI-readable equivalent of
 * robots.txt. Placing it at the site root helps LLMs cite, summarise, and
 * represent the site accurately (GEO — Generative Engine Optimisation).
 */
class Get_Llms_Txt extends Base_Tool {

	public function get_name() {
		return 'wp_get_llms_txt';
	}

	public function get_description() {
		return 'Reads the site\'s llms.txt file if it exists, or auto-generates a ready-to-use one from the site\'s content. llms.txt is the AI-crawler equivalent of robots.txt — it tells generative engines (ChatGPT, Perplexity, Claude) what your site is about, which pages matter, and what topics you cover. Parameters: none. Returns { exists: bool, path: string, content: string, generated: bool }. Use wp_update_llms_txt to write or update the file.';
	}

	public function get_category() {
		return 'geo';
	}

	public function get_required_capability() {
		return 'manage_options';
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
			'properties' => new \stdClass(),
		);
	}

	public function execute( array $arguments ) {
		$path = ABSPATH . 'llms.txt';
		$exists = file_exists( $path );

		if ( $exists ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$content = file_get_contents( $path );
			return array(
				'exists'    => true,
				'path'      => $path,
				'content'   => false !== $content ? $content : '',
				'generated' => false,
			);
		}

		return array(
			'exists'    => false,
			'path'      => $path,
			'content'   => $this->generate(),
			'generated' => true,
		);
	}

	public function generate(): string {
		$site_name = get_bloginfo( 'name' );
		$site_desc = get_bloginfo( 'description' );
		$site_url  = home_url();

		$lines   = array();
		$lines[] = '# ' . $site_name;
		$lines[] = '';
		if ( $site_desc ) {
			$lines[] = '> ' . $site_desc;
			$lines[] = '';
		}

		// Key static pages.
		$pages = get_posts( array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );
		if ( $pages ) {
			$lines[] = '## Pages';
			$lines[] = '';
			foreach ( $pages as $page ) {
				$excerpt = wp_trim_words( wp_strip_all_tags( $page->post_content ), 20, '' );
				$line    = '- [' . $page->post_title . '](' . get_permalink( $page->ID ) . ')';
				if ( $excerpt ) {
					$line .= ': ' . $excerpt;
				}
				$lines[] = $line;
			}
			$lines[] = '';
		}

		// Recent posts.
		$posts = get_posts( array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		if ( $posts ) {
			$lines[] = '## Recent Posts';
			$lines[] = '';
			foreach ( $posts as $post ) {
				$excerpt = get_the_excerpt( $post->ID );
				if ( ! $excerpt ) {
					$excerpt = wp_trim_words( wp_strip_all_tags( $post->post_content ), 25, '' );
				}
				$line = '- [' . $post->post_title . '](' . get_permalink( $post->ID ) . ')';
				if ( $excerpt ) {
					$line .= ': ' . $excerpt;
				}
				$lines[] = $line;
			}
			$lines[] = '';
		}

		// Categories as topics.
		$categories = get_categories( array( 'hide_empty' => true, 'number' => 30 ) );
		if ( $categories ) {
			$lines[] = '## Topics';
			$lines[] = '';
			foreach ( $categories as $cat ) {
				$line    = '- [' . $cat->name . '](' . get_category_link( $cat->term_id ) . ')';
				if ( $cat->description ) {
					$line .= ': ' . $cat->description;
				}
				$lines[] = $line;
			}
			$lines[] = '';
		}

		return implode( "\n", $lines );
	}
}
