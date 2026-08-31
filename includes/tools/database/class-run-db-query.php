<?php
namespace RankOut_Connector\Tools\Database;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Run_DB_Query extends Base_Tool {

	const MAX_ROWS = 500;

	public function get_name() {
		return 'wp_run_db_query';
	}

	public function get_description() {
		return 'Executes a read-only SQL SELECT query against the WordPress database and returns the results. Only SELECT statements are allowed — INSERT, UPDATE, DELETE, DROP, and other write operations are rejected. Parameters: sql (the SELECT query). Returns { columns: string[], rows: object[], count: int, truncated: bool }. The WordPress table prefix is available via the placeholder {prefix} (e.g. SELECT * FROM {prefix}posts). Maximum 500 rows returned.';
	}

	public function get_category() {
		return 'database';
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
				'sql' => array(
					'type'        => 'string',
					'description' => 'A SQL SELECT query. Use {prefix} as a placeholder for the WordPress table prefix (e.g. "SELECT * FROM {prefix}posts WHERE post_status = \'publish\' LIMIT 10").',
				),
			),
			'required'   => array( 'sql' ),
		);
	}

	public function execute( array $arguments ) {
		global $wpdb;

		$this->validate_required( $arguments, array( 'sql' ) );

		$sql = (string) $arguments['sql'];

		// Replace {prefix} placeholder with the actual table prefix.
		$sql = str_replace( '{prefix}', $wpdb->prefix, $sql );

		$this->validate_select_only( $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- query is validated as SELECT-only above
		$results = $wpdb->get_results( $sql, ARRAY_A );

		if ( null === $results ) {
			$error = $wpdb->last_error;
			throw new \RuntimeException( $error ? $error : 'Query returned no results and produced a database error.' );
		}

		$truncated = false;
		if ( count( $results ) > self::MAX_ROWS ) {
			$results   = array_slice( $results, 0, self::MAX_ROWS );
			$truncated = true;
		}

		$columns = ! empty( $results ) ? array_keys( $results[0] ) : array();

		return array(
			'columns'   => $columns,
			'rows'      => $results,
			'count'     => count( $results ),
			'truncated' => $truncated,
		);
	}

	private function validate_select_only( string $sql ): void {
		// Strip single-line comments (-- ...)
		$clean = preg_replace( '/--[^\n]*/', ' ', $sql );
		// Strip block comments (/* ... */)
		$clean = preg_replace( '/\/\*.*?\*\//s', ' ', $clean );
		$clean = trim( $clean );

		if ( ! preg_match( '/^SELECT\s/i', $clean ) ) {
			throw new \InvalidArgumentException( 'Only SELECT queries are permitted.' );
		}

		// Reject stacked statements: find any semicolon that is not the final character.
		$without_trailing = rtrim( $clean, "; \t\n\r" );
		if ( false !== strpos( $without_trailing, ';' ) ) {
			throw new \InvalidArgumentException( 'Multiple SQL statements are not allowed.' );
		}

		// Reject obvious write keywords appearing after SELECT.
		$upper = strtoupper( $clean );
		$forbidden = array( 'INSERT ', 'UPDATE ', 'DELETE ', 'DROP ', 'TRUNCATE ', 'ALTER ', 'CREATE ', 'REPLACE ', 'CALL ', 'EXEC ', 'EXECUTE ', 'LOAD ', 'GRANT ', 'REVOKE ' );
		foreach ( $forbidden as $keyword ) {
			if ( false !== strpos( $upper, $keyword ) ) {
				throw new \InvalidArgumentException( sprintf( 'Query contains forbidden keyword: %s', rtrim( $keyword ) ) );
			}
		}
	}
}
