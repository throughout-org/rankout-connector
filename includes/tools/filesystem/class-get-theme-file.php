<?php
namespace RankOut_Connector\Tools\Filesystem;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Get_Theme_File extends Base_Tool {

	public function get_name() {
		return 'wp_get_theme_file';
	}

	public function get_description() {
		return 'Reads the source code of a file from a WordPress theme directory. Parameters: stylesheet (theme folder identifier, e.g. "twentytwentyfour"), file (relative path within the theme, e.g. "functions.php" or "assets/css/style.css"). Returns { stylesheet, file, size, content }. Use wp_list_theme_files to discover available files, or wp_get_active_theme / wp_list_themes to find the stylesheet value.';
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
				'file'       => array(
					'type'        => 'string',
					'description' => 'Relative path to the file within the theme directory (e.g. "functions.php", "style.css", "inc/custom.php").',
				),
			),
			'required'   => array( 'stylesheet', 'file' ),
		);
	}

	public function execute( array $arguments ) {
		$this->validate_required( $arguments, array( 'stylesheet', 'file' ) );

		$stylesheet = sanitize_file_name( $arguments['stylesheet'] );
		$file       = $arguments['file'];

		$theme_root = get_theme_root( $stylesheet );
		$theme_dir  = $theme_root . DIRECTORY_SEPARATOR . $stylesheet;

		if ( ! is_dir( $theme_dir ) ) {
			throw new \RuntimeException( sprintf( 'Theme "%s" not found.', esc_html( $stylesheet ) ) );
		}

		$abs_path = $this->resolve_safe_path( $theme_dir, $file );

		if ( ! is_file( $abs_path ) ) {
			throw new \RuntimeException( sprintf( 'File "%s" not found in theme "%s".', esc_html( $file ), esc_html( $stylesheet ) ) );
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
			'stylesheet' => $stylesheet,
			'file'       => str_replace( DIRECTORY_SEPARATOR, '/', ltrim( str_replace( realpath( $theme_dir ), '', $abs_path ), DIRECTORY_SEPARATOR ) ),
			'size'       => $size,
			'content'    => $content,
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
				throw new \InvalidArgumentException( 'Access denied: path is outside the theme directory.' );
			}
			return $resolved;
		}

		return $full_path;
	}
}
