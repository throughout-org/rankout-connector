<?php
namespace RankOut_Connector\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Abilities_Page {

	public function __construct() {
		\add_action( 'admin_menu', array( $this, 'register_menu' ) );
		\add_action( 'admin_init', array( $this, 'handle_form_actions' ) );
	}

	public function register_menu() {
		\add_submenu_page(
			'rankout-connector',
			__( 'Abilities', 'rankout-connector' ),
			__( 'Abilities', 'rankout-connector' ),
			'manage_options',
			'rankout-connector-abilities',
			array( $this, 'render_page' )
		);
	}

	public function handle_form_actions() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_POST['rankout_connector_save_abilities'] ) && \check_admin_referer( 'rankout_connector_save_abilities' ) ) {
			$abilities_on_page = isset( $_POST['abilities_on_page'] )
				? array_values( array_filter( array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['abilities_on_page'] ) ) ) )
				: array();
			$checked_on_page   = isset( $_POST['enabled_abilities'] )
				? array_values( array_filter( array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['enabled_abilities'] ) ) ) )
				: array();
			$paged = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;
			$s     = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';
			$this->handle_save_abilities( $abilities_on_page, $checked_on_page, $paged, $s );
		}
	}

	private function handle_save_abilities( array $abilities_on_page, array $checked_on_page, int $paged, string $s ) {
		$enabled_abilities = (array) \get_option( 'rankout_connector_enabled_abilities', array() );
		$new_enabled = array_diff( $enabled_abilities, $abilities_on_page );
		$new_enabled = array_merge( $new_enabled, $checked_on_page );
		\update_option( 'rankout_connector_enabled_abilities', array_unique( $new_enabled ) );

		$redirect_url = \add_query_arg(
			array_filter( array(
				'page'    => 'rankout-connector-abilities',
				'message' => 'saved',
				'paged'   => $paged > 1 ? $paged : false,
				's'       => ! empty( $s ) ? $s : false,
			) ),
			\admin_url( 'admin.php' )
		);

		\wp_safe_redirect( $redirect_url );
		exit;
	}

	public function render_page() {
		$has_abilities_api = \function_exists( 'wp_get_abilities' );
		$enabled_abilities = (array) \get_option( 'rankout_connector_enabled_abilities', array() );
		$message           = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search_query      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		
		$flat_abilities = array();
		$page_abilities = array();
		$page_links = null;
		$total_items = 0;
		$current_page = 1;

		if ( $has_abilities_api ) {
			$all_abilities = \wp_get_abilities();
			foreach ( $all_abilities as $ability ) {
				$name   = $ability->get_name();

				
				if ( ! empty( $search_query ) ) {
					$label = $ability->get_label();
					$desc  = $ability->get_description();

					if ( stripos( $name, $search_query ) === false &&
						 stripos( $label, $search_query ) === false &&
						 stripos( $desc, $search_query ) === false ) {
						continue; 
					}
				}

				$parts  = explode( '/', $name, 2 );
				$prefix = count( $parts ) > 1 ? $parts[0] : 'core';

				$flat_abilities[] = array(
					'prefix' => $prefix,
					'ability' => $ability
				);
			}

			
			$items_per_page = max( 1, (int) \apply_filters( 'rankout_connector_abilities_per_page', 20 ) );
			$current_page = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$total_items = count($flat_abilities);
			$total_pages = ceil($total_items / $items_per_page);
			$offset = ($current_page - 1) * $items_per_page;
			$page_abilities = array_slice($flat_abilities, $offset, $items_per_page);

			if ( $total_pages > 1 ) {
				$page_links = \paginate_links( array(
					'base' => \add_query_arg( 'paged', '%#%' ),
					'format' => '',
					'prev_text' => __('&laquo;', 'rankout-connector'),
					'next_text' => __('&raquo;', 'rankout-connector'),
					'total' => $total_pages,
					'current' => $current_page
				));
			}
		}

		require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/admin/views/abilities.php';
	}
}
