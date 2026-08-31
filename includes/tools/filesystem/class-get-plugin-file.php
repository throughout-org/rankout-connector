<?php
namespace RankOut_Connector\Tools\Filesystem;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Get_Plugin_File extends Base_Tool {

	public function get_name() {
		return 'wp_get_plugin_file';
	}

	public function get_description() {
		return 'Reads the source code of a file from a WordPress plugin directory. Parameters: plugin (plugin folder name, e.g. "woocommerce"), file (relative path within the plugin, e.g. "woocommerce.php" or "includes/class-wc-order.php"). Returns { plugin, file, size, content }. Use wp_list_plugin_files to discover files, or wp_list_plugins to find plugin folder names.';
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
					'description' => 'Plugin folder name (e.g. "woocommerce", "rankout-connector"). Use wp_list_plugins to find the folder — it is the part before the "/" in the plugin identifier.',
				),
				'file'   => array(
					'type'        => 'string',
					'description' => 'Relative path to the file within the plugin directory (e.g. "plugin-name.php", "includes/class-example.php").',
				),
			),
			'required'   => array( 'plugin', 'file' ),
		);
	}

	public function execute( array $arguments ) {
		$this->validate_required( $arguments, array( 'plugin', 'file' ) );

		$plugin_slug = sanitize_file_name( $arguments['plugin'] );
		$file        = $arguments['file'];

		$plugin_dir = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_slug;

		if ( ! is_dir( $plugin_dir ) ) {
			throw new \RuntimeException( sprintf( 'Plugin folder "%s" not found.', esc_html( $plugin_slug ) ) );
		}

		$abs_path = $this->resolve_safe_path( $plugin_dir, $file );

		if ( ! is_file( $abs_path ) ) {
			throw new \RuntimeException( sprintf( 'File "%s" not found in plugin "%s".', esc_html( $file ), esc_html( $plugin_slug ) ) );
		}

		$size = filesize( $abs_path );
		if ( $size > 512 * 1024 ) {
			throw new \RuntimeException( sprintf( 'File is too large to read (%s bytes). Maximum is 512 KB.', number_format( $size ) ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $abs_path );
		if ( false === $content ) {
			throw new \RuntimeException( 'Could not read file.' );
		}

		return array(
			'plugin'  => $plugin_slug,
			'file'    => str_replace( DIRECTORY_SEPARATOR, '/', ltrim( str_replace( realpath( $plugin_dir ), '', $abs_path ), DIRECTORY_SEPARATOR ) ),
			'size'    => $size,
			'content' => $content,
		);
	}

	private function resolve_safe_path( string $base_dir, string $relative_file ): string {
		$relative_file = ltrim( str_replace( '\\', '/', $relative_file ), '/' );

		if ( '' === $relative_file ) {
			throw new \InvalidArgumentException( 'File path cannot be empty.' );
		}

		if ( false !== strpos( $relative_file, '..' ) ) {
			throw new \InvalidArgumentException( 'File path must not contain "..".' );
		}

		$real_base = realpath( $base_dir );
		if ( ! $real_base ) {
			throw new \RuntimeException( 'Could not resolve base directory.' );
		}

		$full_path = $real_base . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $relative_file );

		$resolved = realpath( $full_path );
		if ( $resolved ) {
			if ( strpos( $resolved . DIRECTORY_SEPARATOR, $real_base . DIRECTORY_SEPARATOR ) !== 0 ) {
				throw new \InvalidArgumentException( 'Access denied: path is outside the plugin directory.' );
			}
			return $resolved;
		}

		return $full_path;
	}
}
