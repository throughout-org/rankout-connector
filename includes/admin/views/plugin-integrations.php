<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }









function rankout_connector_view_plugin_integrations( $groups, $enabled_groups, $disabled_tools, $message ) {
    $api_labels = array(
        'plugin_rest' => 'Plugin REST API',
        'wp_rest'     => 'WP REST API',
        'php'         => 'PHP / postmeta',
        'custom_http' => 'Custom HTTP',
    );
    $status_labels = array(
        'stable'    => 'Stable',
        'beta'      => 'Beta',
        'premium'   => 'Premium Required',
        'read_only' => 'Read Only',
    );
    ?>
<div class="wrap wp-mcp-admin">
    <h1><?php esc_html_e( 'RankOut Connector - Plugin Integrations', 'rankout-connector' ); ?></h1>

    <?php include __DIR__ . '/partials/page-nav.php'; ?>

    <p class="description">
        <?php esc_html_e( 'Enable MCP tool groups for third-party plugins. Only plugins that are installed and active can be enabled. Disabled tools return an error when called by an AI agent.', 'rankout-connector' ); ?>
    </p>

    <?php if ( 'saved' === $message ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Plugin integration settings saved.', 'rankout-connector' ); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=rankout-connector-plugin-integrations' ) ); ?>">
        <?php wp_nonce_field( 'rankout_connector_plugin_integrations' ); ?>
        <input type="hidden" name="rankout_connector_save_plugin_integrations" value="1">

        <div class="wp-mcp-plugin-groups">
        <?php foreach ( $groups as $group ) :
            $is_installed  = $group['is_installed'];
            $group_enabled = $is_installed && in_array( $group['slug'], $enabled_groups, true );
            $total         = count( $group['tools'] );
            $read_count    = count( array_filter( $group['tools'], fn($t) => 'read'  === $t['type'] ) );
            $write_count   = count( array_filter( $group['tools'], fn($t) => 'write' === $t['type'] ) );
            $enabled_count = $group_enabled
                ? count( array_filter( $group['tools'], fn($t) => ! in_array( $t['name'], $disabled_tools, true ) ) )
                : 0;
            $card_class    = $group_enabled ? 'is-enabled' : 'is-disabled';
            if ( ! $is_installed ) {
                $card_class .= ' is-not-installed';
            }
        ?>
        <div class="wp-mcp-plugin-card <?php echo esc_attr( $card_class ); ?>" data-group="<?php echo esc_attr( $group['slug'] ); ?>">

            <div class="wp-mcp-plugin-card-header">
                <div class="wp-mcp-plugin-card-header-left">
                    <button type="button" class="wp-mcp-collapse-btn" aria-expanded="false"
                            aria-controls="group-body-<?php echo esc_attr( $group['slug'] ); ?>">
                        <span class="wp-mcp-collapse-icon dashicons dashicons-arrow-right-alt2"></span>
                    </button>
                    <label class="wp-mcp-group-toggle <?php echo ! $is_installed ? 'is-not-installed' : ''; ?>">
                        <input type="checkbox" name="enabled_groups[]"
                               value="<?php echo esc_attr( $group['slug'] ); ?>"
                               class="wp-mcp-group-checkbox"
                               data-group="<?php echo esc_attr( $group['slug'] ); ?>"
                               <?php checked( $group_enabled ); ?>
                               <?php disabled( ! $is_installed ); ?>>
                        <strong><?php echo esc_html( $group['name'] ); ?></strong>
                    </label>
                    <span class="wp-mcp-badge wp-mcp-badge--<?php echo esc_attr( $group['status'] ); ?>">
                        <?php echo esc_html( $status_labels[ $group['status'] ] ?? $group['status'] ); ?>
                    </span>
                    <?php if ( ! empty( $group['paid'] ) ) : ?>
                        <span class="wp-mcp-badge wp-mcp-badge--paid"><?php esc_html_e( 'Paid Plugin', 'rankout-connector' ); ?></span>
                    <?php endif; ?>
                    <?php if ( $is_installed ) : ?>
                        <span class="wp-mcp-badge wp-mcp-badge--installed"><?php esc_html_e( 'Installed', 'rankout-connector' ); ?></span>
                    <?php else : ?>
                        <span class="wp-mcp-badge wp-mcp-badge--not-installed"><?php esc_html_e( 'Not Installed', 'rankout-connector' ); ?></span>
                        <?php if ( empty( $group['paid'] ) && ! empty( $group['wporg_slug'] ) ) : ?>
                            <a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . rawurlencode( $group['wporg_slug'] ) . '&TB_iframe=true&width=600&height=550' ) ); ?>"
                               class="button button-small wp-mcp-install-btn thickbox"
                               data-title="<?php echo esc_attr( $group['name'] ); ?>">
                                <?php esc_html_e( 'Install', 'rankout-connector' ); ?>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="wp-mcp-plugin-card-header-right">
                    <span class="wp-mcp-tool-counts" data-group="<?php echo esc_attr( $group['slug'] ); ?>">
                        <?php
                        /* translators: %1$d is the number of enabled tools, %2$d is the total number of tools. */
                        printf( esc_html__( '%1$d / %2$d tools enabled', 'rankout-connector' ), absint( $enabled_count ), absint( $total ) );
                        ?>
                        &nbsp;&middot;&nbsp;
                        <?php
                        /* translators: %1$d is the number of read tools, %2$d is the number of write tools. */
                        printf( esc_html__( '%1$d read, %2$d write', 'rankout-connector' ), absint( $read_count ), absint( $write_count ) );
                        ?>
                    </span>
                </div>
            </div>

            <div class="wp-mcp-plugin-card-body" id="group-body-<?php echo esc_attr( $group['slug'] ); ?>" hidden>
                <p class="wp-mcp-plugin-description"><?php echo esc_html( $group['description'] ); ?></p>

                <?php if ( ! empty( $group['requires'] ) ) : ?>
                <div class="notice notice-info inline wp-mcp-notice-requires">
                    <p><strong><?php esc_html_e( 'Requires:', 'rankout-connector' ); ?></strong> <?php echo esc_html( $group['requires'] ); ?></p>
                </div>
                <?php endif; ?>

                <?php if ( ! $is_installed ) : ?>
                <div class="notice notice-warning inline wp-mcp-notice-requires">
                    <p><?php esc_html_e( 'This plugin is not installed or not active. Install and activate it to enable these tools.', 'rankout-connector' ); ?></p>
                </div>
                <?php endif; ?>

                <div class="wp-mcp-tool-controls">
                    <input type="text" class="wp-mcp-tool-search"
                           placeholder="<?php esc_attr_e( 'Search tools…', 'rankout-connector' ); ?>"
                           data-group="<?php echo esc_attr( $group['slug'] ); ?>">
                    <span class="wp-mcp-type-filters" data-group="<?php echo esc_attr( $group['slug'] ); ?>">
                        <button type="button" class="button wp-mcp-type-filter-btn is-active" data-filter="all" data-group="<?php echo esc_attr( $group['slug'] ); ?>"><?php esc_html_e( 'All', 'rankout-connector' ); ?></button>
                        <button type="button" class="button wp-mcp-type-filter-btn" data-filter="read" data-group="<?php echo esc_attr( $group['slug'] ); ?>"><?php esc_html_e( 'Read Only', 'rankout-connector' ); ?></button>
                        <button type="button" class="button wp-mcp-type-filter-btn" data-filter="write" data-group="<?php echo esc_attr( $group['slug'] ); ?>"><?php esc_html_e( 'Write', 'rankout-connector' ); ?></button>
                    </span>
                    <?php if ( $is_installed ) : ?>
                    <button type="button" class="button wp-mcp-enable-all-btn" data-group="<?php echo esc_attr( $group['slug'] ); ?>">
                        <?php esc_html_e( 'Enable all', 'rankout-connector' ); ?>
                    </button>
                    <button type="button" class="button wp-mcp-disable-all-btn" data-group="<?php echo esc_attr( $group['slug'] ); ?>">
                        <?php esc_html_e( 'Disable all', 'rankout-connector' ); ?>
                    </button>
                    <?php endif; ?>
                </div>

                <div class="wp-mcp-tool-list">
                <?php foreach ( $group['tools'] as $tool ) :
                    $tool_enabled = $group_enabled && ! in_array( $tool['name'], $disabled_tools, true );
                ?>
                <div class="wp-mcp-tool-row <?php echo $tool_enabled ? 'is-enabled' : 'is-disabled'; ?>"
                     data-tool-name="<?php echo esc_attr( $tool['name'] ); ?>"
                     data-tool-type="<?php echo esc_attr( $tool['type'] ); ?>">
                    <label class="wp-mcp-tool-label">
                        <input type="checkbox" name="enabled_tools[]"
                               value="<?php echo esc_attr( $tool['name'] ); ?>"
                               class="wp-mcp-tool-checkbox"
                               <?php checked( $tool_enabled ); ?>
                               <?php disabled( ! $is_installed ); ?>>
                        <span class="wp-mcp-tool-info">
                            <code class="wp-mcp-tool-name"><?php echo esc_html( $tool['name'] ); ?></code>
                            <span class="wp-mcp-tool-description"><?php echo esc_html( $tool['description'] ); ?></span>
                        </span>
                    </label>
                    <span class="wp-mcp-tool-badges">
                        <span class="wp-mcp-badge wp-mcp-badge--<?php echo esc_attr( $tool['type'] ); ?>"><?php echo esc_html( ucfirst( $tool['type'] ) ); ?></span>
                        <span class="wp-mcp-badge wp-mcp-badge--api"><?php echo esc_html( $api_labels[ $tool['api'] ] ?? $tool['api'] ); ?></span>
                        <?php if ( ! empty( $tool['requires_paid'] ) ) : ?>
                            <span class="wp-mcp-badge wp-mcp-badge--paid"><?php esc_html_e( 'Paid', 'rankout-connector' ); ?></span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>

        <p class="submit"><?php submit_button( __( 'Save Plugin Settings', 'rankout-connector' ), 'primary', 'submit', false ); ?></p>
    </form>
</div>
    <?php
}
