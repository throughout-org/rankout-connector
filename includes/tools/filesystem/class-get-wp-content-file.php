<?php
namespace RankOut_Connector\Tools\Filesystem;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Get_Wp_Content_File extends Base_Tool {

	public function get_name() {
		return 'wp_get_wp_content_file';
	}

	public function get_description() {
		return 'Reads the contents of any file inside wp-content. Parameters: file (path relative to wp-content, e.g. "mu-plugins/my-plugin.php", "uploads/2025/06/document.pdf", "themes/twentytwentyfour/functions.php"). Returns { path, size, content }. Maximum file size: 512 KB. Use wp_list_wp_content to browse available files first.';
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
				'file' => array(
					'type'        => 'string',
					'description' => 'Relative path to the file inside wp-content (e.g. "mu-plugins/my-plugin.php", "themes/storefront/functions.php", "uploads/2025/06/readme.txt").',
				),
			),
			'required'   => array( 'file' ),
		);
	}

	public function execute( array $arguments ) {
		$this->validate_required( $arguments, array( 'file' ) );

		$relative_file = (string) $arguments['file'];

		$real_base = realpath( WP_CONTENT_DIR );
		if ( ! $real_base ) {
			throw new \RuntimeException( 'Could not resolve wp-content directory.' );
		}

		$abs_path = $this->resolve_safe_path( $real_base, $relative_file );

		if ( ! is_file( $abs_path ) ) {
			throw new \RuntimeException( sprintf( 'File "%s" not found inside wp-content.', $relative_file ) );
		}

		$size = filesize( $abs_path );
		if ( $size > 512 * 1024 ) {
			throw new \RuntimeException( sprintf(
				'File is too large to read (%s bytes). Maximum is 512 KB.',
				number_format( $size )
			) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $abs_path );
		if ( false === $content ) {
			throw new \RuntimeException( 'Could not read file.' );
		}

		// Normalise the returned path to forward slashes relative to wp-content.
		$display_path = ltrim( str_replace( $real_base, '', $abs_path ), DIRECTORY_SEPARATOR );
		$display_path = str_replace( DIRECTORY_SEPARATOR, '/', $display_path );

		return array(
			'path'    => $display_path,
			'size'    => $size,
			'content' => $content,
		);
	}

	private function resolve_safe_path( string $real_base, string $relative_file ): string {
		$relative_file = ltrim( str_replace( '\\', '/', $relative_file ), '/' );

		if ( '' === $relative_file ) {
			throw new \InvalidArgumentException( 'File path cannot be empty.' );
		}

		if ( false !== strpos( $relative_file, '..' ) ) {
			throw new \InvalidArgumentException( 'File path must not contain "..".' );
		}

		$full_path = $real_base . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $relative_file );

		$resolved = realpath( $full_path );
		if ( $resolved ) {
			if ( strpos( $resolved . DIRECTORY_SEPARATOR, $real_base . DIRECTORY_SEPARATOR ) !== 0 ) {
				throw new \InvalidArgumentException( 'Access denied: path is outside wp-content.' );
			}
			return $resolved;
		}

		return $full_path;
	}
}
