<?php









if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$quickstart_collapsible = isset( $quickstart_collapsible ) ? (bool) $quickstart_collapsible : false; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$guides_main   = array_filter( $client_guides, fn( $g ) => empty( $g['group'] ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$guides_others = array_filter( $client_guides, fn( $g ) => ! empty( $g['group'] ) && 'others' === $g['group'] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$render_guide_body = function( array $guide, string $endpoint_url, string $tokens_link ) : void { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	?>
	<div class="wp-mcp-quickstart-body">
		<?php if ( ! empty( $guide['signup_link'] ) ) : ?>
			<p class="wp-mcp-mt-8">
				<a href="<?php echo esc_url( $guide['signup_link'] ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary button-small"><?php echo esc_html( ! empty( $guide['signup_label'] ) ? $guide['signup_label'] : __( 'Sign up', 'rankout-connector' ) ); ?> <span class="dashicons dashicons-external" style="font-size:14px;width:14px;height:14px;vertical-align:middle;"></span></a>
				<?php if ( ! empty( $guide['signup_badge'] ) ) : ?>
					<span style="margin-left:8px;display:inline-block;background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:3px;padding:2px 8px;font-size:12px;font-weight:600;vertical-align:middle;"><?php echo esc_html( $guide['signup_badge'] ); ?></span>
				<?php endif; ?>
			</p>
			<?php if ( ! empty( $guide['signup_disclaimer'] ) ) : ?>
				<p class="description" style="font-size:11px;opacity:0.8;"><?php echo esc_html( $guide['signup_disclaimer'] ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
		<?php if ( ! empty( $guide['oauth_steps'] ) ) : ?>
			<p class="description"><strong>⭐ <?php esc_html_e( 'Recommended: OAuth', 'rankout-connector' ); ?></strong></p>
			<ol style="margin:4px 0 8px 20px;padding:0;">
				<?php foreach ( $guide['oauth_steps'] as $step ) : ?>
					<li class="description" style="margin-bottom:3px;"><?php echo esc_html( $step ); ?></li>
				<?php endforeach; ?>
			</ol>
			<?php if ( ! empty( $guide['oauth_config'] ) ) : ?>
				<div class="wp-mcp-code-block">
					<pre id="qs-<?php echo esc_attr( $guide['id'] ); ?>-oauth"><?php echo esc_html( sprintf( $guide['oauth_config'], esc_url( $endpoint_url ) ) ); ?></pre>
					<button type="button" class="button button-small wp-mcp-copy-btn" data-copy-target="qs-<?php echo esc_attr( $guide['id'] ); ?>-oauth"><?php esc_html_e( 'Copy', 'rankout-connector' ); ?></button>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $guide['link'] ) ) : ?>
				<p class="wp-mcp-mt-8"><a href="<?php echo esc_url( $guide['link'] ); ?>" target="_blank" rel="noopener noreferrer" class="button button-small"><?php echo esc_html( ! empty( $guide['link_label'] ) ? $guide['link_label'] : __( 'Visit Website', 'rankout-connector' ) ); ?> <span class="dashicons dashicons-external" style="font-size:14px;width:14px;height:14px;vertical-align:middle;"></span></a></p>
			<?php endif; ?>
			<div class="notice notice-warning inline" style="margin:8px 0 0;padding:6px 12px;">
				<p class="description"><strong><?php esc_html_e( 'Connection failing?', 'rankout-connector' ); ?></strong> <?php esc_html_e( 'Disable any other MCP plugins on this site — AI clients may get confused when multiple OAuth endpoints are detected.', 'rankout-connector' ); ?></p>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $guide['oauth_steps'] ) && ( ! empty( $guide['config'] ) || ! empty( $guide['cli_config'] ) ) ) : ?>
			<p class="description" style="margin-top:16px;"><strong><?php esc_html_e( 'Alternative: Manual Token', 'rankout-connector' ); ?></strong></p>
		<?php endif; ?>
		<?php if ( ! empty( $guide['cli_config'] ) ) : ?>
			<p class="description"><?php esc_html_e( 'Quick install (CLI):', 'rankout-connector' ); ?></p>
			<div class="wp-mcp-code-block">
				<pre id="qs-<?php echo esc_attr( $guide['id'] ); ?>-cli"><?php echo esc_html( sprintf( $guide['cli_config'], esc_url( $endpoint_url ) ) ); ?></pre>
				<button type="button" class="button button-small wp-mcp-copy-btn" data-copy-target="qs-<?php echo esc_attr( $guide['id'] ); ?>-cli"><?php esc_html_e( 'Copy', 'rankout-connector' ); ?></button>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $guide['hint'] ) || ( ! empty( $guide['link'] ) && empty( $guide['oauth_steps'] ) ) ) : ?>
		<p class="description"><?php echo esc_html( $guide['hint'] ?? '' ); ?>
		<?php if ( ! empty( $guide['link'] ) ) : ?>
			<a href="<?php echo esc_url( $guide['link'] ); ?>" target="_blank" rel="noopener noreferrer" class="button button-small" style="margin-left:6px;vertical-align:middle;"><?php echo esc_html( ! empty( $guide['link_label'] ) ? $guide['link_label'] : __( 'Visit Website', 'rankout-connector' ) ); ?> <span class="dashicons dashicons-external" style="font-size:14px;width:14px;height:14px;vertical-align:middle;"></span></a>
		<?php endif; ?>
		</p>
		<?php endif; ?>
		<?php if ( ! empty( $guide['show_url_copy'] ) ) : ?>
			<p class="description"><strong><?php esc_html_e( 'Server URL', 'rankout-connector' ); ?></strong></p>
			<div class="wp-mcp-code-block" style="margin-bottom:12px;">
				<pre id="qs-<?php echo esc_attr( $guide['id'] ); ?>-url"><?php echo esc_html( $endpoint_url ); ?></pre>
				<button type="button" class="button button-small wp-mcp-copy-btn" data-copy-target="qs-<?php echo esc_attr( $guide['id'] ); ?>-url"><?php esc_html_e( 'Copy', 'rankout-connector' ); ?></button>
			</div>
			<p class="description"><strong><?php esc_html_e( 'Full configuration reference', 'rankout-connector' ); ?></strong></p>
			<pre style="background:#f6f7f7;padding:10px 12px;border-radius:3px;font-size:12px;overflow-x:auto;"><?php echo esc_html( sprintf( $guide['config'], esc_url( $endpoint_url ) ) ); ?></pre>
		<?php elseif ( ! empty( $guide['config'] ) ) : ?>
		<div class="wp-mcp-code-block">
			<pre id="qs-<?php echo esc_attr( $guide['id'] ); ?>"><?php echo esc_html( sprintf( $guide['config'], esc_url( $endpoint_url ) ) ); ?></pre>
			<button type="button" class="button button-small wp-mcp-copy-btn" data-copy-target="qs-<?php echo esc_attr( $guide['id'] ); ?>"><?php esc_html_e( 'Copy', 'rankout-connector' ); ?></button>
		</div>
		<?php endif; ?>
		<?php if ( ! empty( $guide['note'] ) ) : ?>
			<p class="description"><?php echo esc_html( $guide['note'] ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $guide['config'] ) || ( empty( $guide['oauth_config'] ) && empty( $guide['oauth_steps'] ) ) ) : ?>
		<p class="description"><?php
			/* translators: %s: link to API Tokens page */
			echo wp_kses_post( sprintf( __( 'Replace YOUR_API_TOKEN with a token from the %s page.', 'rankout-connector' ), $tokens_link ) ); ?></p>
		<?php endif; ?>
	</div>
	<?php
};

if ( $quickstart_collapsible ) : ?>
<details class="wp-mcp-card" style="margin:1em 0;">
	<summary style="cursor:pointer;font-size:1.3em;font-weight:600;padding:.25em 0;"><?php esc_html_e( 'Quick Start: Connect Your AI Client', 'rankout-connector' ); ?></summary>
<?php else : ?>
<div class="wp-mcp-card">
	<h2><?php esc_html_e( 'Quick Start: Connect Your AI Client', 'rankout-connector' ); ?></h2>
<?php endif; ?>
	<p><?php esc_html_e( 'Example settings for connecting popular MCP clients to this server. Click a client to expand.', 'rankout-connector' ); ?></p>
	<div class="wp-mcp-code-block" style="margin-bottom:16px;">
		<pre id="config-examples-endpoint-url-<?php echo esc_attr( uniqid() ); ?>"><?php echo esc_html( $endpoint_url ); ?></pre>
		<button type="button" class="button button-small wp-mcp-copy-btn" data-copy="<?php echo esc_attr( $endpoint_url ); ?>"><?php esc_html_e( 'Copy', 'rankout-connector' ); ?></button>
	</div>

	<div class="wp-mcp-quickstart-grid">
		<?php foreach ( $guides_main as $guide ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
		<details class="wp-mcp-quickstart-item">
			<summary><span><?php echo esc_html( $guide['name'] ); ?></span></summary>
			<?php $render_guide_body( $guide, $endpoint_url, $tokens_link ); ?>
		</details>
		<?php endforeach; ?>

		<?php if ( ! empty( $guides_others ) ) : ?>
		<details class="wp-mcp-quickstart-item wp-mcp-quickstart-item--group" style="grid-column: 1 / -1;">
			<summary><span><?php esc_html_e( 'Others', 'rankout-connector' ); ?></span></summary>
			<div class="wp-mcp-quickstart-body" style="padding:0;">
				<div class="wp-mcp-quickstart-grid" style="margin:0;">
					<?php foreach ( $guides_others as $guide ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
					<details class="wp-mcp-quickstart-item">
						<summary><span><?php echo esc_html( $guide['name'] ); ?></span></summary>
						<?php $render_guide_body( $guide, $endpoint_url, $tokens_link ); ?>
					</details>
					<?php endforeach; ?>
				</div>
			</div>
		</details>
		<?php endif; ?>

		<details class="wp-mcp-quickstart-item" style="grid-column: 1 / -1;">
			<summary><span><?php esc_html_e( 'Generic MCP Client', 'rankout-connector' ); ?></span></summary>
			<div class="wp-mcp-quickstart-body">
				<p class="description"><?php esc_html_e( 'For any MCP-compatible client, use the following settings:', 'rankout-connector' ); ?></p>

				<details class="wp-mcp-quickstart-item" style="margin-top:12px;">
					<summary><span><?php esc_html_e( '⭐ OAuth 2.1 — Recommended', 'rankout-connector' ); ?></span></summary>
					<div class="wp-mcp-quickstart-body">
						<p class="description"><?php esc_html_e( 'For clients that support OAuth 2.1 (e.g. Claude Desktop, Claude.ai, Cursor, Windsurf), point the client at the MCP endpoint URL below. The client will auto-discover OAuth, prompt you to log in, and manage tokens automatically — no manual token needed.', 'rankout-connector' ); ?></p>
						<table class="widefat striped">
							<tbody>
								<tr>
									<th><?php esc_html_e( 'Transport', 'rankout-connector' ); ?></th>
									<td><code>Streamable HTTP</code></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'MCP Endpoint URL', 'rankout-connector' ); ?></th>
									<td><code><?php echo esc_url( $endpoint_url ); ?></code> <button type="button" class="button button-small wp-mcp-copy-btn" data-copy="<?php echo esc_attr( $endpoint_url ); ?>"><?php esc_html_e( 'Copy', 'rankout-connector' ); ?></button></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Auth method', 'rankout-connector' ); ?></th>
									<td><?php esc_html_e( 'OAuth 2.1 (auto-discovered)', 'rankout-connector' ); ?></td>
								</tr>
							</tbody>
						</table>
						<div class="notice notice-warning inline" style="margin:8px 0 0;padding:6px 12px;">
							<p class="description"><strong><?php esc_html_e( 'Connection failing?', 'rankout-connector' ); ?></strong> <?php esc_html_e( 'Disable any other MCP plugins on this site — AI clients may get confused when multiple OAuth endpoints are detected.', 'rankout-connector' ); ?></p>
						</div>
					</div>
				</details>

				<details class="wp-mcp-quickstart-item" style="margin-top:8px;">
					<summary><span><?php esc_html_e( 'Bearer Token (Authorization Header)', 'rankout-connector' ); ?></span></summary>
					<div class="wp-mcp-quickstart-body">
						<p class="description"><?php esc_html_e( 'For clients that do not support OAuth, generate a token on the API Tokens page and pass it as a header.', 'rankout-connector' ); ?></p>
						<table class="widefat striped">
							<tbody>
								<tr>
									<th><?php esc_html_e( 'Transport', 'rankout-connector' ); ?></th>
									<td><code>Streamable HTTP</code></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Endpoint URL', 'rankout-connector' ); ?></th>
									<td><code><?php echo esc_url( $endpoint_url ); ?></code> <button type="button" class="button button-small wp-mcp-copy-btn" data-copy="<?php echo esc_attr( $endpoint_url ); ?>"><?php esc_html_e( 'Copy', 'rankout-connector' ); ?></button></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Authentication', 'rankout-connector' ); ?></th>
									<td><code>Authorization: Bearer YOUR_API_TOKEN</code></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Content-Type', 'rankout-connector' ); ?></th>
									<td><code>application/json</code></td>
								</tr>
							</tbody>
						</table>
					</div>
				</details>

				<details class="wp-mcp-quickstart-item" style="margin-top:8px;">
					<summary><span><?php esc_html_e( 'Token in URL (Last Resort)', 'rankout-connector' ); ?></span></summary>
					<div class="wp-mcp-quickstart-body">
						<p class="description"><?php esc_html_e( 'For clients that cannot set headers:', 'rankout-connector' ); ?></p>
						<code><?php echo esc_url( $endpoint_url ); ?>/YOUR_API_TOKEN</code> <button type="button" class="button button-small wp-mcp-copy-btn" data-copy="<?php echo esc_attr( $endpoint_url ); ?>/YOUR_API_TOKEN"><?php esc_html_e( 'Copy', 'rankout-connector' ); ?></button>
						<div class="notice notice-warning inline wp-mcp-mt-8">
							<p><?php esc_html_e( '⚠ Security note: URL-based tokens appear in server access logs, WordPress debug logs, and browser history. Use the Authorization header method whenever possible.', 'rankout-connector' ); ?></p>
						</div>
					</div>
				</details>
			</div>
		</details>
	</div>
<?php if ( $quickstart_collapsible ) : ?>
</details>
<?php else : ?>
</div>
<?php endif; ?>
