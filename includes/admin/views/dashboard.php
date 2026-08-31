<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'rankout_connector_view_dashboard' ) ) :
function rankout_connector_view_dashboard( $endpoint_url, $token_count, $tool_count, $tool_groups, $client_guides, $oauth_client_count = 0, $external_data_integrations = array() ) {
	$tokens_url  = esc_url( admin_url( 'admin.php?page=rankout-connector-tokens' ) );
	$oauth_url   = esc_url( admin_url( 'admin.php?page=rankout-connector-oauth' ) );
	$tokens_link = '<a href="' . $tokens_url . '">' . esc_html__( 'API Tokens', 'rankout-connector' ) . '</a>';
?>
<div class="wrap wp-mcp-admin">
	<h1><?php esc_html_e( 'RankOut Connector for WP Dashboard', 'rankout-connector' ); ?></h1>

	<?php include __DIR__ . '/partials/page-nav.php'; ?>

	<div class="wp-mcp-dashboard-grid">

		<!-- Quick Start Guides -->
		<?php
		$quickstart_collapsible = false;
		include __DIR__ . '/partials/quickstart-card.php';
		?>

		<!-- Server Status Card -->
		<div class="wp-mcp-card">
			<h2><?php esc_html_e( 'Server Status', 'rankout-connector' ); ?></h2>
			<table class="widefat striped">
				<tbody>
					<tr>
						<td class="wp-mcp-status-label"><?php esc_html_e( 'Protocol Version', 'rankout-connector' ); ?></td>
						<td><code>2025-11-25 / 2025-06-18 / 2025-03-26</code></td>
					</tr>
					<tr>
						<td class="wp-mcp-status-label"><?php esc_html_e( 'Plugin Version', 'rankout-connector' ); ?></td>
						<td><code><?php echo esc_html( RANKOUT_CONNECTOR_VERSION ); ?></code></td>
					</tr>
					<tr>
						<td class="wp-mcp-status-label"><?php esc_html_e( 'PHP Version', 'rankout-connector' ); ?></td>
						<td><code><?php echo esc_html( PHP_VERSION ); ?></code></td>
					</tr>
					<tr>
						<td class="wp-mcp-status-label"><?php esc_html_e( 'Active Tokens', 'rankout-connector' ); ?></td>
						<td><?php if ( $token_count > 0 ) : ?><a href="<?php echo esc_url( $tokens_url ); ?>"><?php echo esc_html( $token_count ); ?></a><?php else : ?><?php echo esc_html( $token_count ); ?><?php endif; ?></td>
					</tr>
					<tr>
						<td class="wp-mcp-status-label"><?php esc_html_e( 'Active Clients', 'rankout-connector' ); ?></td>
						<td><?php if ( $oauth_client_count > 0 ) : ?><a href="<?php echo esc_url( $oauth_url ); ?>"><?php echo esc_html( $oauth_client_count ); ?></a><?php else : ?><?php echo esc_html( $oauth_client_count ); ?><?php endif; ?></td>
					</tr>
					<tr>
						<td class="wp-mcp-status-label"><?php esc_html_e( 'Registered Tools', 'rankout-connector' ); ?></td>
						<td><a href="#available-tools"><?php echo esc_html( $tool_count ); ?></a></td>
					</tr>
					<?php
					$external_data_integrations = is_array( $external_data_integrations ) ? $external_data_integrations : array();
					$ext_connected = array_filter( $external_data_integrations );
					$ext_count     = count( $ext_connected );
					$ext_total     = count( $external_data_integrations );
					$ext_missing   = array_keys( array_filter( $external_data_integrations, fn( $v ) => ! $v ) );
					?>
					<tr>
						<td class="wp-mcp-status-label"><?php esc_html_e( 'External Data', 'rankout-connector' ); ?></td>
						<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=rankout-connector-external-data' ) ); ?>"><code><?php echo esc_html( $ext_count . '/' . $ext_total ); ?></code></a></td>
					</tr>
				</tbody>
			</table>

			<?php if ( $ext_count < $ext_total ) : ?>
			<div class="wp-mcp-hint wp-mcp-hint-warn" style="margin-top:12px;">
				<span class="dashicons dashicons-warning"></span>
				<span>
					<?php
					printf(
						/* translators: 1: connected count, 2: total count, 3: comma-separated list of missing integration names, 4: link to External Data page */
						esc_html__( '%1$s of %2$s external data sources connected. Set up %3$s on the %4$s page to enable more AI tools.', 'rankout-connector' ),
						'<strong>' . esc_html( $ext_count ) . '</strong>',
						'<strong>' . esc_html( $ext_total ) . '</strong>',
						'<strong>' . esc_html( implode( ', ', $ext_missing ) ) . '</strong>',
						'<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector-external-data' ) ) . '">' . esc_html__( 'External Data', 'rankout-connector' ) . '</a>'
					);
					?>
				</span>
			</div>
			<?php endif; ?>
		</div>

		<!-- Available Tools Card -->
		<div class="wp-mcp-card wp-mcp-card-full" id="available-tools">
			<h2><?php esc_html_e( 'Available Tools', 'rankout-connector' ); ?></h2>
			<p><?php
			/* translators: %d: number of registered MCP tools */
			printf( esc_html__( '%d tools are registered and available for MCP clients.', 'rankout-connector' ), absint( $tool_count ) ); ?></p>
			<ul class="description" style="margin-top:4px; list-style: disc; padding-left: 20px;">
				<li>
					<?php
					printf(
						/* translators: 1: link to Settings page, 2: link to Plugin Integrations page, 3: link to Abilities page, 4: link to External Data page. */
						esc_html__( 'To enable or disable tools, visit the %1$s, %2$s, %3$s, or %4$s page — disabled tools will not appear in this list.', 'rankout-connector' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector-settings' ) ) . '">' . esc_html__( 'Settings', 'rankout-connector' ) . '</a>',
						'<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector-plugin-integrations' ) ) . '">' . esc_html__( 'Plugin Integrations', 'rankout-connector' ) . '</a>',
						'<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector-abilities' ) ) . '">' . esc_html__( 'Abilities', 'rankout-connector' ) . '</a>',
						'<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector-external-data' ) ) . '">' . esc_html__( 'External Data', 'rankout-connector' ) . '</a>'
					);
					?>
				</li>
			</ul>

			<?php
			$hints = isset( $tool_groups['hints'] ) ? $tool_groups['hints'] : array();

			
			if ( ! empty( $hints['has_global_overrides'] ) ) :
				$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector-settings' ) ) . '">' . esc_html__( 'Settings', 'rankout-connector' ) . '</a>';
			?>
				<div class="wp-mcp-hint wp-mcp-hint-info">
					<span class="dashicons dashicons-filter" aria-hidden="true"></span>
					<span><?php
						/* translators: %s: link to Settings page */
						echo wp_kses_post( sprintf( __( 'Some tools are hidden by your global filters in %s.', 'rankout-connector' ), $settings_link ) );
					?></span>
				</div>
			<?php endif; ?>

			<?php
			
			$bucket_links = array();
			if ( ! empty( $hints['disabled_abilities_present'] ) ) {
				$bucket_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector-abilities' ) ) . '">' . esc_html__( 'Abilities', 'rankout-connector' ) . '</a>';
			}
			if ( ! empty( $hints['disabled_plugin_tools_present'] ) ) {
				$bucket_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector-plugin-integrations' ) ) . '">' . esc_html__( 'Plugin Integrations', 'rankout-connector' ) . '</a>';
			}
			if ( ! empty( $hints['disabled_ga_present'] ) || ! empty( $hints['disabled_gsc_present'] ) ) {
				$bucket_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector-external-data' ) ) . '">' . esc_html__( 'External Data', 'rankout-connector' ) . '</a>';
			}
			if ( ! empty( $bucket_links ) ) :
				$count = count( $bucket_links );
				if ( 1 === $count ) {
					$joined = $bucket_links[0];
				} elseif ( 2 === $count ) {
					/* translators: separator between two page links */
					$joined = $bucket_links[0] . ' ' . esc_html__( 'or', 'rankout-connector' ) . ' ' . $bucket_links[1];
				} else {
					$last   = array_pop( $bucket_links );
					$joined = implode( ', ', $bucket_links ) . ', ' . esc_html__( 'or', 'rankout-connector' ) . ' ' . $last;
				}
			?>
				<div class="wp-mcp-hint wp-mcp-hint-warn">
					<span class="dashicons dashicons-warning" aria-hidden="true"></span>
					<span><?php
						/* translators: %s: list of admin page links (Abilities / Plugin Integrations / External Data) */
						echo wp_kses_post( sprintf( __( 'Some installed tools are turned off — re-enable in %s.', 'rankout-connector' ), $joined ) );
					?></span>
				</div>
			<?php endif; ?>

			<?php
			
			$missing_sources = array();
			if ( ! empty( $hints['ga_missing'] ) )       { $missing_sources[] = __( 'Google Analytics', 'rankout-connector' ); }
			if ( ! empty( $hints['gsc_missing'] ) )      { $missing_sources[] = __( 'Google Search Console', 'rankout-connector' ); }
			if ( ! empty( $hints['dfs_missing'] ) )      { $missing_sources[] = __( 'DataForSEO', 'rankout-connector' ); }
			if ( ! empty( $hints['semrush_missing'] ) )  { $missing_sources[] = __( 'SEMrush', 'rankout-connector' ); }
			if ( ! empty( $missing_sources ) ) :
				$ext_link = '<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector-external-data' ) ) . '">' . esc_html__( 'External Data', 'rankout-connector' ) . '</a>';
				$names    = '<strong>' . esc_html( implode( ', ', $missing_sources ) ) . '</strong>';
				/* translators: 1: comma-separated source names, 2: link to External Data page */
				$msg = sprintf( __( '%1$s %2$s — add credentials in %3$s to expose their tools.', 'rankout-connector' ),
					$names,
					count( $missing_sources ) === 1 ? esc_html__( 'is not connected', 'rankout-connector' ) : esc_html__( 'are not connected', 'rankout-connector' ),
					$ext_link
				);
			?>
				<div class="wp-mcp-hint wp-mcp-hint-warn">
					<span class="dashicons dashicons-admin-network" aria-hidden="true"></span>
					<span><?php echo wp_kses_post( $msg ); ?></span>
				</div>
			<?php endif; ?>

			<div class="wp-mcp-hint wp-mcp-hint-info">
				<span class="dashicons dashicons-admin-network" aria-hidden="true"></span>
				<span><?php
					$tokens_oauth_link = '<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector-tokens' ) ) . '">' . esc_html__( 'API Token &amp; OAuth', 'rankout-connector' ) . '</a>';
					/* translators: %s: link to Tokens & OAuth page */
					echo wp_kses_post( sprintf( __( 'Token permissions and OAuth scopes may further limit which tools your AI client can see. Review access in %s.', 'rankout-connector' ), $tokens_oauth_link ) );
				?></span>
			</div>

			<?php
			$render_tool_chips = function( array $tools ) {
				echo '<div class="wp-mcp-tools-list" style="margin:8px 0 4px;">';
				foreach ( $tools as $def ) {
					$name = is_array( $def ) ? $def['name'] : $def;
					echo '<span class="wp-mcp-tool-chip"><code>' . esc_html( $name ) . '</code></span>';
				}
				echo '</div>';
			};
			?>

			<!-- Core Tools -->
			<details class="wp-mcp-quickstart-item" style="margin-top:12px;">
				<summary><span><?php
					/* translators: %d: number of core tools */
					printf( esc_html__( 'Core (%d tools)', 'rankout-connector' ), absint( array_sum( array_map( 'count', $tool_groups['core'] ) ) ) );
				?></span></summary>
				<div class="wp-mcp-quickstart-body">
					<?php if ( empty( $tool_groups['core'] ) ) : ?>
						<p class="description"><?php esc_html_e( 'No core tools are currently registered.', 'rankout-connector' ); ?></p>
					<?php else : ?>
						<?php foreach ( $tool_groups['core'] as $label => $tools ) : ?>
							<details class="wp-mcp-quickstart-item" style="margin-top:6px;">
								<summary><span><?php echo esc_html( $label ); ?> <small style="font-weight:normal;opacity:0.7;">(<?php echo absint( count( $tools ) ); ?>)</small></span></summary>
								<div class="wp-mcp-quickstart-body">
									<?php $render_tool_chips( $tools ); ?>
								</div>
							</details>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</details>

			<!-- Plugin Tools: always shows all supported plugins with status -->
			<?php
			$plugin_active_count = 0;
			foreach ( $tool_groups['plugins'] as $p ) {
				if ( 'active' === $p['status'] ) {
					$plugin_active_count += count( $p['tools'] );
				}
			}
			?>
			<details class="wp-mcp-quickstart-item" style="margin-top:8px;">
				<summary><span><?php
					/* translators: %d: number of plugin tools */
					printf( esc_html__( 'Plugins (%d tools)', 'rankout-connector' ), absint( $plugin_active_count ) );
				?></span></summary>
				<div class="wp-mcp-quickstart-body">
					<?php foreach ( $tool_groups['plugins'] as $plugin_label => $plugin_data ) : ?>
						<details class="wp-mcp-quickstart-item" style="margin-top:6px;">
							<summary><span>
								<?php echo esc_html( $plugin_label ); ?>
								<?php if ( 'active' === $plugin_data['status'] ) : ?>
									<small style="font-weight:normal;opacity:0.7;">(<?php echo absint( count( $plugin_data['tools'] ) ); ?> tools)</small>
								<?php elseif ( 'not_installed' === $plugin_data['status'] ) : ?>
									<small style="font-weight:normal;opacity:0.55;">— <?php esc_html_e( 'Not installed', 'rankout-connector' ); ?></small>
								<?php else : ?>
									<small style="font-weight:normal;opacity:0.55;">— <?php esc_html_e( 'Installed, no tools active', 'rankout-connector' ); ?></small>
								<?php endif; ?>
							</span></summary>
							<div class="wp-mcp-quickstart-body">
								<?php if ( 'active' === $plugin_data['status'] ) : ?>
									<?php $render_tool_chips( $plugin_data['tools'] ); ?>
								<?php elseif ( 'not_installed' === $plugin_data['status'] ) : ?>
									<p class="description"><?php
									/* translators: %s: plugin name */
									printf( esc_html__( '%s is not installed or not active. Install and activate it to unlock these MCP tools.', 'rankout-connector' ), esc_html( $plugin_label ) ); ?></p>
								<?php else : ?>
									<p class="description"><?php
									$integrations_link = '<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector-plugin-integrations' ) ) . '">' . esc_html__( 'Plugin Integrations', 'rankout-connector' ) . '</a>';
									/* translators: 1: plugin name, 2: Plugin Integrations page link */
									echo wp_kses_post( sprintf( __( '%1$s installed but no tools activated. Please activate in %2$s.', 'rankout-connector' ), esc_html( $plugin_label ), $integrations_link ) );
								?></p>
								<?php endif; ?>
							</div>
						</details>
					<?php endforeach; ?>
				</div>
			</details>

			<!-- External Data Tools (GA, GSC) -->
			<?php
			$external_active_count = 0;
			foreach ( $tool_groups['external'] as $e ) {
				if ( 'active' === $e['status'] ) {
					$external_active_count += count( $e['tools'] );
				}
			}
			$external_data_url = esc_url( admin_url( 'admin.php?page=rankout-connector-external-data' ) );
			?>
			<details class="wp-mcp-quickstart-item" style="margin-top:8px;">
				<summary><span><?php
					/* translators: %d: number of external data tools */
					printf( esc_html__( 'External Data (%d tools)', 'rankout-connector' ), absint( $external_active_count ) );
				?></span></summary>
				<div class="wp-mcp-quickstart-body">
					<p class="description"><?php
						$ext_link = '<a href="' . $external_data_url . '">' . esc_html__( 'External Data', 'rankout-connector' ) . '</a>';
						/* translators: %s: link to External Data settings page */
						echo wp_kses_post( sprintf( __( 'Configure credentials in %s to activate these tools.', 'rankout-connector' ), $ext_link ) );
					?></p>
					<?php foreach ( $tool_groups['external'] as $ext_label => $ext_data ) : ?>
						<details class="wp-mcp-quickstart-item" style="margin-top:6px;">
							<summary><span>
								<?php echo esc_html( $ext_label ); ?>
								<?php if ( 'active' === $ext_data['status'] ) : ?>
									<small style="font-weight:normal;opacity:0.7;">(<?php echo absint( count( $ext_data['tools'] ) ); ?> tools)</small>
								<?php elseif ( 'not_configured' === $ext_data['status'] ) : ?>
									<small style="font-weight:normal;opacity:0.55;">— <?php esc_html_e( 'Not configured', 'rankout-connector' ); ?></small>
								<?php else : ?>
									<small style="font-weight:normal;opacity:0.55;">— <?php esc_html_e( 'Configured, no tools active', 'rankout-connector' ); ?></small>
								<?php endif; ?>
							</span></summary>
							<div class="wp-mcp-quickstart-body">
								<?php if ( 'active' === $ext_data['status'] ) : ?>
									<?php $render_tool_chips( $ext_data['tools'] ); ?>
								<?php elseif ( 'not_configured' === $ext_data['status'] ) : ?>
									<p class="description"><?php
										$ext_link2 = '<a href="' . $external_data_url . '">' . esc_html__( 'External Data', 'rankout-connector' ) . '</a>';
										/* translators: 1: integration name, 2: External Data page link */
										echo wp_kses_post( sprintf( __( '%1$s credentials are not configured. Add a service account in %2$s to enable these tools.', 'rankout-connector' ), esc_html( $ext_label ), $ext_link2 ) );
									?></p>
								<?php else : ?>
									<p class="description"><?php esc_html_e( 'Credentials configured but no tools are active.', 'rankout-connector' ); ?></p>
								<?php endif; ?>
							</div>
						</details>
					<?php endforeach; ?>
				</div>
			</details>

			<!-- Abilities: Core (flat) + one collapsible per plugin -->
			<?php
			$abilities_tool_count = 0;
			foreach ( $tool_groups['abilities'] as $group_data ) {
				$abilities_tool_count += count( $group_data['tools'] );
			}
			$integrations_url = admin_url( 'admin.php?page=rankout-connector-plugin-integrations' );
			?>
			<details class="wp-mcp-quickstart-item" style="margin-top:8px;">
				<summary><span><?php
					/* translators: %d: number of registered abilities */
					printf( esc_html__( 'Abilities (%d tools)', 'rankout-connector' ), absint( $abilities_tool_count ) );
				?></span></summary>
				<div class="wp-mcp-quickstart-body">
					<?php if ( empty( $tool_groups['abilities'] ) ) : ?>
						<p class="description"><?php esc_html_e( 'No abilities are currently registered. Abilities require WordPress 6.9+ and plugins that implement wp_register_ability().', 'rankout-connector' ); ?></p>
					<?php else : ?>
						<?php foreach ( $tool_groups['abilities'] as $group_label => $group_data ) :
							$tools    = $group_data['tools'];
							$is_known = ! empty( $group_data['is_known'] );
						?>
							<?php if ( 'Core' === $group_label ) : ?>
								<div style="margin-top:8px;">
									<strong><?php esc_html_e( 'Core', 'rankout-connector' ); ?></strong>
									<?php if ( empty( $tools ) ) : ?>
										<p class="description" style="margin-top:4px;"><?php esc_html_e( 'No core abilities are registered yet.', 'rankout-connector' ); ?></p>
									<?php else : ?>
										<?php $render_tool_chips( $tools ); ?>
									<?php endif; ?>
								</div>
							<?php else : ?>
								<details class="wp-mcp-quickstart-item" style="margin-top:6px;">
									<summary><span>
										<?php echo esc_html( $group_label ); ?>
										<?php if ( ! empty( $tools ) ) : ?>
											<small style="font-weight:normal;opacity:0.7;">(<?php echo absint( count( $tools ) ); ?> abilities)</small>
										<?php elseif ( ! empty( $group_data['has_abilities'] ) ) : ?>
											<small style="font-weight:normal;opacity:0.55;">— <?php esc_html_e( 'Have abilities but not activated', 'rankout-connector' ); ?></small>
										<?php else : ?>
											<small style="font-weight:normal;opacity:0.55;">— <?php esc_html_e( 'No abilities', 'rankout-connector' ); ?></small>
										<?php endif; ?>
									</span></summary>
									<div class="wp-mcp-quickstart-body">
										<?php if ( ! empty( $tools ) ) : ?>
											<?php $render_tool_chips( $tools ); ?>
										<?php elseif ( ! empty( $group_data['has_abilities'] ) ) : ?>
											<?php  ?>
											<p class="description"><?php
												$abilities_link = '<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector-abilities' ) ) . '">' . esc_html__( 'Abilities', 'rankout-connector' ) . '</a>';
												/* translators: 1: plugin name, 2: Abilities page link */
												echo wp_kses_post( sprintf( __( '%1$s has registered abilities but they are not currently active in RankOut Connector. Enable them in %2$s.', 'rankout-connector' ), esc_html( $group_label ), $abilities_link ) );
											?></p>
										<?php else : ?>
											<?php  ?>
											<p class="description"><?php esc_html_e( 'This plugin does not expose any abilities. Ask the plugin developer to implement them via wp_register_ability() and they will automatically appear here.', 'rankout-connector' ); ?></p>
											<?php if ( $is_known ) : ?>
												<p class="description"><?php
													$link = '<a href="' . esc_url( $integrations_url ) . '">' . esc_html__( 'Plugin Integrations', 'rankout-connector' ) . '</a>';
													/* translators: 1: plugin name, 2: Plugin Integrations link */
													echo wp_kses_post( sprintf( __( 'We do support %1$s without abilities — see available tools in %2$s.', 'rankout-connector' ), esc_html( $group_label ), $link ) );
												?></p>
											<?php endif; ?>
										<?php endif; ?>
									</div>
								</details>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</details>

		</div>

	</div>
</div>
<?php
}
endif;
rankout_connector_view_dashboard( $endpoint_url, $token_count, $tool_count, $tool_groups, $client_guides, isset( $oauth_client_count ) ? $oauth_client_count : 0, isset( $external_data_integrations ) ? $external_data_integrations : array() );
