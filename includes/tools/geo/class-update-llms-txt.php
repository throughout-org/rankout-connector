<?php
namespace RankOut_Connector\Tools\GEO;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Update_Llms_Txt extends Base_Tool {

	public function get_name() {
		return 'wp_update_llms_txt';
	}

	public function get_description() {
		return 'Writes or updates the site\'s llms.txt file at the WordPress root. This file helps generative engines (ChatGPT, Perplexity, Claude) discover and accurately cite your site — a core GEO signal. Two modes: (1) pass content (string) to write your own llms.txt; (2) pass auto: true to auto-generate from your site\'s pages, posts, and categories. Returns { success: bool, path: string, bytes_written: int, content: string }.';
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
			'readOnlyHint'    => false,
			'destructiveHint' => false,
			'openWorldHint'   => false,
		);
	}

	public function get_input_schema() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'content' => array(
					'type'        => 'string',
					'description' => 'The full llms.txt content to write (Markdown format). Required unless auto is true.',
				),
				'auto'    => array(
					'type'        => 'boolean',
					'description' => 'When true, auto-generates llms.txt from the site\'s pages, posts, and topics. Ignored if content is provided.',
				),
			),
		);
	}

	public function execute( array $arguments ) {
		$path    = ABSPATH . 'llms.txt';
		$content = null;

		if ( ! empty( $arguments['content'] ) ) {
			$content = (string) $arguments['content'];
		} elseif ( ! empty( $arguments['auto'] ) ) {
			$getter  = new Get_Llms_Txt();
			$content = $getter->generate();
		}

		if ( null === $content || '' === $content ) {
			throw new \InvalidArgumentException( 'Provide either content (string) or set auto: true.' );
		}

		// Use WP_Filesystem for writing.
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_WP_Filesystem
		WP_Filesystem();
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			throw new \RuntimeException( 'WordPress filesystem could not be initialised. Check filesystem permissions.' );
		}

		$written = $wp_filesystem->put_contents( $path, $content, FS_CHMOD_FILE );

		if ( ! $written ) {
			throw new \RuntimeException( sprintf(
				'Could not write llms.txt to %s. The web server may not have write permission to the site root.',
				$path
			) );
		}

		return array(
			'success'       => true,
			'path'          => $path,
			'url'           => home_url( '/llms.txt' ),
			'bytes_written' => strlen( $content ),
			'content'       => $content,
		);
	}
}
