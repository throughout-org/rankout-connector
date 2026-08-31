<?php
namespace RankOut_Connector\Tools\Filesystem;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class List_Wp_Content extends Base_Tool {

	public function get_name() {
		return 'wp_list_wp_content';
	}

	public function get_description() {
		return 'Lists files and directories inside wp-content (or any subdirectory within it). Parameters: path (optional relative path inside wp-content, e.g. "uploads/2025", "mu-plugins", "languages" — defaults to the wp-content root), depth (optional recursion depth 1–5, default: 2). Returns { base, path, files: [{ name, path, type, size?, extension? }], count }. Use wp_get_wp_content_file to read a specific file\'s contents. Covers themes, plugins, mu-plugins, uploads, languages, and any custom directories under wp-content.';
	}

	public function get_category() {
		return 'filesystem';
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
			'properties' => array(
				'path'  => array(
					'type'        => 'string',
					'description' => 'Relative path inside wp-content to list (e.g. "uploads/2025/06", "mu-plugins"). Omit to list the wp-content root.',
				),
				'depth' => array(
					'type'        => 'integer',
					'description' => 'Recursion depth (1–5). Default: 2.',
					'minimum'     => 1,
					'maximum'     => 5,
				),
			),
		);
	}

	public function execute( array $arguments ) {
		$subpath = isset( $arguments['path'] ) ? (string) $arguments['path'] : '';
		$depth   = isset( $arguments['depth'] ) ? (int) $arguments['depth'] : 2;
		$depth   = min( max( $depth, 1 ), 5 );

		$base_dir     = WP_CONTENT_DIR;
		$real_base    = realpath( $base_dir );

		if ( ! $real_base ) {
			throw new \RuntimeException( 'Could not resolve wp-content directory.' );
		}

		$scan_dir = $real_base;

		if ( '' !== $subpath ) {
			$subpath  = ltrim( str_replace( '\\', '/', $subpath ), '/' );
			if ( false !== strpos( $subpath, '..' ) ) {
				throw new \InvalidArgumentException( 'Path must not contain "..".' );
			}
			$candidate = $real_base . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $subpath );
			$resolved  = realpath( $candidate );
			if ( ! $resolved || strpos( $resolved . DIRECTORY_SEPARATOR, $real_base . DIRECTORY_SEPARATOR ) !== 0 ) {
				throw new \RuntimeException( sprintf( 'Directory "%s" not found inside wp-content.', $subpath ) );
			}
			$scan_dir = $resolved;
		}

		$files = $this->scan_directory( $scan_dir, $real_base, $depth, 0 );

		return array(
			'base'  => WP_CONTENT_DIR,
			'path'  => '' !== $subpath ? $subpath : '.',
			'files' => $files,
			'count' => count( $files ),
		);
	}

	private function scan_directory( string $dir, string $base_dir, int $max_depth, int $current_depth ): array {
		$items = array();

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$entries = @scandir( $dir );
		if ( false === $entries ) {
			return $items;
		}

		foreach ( $entries as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}

			if ( count( $items ) >= 500 ) {
				break;
			}

			$full_path     = $dir . DIRECTORY_SEPARATOR . $entry;
			$relative_path = ltrim( str_replace( $base_dir, '', $full_path ), DIRECTORY_SEPARATOR );
			$relative_path = str_replace( DIRECTORY_SEPARATOR, '/', $relative_path );

			if ( is_dir( $full_path ) ) {
				$items[] = array(
					'name' => $entry,
					'path' => $relative_path,
					'type' => 'dir',
				);
				if ( $current_depth < $max_depth - 1 ) {
					$children = $this->scan_directory( $full_path, $base_dir, $max_depth, $current_depth + 1 );
					foreach ( $children as $child ) {
						if ( count( $items ) >= 500 ) {
							break;
						}
						$items[] = $child;
					}
				}
			} else {
				$items[] = array(
					'name'      => $entry,
					'path'      => $relative_path,
					'type'      => 'file',
					'size'      => (int) filesize( $full_path ),
					'extension' => strtolower( pathinfo( $entry, PATHINFO_EXTENSION ) ),
				);
			}
		}

		return $items;
	}
}
