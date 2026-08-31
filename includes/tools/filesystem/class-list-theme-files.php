<?php
namespace RankOut_Connector\Tools\Filesystem;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class List_Theme_Files extends Base_Tool {

	public function get_name() {
		return 'wp_list_theme_files';
	}

	public function get_description() {
		return 'Lists files and directories in a WordPress theme. Parameters: stylesheet (theme folder, e.g. "twentytwentyfour"), path (optional subdirectory within the theme, default: root), depth (optional recursion depth 1–5, default: 3). Returns { stylesheet, path, files: [{ name, path, type, size?, extension? }], count }. Use wp_get_theme_file to read a specific file\'s contents.';
	}

	public function get_category() {
		return 'filesystem';
	}

	public function get_required_capability() {
		return 'edit_themes';
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
				'stylesheet' => array(
					'type'        => 'string',
					'description' => 'Theme folder identifier (e.g. "twentytwentyfour"). Use wp_get_active_theme or wp_list_themes to find this.',
				),
				'path'       => array(
					'type'        => 'string',
					'description' => 'Optional subdirectory within the theme to list (e.g. "inc", "assets/css"). Defaults to the theme root.',
				),
				'depth'      => array(
					'type'        => 'integer',
					'description' => 'How many directory levels to recurse into (1–5). Default: 3.',
					'minimum'     => 1,
					'maximum'     => 5,
				),
			),
			'required'   => array( 'stylesheet' ),
		);
	}

	public function execute( array $arguments ) {
		$this->validate_required( $arguments, array( 'stylesheet' ) );

		$stylesheet = sanitize_file_name( $arguments['stylesheet'] );
		$subpath    = isset( $arguments['path'] ) ? (string) $arguments['path'] : '';
		$depth      = isset( $arguments['depth'] ) ? (int) $arguments['depth'] : 3;
		$depth      = min( max( $depth, 1 ), 5 );

		$theme_root = get_theme_root( $stylesheet );
		$theme_dir  = $theme_root . DIRECTORY_SEPARATOR . $stylesheet;

		if ( ! is_dir( $theme_dir ) ) {
			throw new \RuntimeException( sprintf( 'Theme "%s" not found.', esc_html( $stylesheet ) ) );
		}

		$real_theme_dir = realpath( $theme_dir );
		if ( ! $real_theme_dir ) {
			throw new \RuntimeException( 'Could not resolve theme directory.' );
		}

		$scan_dir = $real_theme_dir;

		if ( '' !== $subpath ) {
			$subpath  = ltrim( str_replace( '\\', '/', $subpath ), '/' );
			if ( false !== strpos( $subpath, '..' ) ) {
				throw new \InvalidArgumentException( 'Path must not contain "..".' );
			}
			$scan_dir = $real_theme_dir . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $subpath );
			$scan_dir = realpath( $scan_dir );
			if ( ! $scan_dir || strpos( $scan_dir . DIRECTORY_SEPARATOR, $real_theme_dir . DIRECTORY_SEPARATOR ) !== 0 ) {
				throw new \RuntimeException( sprintf( 'Directory "%s" not found in theme "%s".', esc_html( $subpath ), esc_html( $stylesheet ) ) );
			}
		}

		$files = $this->scan_directory( $scan_dir, $real_theme_dir, $depth, 0 );

		return array(
			'stylesheet' => $stylesheet,
			'path'       => '' !== $subpath ? $subpath : '.',
			'files'      => $files,
			'count'      => count( $files ),
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

			$full_path     = $dir . DIRECTORY_SEPARATOR . $entry;
			$relative_path = ltrim( str_replace( $base_dir, '', $full_path ), DIRECTORY_SEPARATOR );
			$relative_path = str_replace( DIRECTORY_SEPARATOR, '/', $relative_path );

			if ( count( $items ) >= 500 ) {
				break;
			}

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
				$item = array(
					'name'      => $entry,
					'path'      => $relative_path,
					'type'      => 'file',
					'size'      => (int) filesize( $full_path ),
					'extension' => strtolower( pathinfo( $entry, PATHINFO_EXTENSION ) ),
				);
				$items[] = $item;
			}
		}

		return $items;
	}
}
