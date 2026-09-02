<?php
namespace RankOut_Connector\Tools\Schema;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Update_Post_Schema extends Base_Tool {

	public function get_name() {
		return 'wp_update_post_schema';
	}

	public function get_description() {
		return 'Sets or replaces the JSON-LD structured data (schema.org) for a post or page. The schema is stored in post meta and automatically output in the page <head> on the front end. Parameters: post_id (integer), schema (object — must include "@context" and "@type", e.g. {"@context":"https://schema.org","@type":"Article","headline":"..."} ). Pass schema: null to remove existing schema. Returns { post_id, schema } on success. Use wp_list_schema_types to see supported types and their fields.';
	}

	public function get_category() {
		return 'schema';
	}

	public function get_required_capability() {
		return 'edit_posts';
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
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'The ID of the post or page to update.',
				),
				'schema'  => array(
					'description' => 'A JSON-LD schema object with at minimum "@context" and "@type". Pass null to delete existing schema.',
				),
			),
			'required'   => array( 'post_id', 'schema' ),
		);
	}

	public function execute( array $arguments ) {
		$this->validate_required( $arguments, array( 'post_id' ) );

		$post_id = $this->parse_required_id( $arguments['post_id'], 'post_id' );

		if ( ! get_post( $post_id ) ) {
			throw new \RuntimeException( 'Post not found.' );
		}

		$schema = isset( $arguments['schema'] ) ? $arguments['schema'] : null;

		// Deletion.
		if ( null === $schema ) {
			delete_post_meta( $post_id, '_rankout_connector_schema' );
			return array(
				'post_id' => $post_id,
				'schema'  => null,
				'deleted' => true,
			);
		}

		// Accept either a pre-encoded JSON string or a native array/object.
		if ( is_string( $schema ) ) {
			$schema = json_decode( $schema, true );
			if ( ! is_array( $schema ) ) {
				throw new \InvalidArgumentException( 'schema must be a valid JSON object.' );
			}
		} elseif ( is_object( $schema ) ) {
			$schema = json_decode( wp_json_encode( $schema ), true );
		}

		if ( ! is_array( $schema ) ) {
			throw new \InvalidArgumentException( 'schema must be an object.' );
		}

		// Require @context and @type.
		if ( empty( $schema['@context'] ) ) {
			$schema['@context'] = 'https://schema.org';
		}

		if ( empty( $schema['@type'] ) ) {
			throw new \InvalidArgumentException( 'schema must include "@type" (e.g. "Article", "FAQPage", "Product").' );
		}

		$json = wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		update_post_meta( $post_id, '_rankout_connector_schema', $json );

		return array(
			'post_id' => $post_id,
			'schema'  => $schema,
		);
	}
}
