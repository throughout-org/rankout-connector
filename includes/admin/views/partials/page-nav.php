<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template partial, variables are include-scoped not global.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only nav highlighting, no form processing.
$current_page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'rankout-connector';

$nav_items = array(
	'rankout-connector'               => __( 'Dashboard', 'rankout-connector' ),
	'rankout-connector-oauth'         => __( 'API Token & OAuth', 'rankout-connector' ),
	'rankout-connector-settings'      => __( 'Settings', 'rankout-connector' ),
	'rankout-connector-plugin-integrations' => __( 'Plugins', 'rankout-connector' ),
	'rankout-connector-abilities'     => __( 'Abilities', 'rankout-connector' ),
	'rankout-connector-external-data' => __( 'External Data', 'rankout-connector' ),
	'rankout-connector-audit'         => __( 'Audit Log', 'rankout-connector' ),
	'rankout-connector-history'       => __( 'Change History', 'rankout-connector' ),
);


$oauth_pages = array( 'rankout-connector-oauth', 'rankout-connector-tokens' );
?>
<nav class="nav-tab-wrapper wp-mcp-page-nav" style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;">
	<div style="display:flex;flex-wrap:wrap;">
	<?php foreach ( $nav_items as $slug => $label ) :
		$is_active = ( $current_page === $slug )
			|| ( 'rankout-connector-oauth' === $slug && in_array( $current_page, $oauth_pages, true ) );
		$class = 'nav-tab' . ( $is_active ? ' nav-tab-active' : '' );
	?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>" class="<?php echo esc_attr( $class ); ?>">
			<?php echo esc_html( $label ); ?>
		</a>
	<?php endforeach; ?>
	</div>
	<div style="display:inline-flex;gap:6px;margin-bottom:6px;">
		<a href="https://wordpress.org/support/plugin/rankout-connector/reviews/"
			target="_blank"
			rel="noopener noreferrer"
			class="button button-secondary button-small"
			style="line-height:1.2;display:inline-flex;align-items:center;gap:4px;">
			<span class="dashicons dashicons-star-filled" style="color:#f7b500;font-size:14px;width:14px;height:14px;line-height:1;"></span>
			<?php esc_html_e( 'Rate & Review', 'rankout-connector' ); ?>
		</a>
		<a href="https://wordpress.org/support/plugin/rankout-connector/"
			target="_blank"
			rel="noopener noreferrer"
			class="button button-secondary button-small"
			style="line-height:1.2;display:inline-flex;align-items:center;gap:4px;">
			<span class="dashicons dashicons-sos" style="color:#2271b1;font-size:14px;width:14px;height:14px;line-height:1;"></span>
			<?php esc_html_e( 'Get Help', 'rankout-connector' ); ?>
		</a>
	</div>
</nav>
<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
