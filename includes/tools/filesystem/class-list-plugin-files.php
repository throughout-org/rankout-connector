<?php
namespace RankOut_Connector\Tools\Filesystem;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class List_Plugin_Files extends Base_Tool {

	public function get_name() {
		return 'wp_list_plugin_files';
	}

	public function get_description() {
		return 'Lists files and directories inside a WordPress plugin folder. Parameters: plugin (plugin folder name, e.g. "woocommerce"), path (optional subdirectory, default: root), depth (optional recursion depth 1–5, default: 3). Returns { plugin, path, files: [{ name, path, type, size?, extension? }], count }. Use wp_get_plugin_file to read a specific file\'s contents.';
	}

	public function get_category() {
		return 'filesystem';
	}

	public function get_required_capability() {
		return 'edit_plugins';
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
				'plugin' => array(
					'type'        => 'string',
					'description' => 'Plugin folder name (e.g. "woocommerce"). Use wp_list_plugins — the folder is the part before "/" in the plugin identifier.',
				),
				'path'   => array(
					'type'        => 'string',
					'description' => 'Optional subdirectory within the plugin to list (e.g. "includes", "src/Admin"). Defaults to the plugin root.',
				),
				'depth'  => array(
					'type'        => 'integer',
					'description' => 'How many directory levels to recurse into (1–5). Default: 3.',
					'minimum'     => 1,
					'maximum'     => 5,
				),
			),
			'required'   => array( 'plugin' ),
		);
	}

	public function execute( array $arguments ) {
		$this->validate_required( $arguments, array( 'plugin' ) );

		$plugin_slug = sanitize_file_name( $arguments['plugin'] );
		$subpath     = isset( $arguments['path'] ) ? (string) $arguments['path'] : '';
		$depth       = isset( $arguments['depth'] ) ? (int) $arguments['depth'] : 3;
		$depth       = min( max( $depth, 1 ), 5 );

		$plugin_dir = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_slug;

		if ( ! is_dir( $plugin_dir ) ) {
			throw new \RuntimeException( sprintf( 'Plugin folder "%s" not found.', esc_html( $plugin_slug ) ) );
		}

		$real_plugin_dir = realpath( $plugin_dir );
		if ( ! $real_plugin_dir ) {
			throw new \RuntimeException( 'Could not resolve plugin directory.' );
		}

		$scan_dir = $real_plugin_dir;

		if ( '' !== $subpath ) {
			$subpath  = ltrim( str_replace( '\\', '/', $subpath ), '/' );
			if ( false !== strpos( $subpath, '..' ) ) {
				throw new \InvalidArgumentException( 'Path must not contain "..".' );
			}
			$scan_dir = $real_plugin_dir . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $subpath );
			$scan_dir = realpath( $scan_dir );
			if ( ! $scan_dir || strpos( $scan_dir . DIRECTORY_SEPARATOR, $real_plugin_dir . DIRECTORY_SEPARATOR ) !== 0 ) {
				throw new \RuntimeException( sprintf( 'Directory "%s" not found in plugin "%s".', esc_html( $subpath ), esc_html( $plugin_slug ) ) );
			}
		}

		$files = $this->scan_directory( $scan_dir, $real_plugin_dir, $depth, 0 );

		return array(
			'plugin' => $plugin_slug,
			'path'   => '' !== $subpath ? $subpath : '.',
			'files'  => $files,
			'count'  => count( $files ),
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
