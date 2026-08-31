<?php
namespace RankOut_Connector\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}







class OAuth_Admin {

    


    public function __construct() {
        \add_action( 'rankout_connector_render_oauth_page', array( $this, 'render_page' ) );
        \add_action( 'admin_init', array( $this, 'handle_actions' ) );
        \add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    


    public function register_settings() {
        \register_setting( 'rankout_connector_oauth', 'rankout_connector_oauth_access_token_ttl', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 3600,
        ) );
        \register_setting( 'rankout_connector_oauth', 'rankout_connector_oauth_refresh_token_ttl', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 2592000,
        ) );
        \register_setting( 'rankout_connector_oauth', 'rankout_connector_oauth_dcr_enabled', array(
            'type'              => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default'           => true,
        ) );
    }

    


    


    public function render_page() {
        if ( ! \current_user_can( 'manage_options' ) ) {
            \wp_die( \esc_html__( 'You do not have sufficient permissions to access this page.', 'rankout-connector' ) );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view/tab selector; mutating actions enforce their own nonces.
        $action = isset( $_GET['action'] ) ? \sanitize_text_field( \wp_unslash( $_GET['action'] ) ) : '';

        echo '<div class="wrap">';
        echo '<h1>' . \esc_html__( 'API Token & OAuth', 'rankout-connector' ) . '</h1>';
        include RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/admin/views/partials/page-nav.php';
        $tokens_url = \admin_url( 'admin.php?page=rankout-connector-tokens' );
        $oauth_url  = \admin_url( 'admin.php?page=rankout-connector-oauth' );
        $endpoint_url           = \rest_url( 'rankout-connector/v1/mcp' );
        $client_guides          = Admin_Page::get_client_guides();
        $tokens_link            = '<a href="' . \esc_url( $tokens_url ) . '">' . \esc_html__( 'API Tokens', 'rankout-connector' ) . '</a>';
        $quickstart_collapsible = true;
        include RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/admin/views/partials/quickstart-card.php';
        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="' . \esc_url( $oauth_url ) . '" class="nav-tab nav-tab-active">' . \esc_html__( 'OAuth', 'rankout-connector' ) . '</a>';
        echo '<a href="' . \esc_url( $tokens_url ) . '" class="nav-tab">' . \esc_html__( 'API Token', 'rankout-connector' ) . '</a>';
        echo '</h2>';

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag set by our own redirect.
        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . \esc_html__( 'Settings saved.', 'rankout-connector' ) . '</p></div>';
        }

        if ( 'edit_scope' === $action ) {
            $this->render_edit_scope_view();
        } else {
            $this->render_settings_section();
            $this->render_clients_table();
            $this->render_grants_table();
        }

        echo '</div>';
    }

    
    
    

    


    private function render_settings_section() {
        $access_ttl  = (int) \get_option( 'rankout_connector_oauth_access_token_ttl', 3600 );
        $refresh_ttl = (int) \get_option( 'rankout_connector_oauth_refresh_token_ttl', 2592000 );
        $dcr_enabled = (bool) \get_option( 'rankout_connector_oauth_dcr_enabled', true );
        ?>
        <details class="rankout-connector-oauth-settings" style="margin:1em 0;">
            <summary style="cursor:pointer;font-size:1.3em;font-weight:600;padding:.25em 0;"><?php \esc_html_e( 'Settings', 'rankout-connector' ); ?></summary>
        <form method="post" action="<?php echo \esc_url( \admin_url( 'admin.php?page=rankout-connector-oauth' ) ); ?>">
            <?php \wp_nonce_field( 'rankout_connector_oauth_save_settings' ); ?>
            <input type="hidden" name="rankout_connector_oauth_action" value="save_settings" />
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="access_token_ttl"><?php \esc_html_e( 'Access Token TTL (seconds)', 'rankout-connector' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="access_token_ttl" name="access_token_ttl"
                               value="<?php echo \esc_attr( $access_ttl ); ?>" min="60" step="1" class="regular-text" />
                        <p class="description"><?php \esc_html_e( 'Default: 3600 (1 hour).', 'rankout-connector' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="refresh_token_ttl"><?php \esc_html_e( 'Refresh Token TTL (seconds)', 'rankout-connector' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="refresh_token_ttl" name="refresh_token_ttl"
                               value="<?php echo \esc_attr( $refresh_ttl ); ?>" min="60" step="1" class="regular-text" />
                        <p class="description"><?php \esc_html_e( 'Default: 2592000 (30 days).', 'rankout-connector' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php \esc_html_e( 'Dynamic Client Registration', 'rankout-connector' ); ?></th>
                    <td>
                        <label for="dcr_enabled">
                            <input type="checkbox" id="dcr_enabled" name="dcr_enabled" value="1"
                                <?php \checked( $dcr_enabled ); ?> />
                            <?php \esc_html_e( 'Enable DCR endpoint', 'rankout-connector' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
            <?php \submit_button( __( 'Save Settings', 'rankout-connector' ) ); ?>
        </form>
        </details>
        <hr />
        <?php
    }

    
    
    

    


    private function render_clients_table() {
        global $wpdb;

        $table = $wpdb->prefix . 'rankout_connector_oauth_clients';
        $tokens_table = $wpdb->prefix . 'rankout_connector_oauth_access_tokens';

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination parameter.
        $page   = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
        $offset = ( $page - 1 ) * 50;

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom plugin tables; names prefixed by $wpdb->prefix.
        $clients = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.*, COUNT(t.id) AS active_tokens
                 FROM {$table} c
                 LEFT JOIN {$tokens_table} t
                   ON t.client_id = c.client_id AND t.is_active = %d AND t.expires_at > %s
                 WHERE c.is_active = %d
                 GROUP BY c.id
                 ORDER BY c.created_at DESC
                 LIMIT 51 OFFSET %d",
                1,
                \current_time( 'mysql', true ),
                1,
                $offset
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        
        $has_next_page = ( count( (array) $clients ) > 50 );
        if ( $has_next_page ) {
            $clients = array_slice( (array) $clients, 0, 50 );
        }
        ?>
        <h2><?php \esc_html_e( 'Registered Clients', 'rankout-connector' ); ?></h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php \esc_html_e( 'Client Name', 'rankout-connector' ); ?></th>
                    <th><?php \esc_html_e( 'Client ID', 'rankout-connector' ); ?></th>
                    <th><?php \esc_html_e( 'Redirect URIs', 'rankout-connector' ); ?></th>
                    <th><?php \esc_html_e( 'Created', 'rankout-connector' ); ?></th>
                    <th><?php \esc_html_e( 'Active Tokens', 'rankout-connector' ); ?></th>
                    <th><?php \esc_html_e( 'Status', 'rankout-connector' ); ?></th>
                    <th><?php \esc_html_e( 'Actions', 'rankout-connector' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $clients ) ) : ?>
                    <tr>
                        <td colspan="7">
                            <?php \esc_html_e( 'No registered clients.', 'rankout-connector' ); ?>
                            <a href="<?php echo \esc_url( \admin_url( 'admin.php?page=rankout-connector' ) ); ?>">
                                <?php \esc_html_e( 'Connect to AI Client', 'rankout-connector' ); ?>
                            </a>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $clients as $client ) : ?>
                        <tr>
                            <td><?php echo \esc_html( $client->client_name ); ?></td>
                            <td>
                                <code><?php echo \esc_html( substr( $client->client_id, 0, 16 ) ); ?>&hellip;</code>
                            </td>
                            <td>
                                <?php
                                $uris = json_decode( $client->redirect_uris, true );
                                if ( is_array( $uris ) ) {
                                    echo \esc_html( implode( ', ', $uris ) );
                                } else {
                                    echo \esc_html( $client->redirect_uris );
                                }
                                ?>
                            </td>
                            <td><?php echo \esc_html( $client->created_at ); ?></td>
                            <td><?php echo \esc_html( $client->active_tokens ); ?></td>
                            <td>
                                <span class="dashicons dashicons-yes-alt" style="color:#46b450;"></span>
                                <?php \esc_html_e( 'Active', 'rankout-connector' ); ?>
                            </td>
                            <td>
                                <?php
                                $revoke_url = \wp_nonce_url(
                                    \admin_url( 'admin.php?page=rankout-connector-oauth&rankout_connector_oauth_action=revoke_client&client_id=' . \urlencode( $client->client_id ) ),
                                    'rankout_connector_oauth_revoke_client_' . $client->client_id
                                );
                                ?>
                                <a href="<?php echo \esc_url( $revoke_url ); ?>"
                                   class="button button-secondary"
                                   onclick="return confirm('<?php \esc_attr_e( 'Revoke this client and all its tokens?', 'rankout-connector' ); ?>');">
                                    <?php \esc_html_e( 'Revoke', 'rankout-connector' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if ( $has_next_page ) : ?>
            <a href="<?php echo \esc_url( \add_query_arg( 'paged', $page + 1 ) ); ?>" class="button">
                <?php \esc_html_e( 'Next page', 'rankout-connector' ); ?>
            </a>
        <?php endif; ?>
        <br />
        <?php
    }

    
    
    

    


    private function render_grants_table() {
        global $wpdb;

        $consents_table = $wpdb->prefix . 'rankout_connector_oauth_consents';
        $clients_table  = $wpdb->prefix . 'rankout_connector_oauth_clients';
        $tokens_table   = $wpdb->prefix . 'rankout_connector_oauth_access_tokens';

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination parameter.
        $grants_page   = isset( $_GET['grants_paged'] ) ? max( 1, (int) $_GET['grants_paged'] ) : 1;
        $grants_offset = ( $grants_page - 1 ) * 50;

        
        
        
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom plugin tables; names prefixed by $wpdb->prefix.
        $page_grants = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT con.id AS consent_id, con.wp_user_id, con.client_id, con.scope, con.granted_at, con.updated_at,
                        cl.client_name
                 FROM {$consents_table} con
                 INNER JOIN {$clients_table} cl ON cl.client_id = con.client_id AND cl.is_active = %d
                 ORDER BY con.granted_at DESC
                 LIMIT 51 OFFSET %d",
                1,
                $grants_offset
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        
        $grants_has_next = ( count( (array) $page_grants ) > 50 );
        if ( $grants_has_next ) {
            $page_grants = array_slice( (array) $page_grants, 0, 50 );
        }

        
        
        
        $last_used_map = array();
        if ( ! empty( $page_grants ) ) {
            $client_ids   = array_values( array_unique( array_column( (array) $page_grants, 'client_id' ) ) );
            $placeholders = implode( ', ', array_fill( 0, count( $client_ids ), '%s' ) );
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Custom plugin table; name prefixed by $wpdb->prefix. Dynamic %s placeholders in IN() list match ...$client_ids spread.
            $lu_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT client_id, wp_user_id, MAX(last_used_at) AS last_used
                     FROM {$tokens_table}
                     WHERE client_id IN ({$placeholders})
                     GROUP BY client_id, wp_user_id",
                    ...$client_ids
                )
            );
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
            foreach ( (array) $lu_rows as $lr ) {
                $last_used_map[ $lr->client_id . ':' . $lr->wp_user_id ] = $lr->last_used;
            }
        }

        
        $grants = array();
        foreach ( (array) $page_grants as $g ) {
            $g->last_used = $last_used_map[ $g->client_id . ':' . $g->wp_user_id ] ?? null;
            $grants[]     = $g;
        }
        ?>
        <h2><?php \esc_html_e( 'Active Grants', 'rankout-connector' ); ?></h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php \esc_html_e( 'User', 'rankout-connector' ); ?></th>
                    <th><?php \esc_html_e( 'Client', 'rankout-connector' ); ?></th>
                    <th><?php \esc_html_e( 'Scope', 'rankout-connector' ); ?></th>
                    <th><?php \esc_html_e( 'Granted At', 'rankout-connector' ); ?></th>
                    <th><?php \esc_html_e( 'Last Used', 'rankout-connector' ); ?></th>
                    <th><?php \esc_html_e( 'Actions', 'rankout-connector' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $grants ) ) : ?>
                    <tr>
                        <td colspan="6">
                            <?php \esc_html_e( 'No active grants.', 'rankout-connector' ); ?>
                            <a href="<?php echo \esc_url( \admin_url( 'admin.php?page=rankout-connector' ) ); ?>">
                                <?php \esc_html_e( 'Connect to AI Client', 'rankout-connector' ); ?>
                            </a>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php
                    
                    $grant_user_ids = array_unique( array_map( 'intval', array_column( $grants, 'wp_user_id' ) ) );
                    $grant_users    = array();
                    if ( ! empty( $grant_user_ids ) ) {
                        foreach ( \get_users( array( 'include' => $grant_user_ids, 'number' => count( $grant_user_ids ) ) ) as $u ) {
                            $grant_users[ $u->ID ] = $u;
                        }
                    }
                    ?>
                    <?php foreach ( $grants as $grant ) : ?>
                        <?php $user = isset( $grant_users[ (int) $grant->wp_user_id ] ) ? $grant_users[ (int) $grant->wp_user_id ] : null; ?>
                        <tr>
                            <td>
                                <?php
                                if ( $user ) {
                                    echo \esc_html( $user->display_name ) . ' <small>(' . \esc_html( $user->user_login ) . ')</small>';
                                } else {
                                    echo \esc_html__( 'Unknown user', 'rankout-connector' ) . ' #' . \esc_html( $grant->wp_user_id );
                                }
                                ?>
                            </td>
                            <td><?php echo \esc_html( $grant->client_name ); ?></td>
                            <td><?php echo \esc_html( $this->summarize_scope( $grant->scope ) ); ?></td>
                            <td><?php echo \esc_html( $grant->granted_at ); ?></td>
                            <td><?php echo $grant->last_used ? \esc_html( $grant->last_used ) : '<em>' . \esc_html__( 'Never', 'rankout-connector' ) . '</em>'; ?></td>
                            <td>
                                <?php
                                $revoke_grant_url = \wp_nonce_url(
                                    \admin_url( 'admin.php?page=rankout-connector-oauth&rankout_connector_oauth_action=revoke_grant&consent_id=' . \absint( $grant->consent_id ) ),
                                    'rankout_connector_oauth_revoke_grant_' . $grant->consent_id
                                );
                                $edit_scope_url = \admin_url( 'admin.php?page=rankout-connector-oauth&action=edit_scope&consent_id=' . \absint( $grant->consent_id ) );
                                ?>
                                <a href="<?php echo \esc_url( $revoke_grant_url ); ?>"
                                   class="button button-secondary"
                                   onclick="return confirm('<?php \esc_attr_e( 'Revoke this grant and all associated tokens?', 'rankout-connector' ); ?>');">
                                    <?php \esc_html_e( 'Revoke', 'rankout-connector' ); ?>
                                </a>
                                <a href="<?php echo \esc_url( $edit_scope_url ); ?>" class="button button-secondary">
                                    <?php \esc_html_e( 'Edit Scope', 'rankout-connector' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if ( $grants_has_next ) : ?>
            <a href="<?php echo \esc_url( \add_query_arg( 'grants_paged', $grants_page + 1 ) ); ?>" class="button">
                <?php \esc_html_e( 'Next page', 'rankout-connector' ); ?>
            </a>
        <?php endif; ?>
        <?php
    }

    
    
    

    


    private function render_edit_scope_view() {
        global $wpdb;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view identifier; save action enforces its own nonce.
        $consent_id = isset( $_GET['consent_id'] ) ? \absint( $_GET['consent_id'] ) : 0;
        if ( ! $consent_id ) {
            echo '<div class="notice notice-error"><p>' . \esc_html__( 'Invalid consent ID.', 'rankout-connector' ) . '</p></div>';
            return;
        }

        $consents_table = $wpdb->prefix . 'rankout_connector_oauth_consents';
        $clients_table  = $wpdb->prefix . 'rankout_connector_oauth_clients';

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom plugin tables; names prefixed by $wpdb->prefix.
        $consent = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT con.*, cl.client_name FROM {$consents_table} con INNER JOIN {$clients_table} cl ON cl.client_id = con.client_id AND cl.is_active = 1 WHERE con.id = %d",
                $consent_id
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        if ( ! $consent ) {
            echo '<div class="notice notice-error"><p>' . \esc_html__( 'Consent not found.', 'rankout-connector' ) . '</p></div>';
            return;
        }

        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-scope-map.php';

        $categories    = \RankOut_Connector\OAuth\Scope_Map::get_categories();
        $current_parts = array_filter( array_map( 'trim', explode( ' ', $consent->scope ) ) );
        $is_full_access = in_array( 'mcp', $current_parts, true );

        
        $tokens_table      = $wpdb->prefix . 'rankout_connector_oauth_access_tokens';
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom plugin table; names prefixed by $wpdb->prefix.
        $active_token_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$tokens_table} WHERE client_id = %s AND wp_user_id = %d AND is_active = 1 AND expires_at > %s",
                $consent->client_id,
                (int) $consent->wp_user_id,
                gmdate( 'Y-m-d H:i:s' )
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        $back_url = \admin_url( 'admin.php?page=rankout-connector-oauth' );
        ?>
        <p>
            <a href="<?php echo \esc_url( $back_url ); ?>">&larr; <?php \esc_html_e( 'Back to OAuth Clients', 'rankout-connector' ); ?></a>
        </p>
        <h2>
            <?php
            printf(
                /* translators: %s: client name */
                \esc_html__( 'Edit Scope for %s', 'rankout-connector' ),
                \esc_html( $consent->client_name )
            );
            ?>
        </h2>
        <p>
            <?php
            $user = \get_userdata( (int) $consent->wp_user_id );
            if ( $user ) {
                printf(
                    /* translators: %s: user display name */
                    \esc_html__( 'User: %s', 'rankout-connector' ),
                    \esc_html( $user->display_name )
                );
            }
            ?>
        </p>

        <?php if ( $active_token_count > 0 ) : ?>
            <div class="notice notice-warning is-dismissible" style="padding:12px;margin:1em 0;">
                <p><strong><?php \esc_html_e( 'Active Tokens Warning', 'rankout-connector' ); ?></strong></p>
                <p>
                    <?php
                    printf(
                        /* translators: %d: number of active tokens */
                        \esc_html__( 'This user has %d active token(s) for this client. Saving will immediately apply the new scope to all active tokens — no re-authentication required.', 'rankout-connector' ),
                        (int) $active_token_count
                    );
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo \esc_url( \admin_url( 'admin.php?page=rankout-connector-oauth' ) ); ?>">
            <?php \wp_nonce_field( 'rankout_connector_oauth_save_scope_' . $consent_id ); ?>
            <input type="hidden" name="rankout_connector_oauth_action" value="save_scope" />
            <input type="hidden" name="consent_id" value="<?php echo \esc_attr( $consent_id ); ?>" />

            <?php if ( $is_full_access ) : ?>
                <div class="notice notice-info inline" style="padding:10px 14px;margin:0 0 16px;border-left-color:#007cba;background:#f0f6fc;">
                    <p><?php \esc_html_e( 'This grant has Full Access — all tools are allowed, including any added in the future (plugin integrations, external data, and WordPress Abilities).', 'rankout-connector' ); ?></p>
                </div>
            <?php endif; ?>

            <p style="margin-bottom:12px;">
                <label>
                    <input type="checkbox"
                           name="scopes[]"
                           value="mcp"
                           id="rankout-connector-full-access-toggle"
                        <?php \checked( $is_full_access ); ?> />
                    <strong><?php \esc_html_e( 'Full Access', 'rankout-connector' ); ?></strong>
                    &mdash; <?php \esc_html_e( 'grant all tools, current and future', 'rankout-connector' ); ?>
                </label>
            </p>

            <table class="widefat striped" id="rankout-connector-scope-table" style="max-width:700px;">
                <thead>
                    <tr>
                        <th><?php \esc_html_e( 'Category', 'rankout-connector' ); ?></th>
                        <th style="text-align:center;"><?php \esc_html_e( 'Read', 'rankout-connector' ); ?></th>
                        <th style="text-align:center;"><?php \esc_html_e( 'Write', 'rankout-connector' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $categories as $cat ) : ?>
                        <tr>
                            <td>
                                <?php echo \esc_html( $cat['label'] ); ?>
                                <?php if ( $cat['plugin_required'] ) : ?>
                                    <br /><small class="description"><?php echo \esc_html( $cat['plugin_required'] ); ?></small>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <input type="checkbox"
                                       name="scopes[]"
                                       value="<?php echo \esc_attr( $cat['read_scope'] ); ?>"
                                    <?php \checked( $is_full_access || in_array( $cat['read_scope'], $current_parts, true ) ); ?> />
                            </td>
                            <td style="text-align:center;">
                                <input type="checkbox"
                                       name="scopes[]"
                                       value="<?php echo \esc_attr( $cat['write_scope'] ); ?>"
                                    <?php \checked( $is_full_access || in_array( $cat['write_scope'], $current_parts, true ) ); ?> />
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <script>
            (function() {
                var toggle = document.getElementById('rankout-connector-full-access-toggle');
                var table  = document.getElementById('rankout-connector-scope-table');
                if ( ! toggle || ! table ) { return; }

                var savedStates = {};

                function getCategoryCheckboxes() {
                    return Array.prototype.slice.call( table.querySelectorAll('input[type="checkbox"]') );
                }

                function applyFullAccess( checked ) {
                    table.style.display = checked ? 'none' : '';
                    getCategoryCheckboxes().forEach(function( cb ) {
                        if ( checked ) {
                            savedStates[ cb.value ] = cb.checked;
                            cb.checked = false;
                        } else if ( cb.value in savedStates ) {
                            cb.checked = savedStates[ cb.value ];
                        }
                    });
                }

                if ( toggle.checked ) {
                    applyFullAccess( true );
                }

                toggle.addEventListener('change', function() {
                    applyFullAccess( this.checked );
                });
            })();
            </script>

            <?php \submit_button( __( 'Save Scope', 'rankout-connector' ) ); ?>
        </form>
        <?php
    }

    
    
    

    


    public function handle_actions() {
        if ( ! \current_user_can( 'manage_options' ) ) {
            return;
        }

        
        
        
        $action = '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Dispatch only; each action_*() handler calls check_admin_referer() before mutating state.
        if ( isset( $_POST['rankout_connector_oauth_action'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- See above; handlers enforce nonces.
            $action = \sanitize_text_field( \wp_unslash( $_POST['rankout_connector_oauth_action'] ) );
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- See above; handlers enforce nonces.
        } elseif ( isset( $_GET['rankout_connector_oauth_action'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- See above; handlers enforce nonces.
            $action = \sanitize_text_field( \wp_unslash( $_GET['rankout_connector_oauth_action'] ) );
        }

        if ( '' === $action ) {
            return;
        }

        switch ( $action ) {
            case 'save_settings':
                $this->action_save_settings();
                break;
            case 'revoke_client':
                $this->action_revoke_client();
                break;
            case 'revoke_grant':
                $this->action_revoke_grant();
                break;
            case 'save_scope':
                $this->action_save_scope();
                break;
        }
    }

    
    
    

    


    private function action_save_settings() {
        \check_admin_referer( 'rankout_connector_oauth_save_settings' );

        $access_ttl  = isset( $_POST['access_token_ttl'] ) ? \absint( $_POST['access_token_ttl'] ) : 3600;
        $refresh_ttl = isset( $_POST['refresh_token_ttl'] ) ? \absint( $_POST['refresh_token_ttl'] ) : 2592000;
        $dcr_enabled = isset( $_POST['dcr_enabled'] ) ? 1 : 0;

        
        $access_ttl  = max( 60, $access_ttl );
        $refresh_ttl = max( 60, $refresh_ttl );

        \update_option( 'rankout_connector_oauth_access_token_ttl', $access_ttl );
        \update_option( 'rankout_connector_oauth_refresh_token_ttl', $refresh_ttl );
        \update_option( 'rankout_connector_oauth_dcr_enabled', $dcr_enabled );

        \wp_safe_redirect( \admin_url( 'admin.php?page=rankout-connector-oauth&updated=1' ) );
        exit;
    }

    


    private function action_revoke_client() {
        $client_id = isset( $_GET['client_id'] ) ? \sanitize_text_field( \wp_unslash( $_GET['client_id'] ) ) : '';
        if ( '' === $client_id ) {
            return;
        }

        \check_admin_referer( 'rankout_connector_oauth_revoke_client_' . $client_id );

        global $wpdb;

        $clients_table  = $wpdb->prefix . 'rankout_connector_oauth_clients';
        $tokens_table   = $wpdb->prefix . 'rankout_connector_oauth_access_tokens';
        $codes_table    = $wpdb->prefix . 'rankout_connector_oauth_codes';
        $consents_table = $wpdb->prefix . 'rankout_connector_oauth_consents';

        
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete( $tokens_table, array( 'client_id' => $client_id ), array( '%s' ) );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete( $codes_table, array( 'client_id' => $client_id ), array( '%s' ) );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete( $consents_table, array( 'client_id' => $client_id ), array( '%s' ) );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete( $clients_table, array( 'client_id' => $client_id ), array( '%s' ) );

        \wp_safe_redirect( \admin_url( 'admin.php?page=rankout-connector-oauth&updated=1' ) );
        exit;
    }

    


    private function action_revoke_grant() {
        $consent_id = isset( $_GET['consent_id'] ) ? \absint( $_GET['consent_id'] ) : 0;
        if ( ! $consent_id ) {
            return;
        }

        \check_admin_referer( 'rankout_connector_oauth_revoke_grant_' . $consent_id );

        global $wpdb;

        $consents_table = $wpdb->prefix . 'rankout_connector_oauth_consents';
        $tokens_table   = $wpdb->prefix . 'rankout_connector_oauth_access_tokens';

        
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom plugin table; name prefixed by $wpdb->prefix.
        $consent = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT wp_user_id, client_id FROM {$consents_table} WHERE id = %d",
                $consent_id
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        if ( $consent ) {
            
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update(
                $tokens_table,
                array( 'is_active' => 0 ),
                array(
                    'client_id'  => $consent->client_id,
                    'wp_user_id' => $consent->wp_user_id,
                ),
                array( '%d' ),
                array( '%s', '%d' )
            );

            
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->delete(
                $consents_table,
                array( 'id' => $consent_id ),
                array( '%d' )
            );
        }

        \wp_safe_redirect( \admin_url( 'admin.php?page=rankout-connector-oauth&updated=1' ) );
        exit;
    }

    


    private function action_save_scope() {
        $consent_id = isset( $_POST['consent_id'] ) ? \absint( $_POST['consent_id'] ) : 0;
        if ( ! $consent_id ) {
            return;
        }

        \check_admin_referer( 'rankout_connector_oauth_save_scope_' . $consent_id );

        require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/oauth/class-scope-map.php';

        $valid_scopes = \RankOut_Connector\OAuth\Scope_Map::get_all_scopes();
        $submitted    = isset( $_POST['scopes'] ) && is_array( $_POST['scopes'] )
            ? array_map( 'sanitize_text_field', \wp_unslash( $_POST['scopes'] ) )
            : array();

        
        $clean_scopes = array_values( array_intersect( $submitted, $valid_scopes ) );
        $new_scope    = implode( ' ', $clean_scopes );

        global $wpdb;

        $consents_table = $wpdb->prefix . 'rankout_connector_oauth_consents';
        $tokens_table   = $wpdb->prefix . 'rankout_connector_oauth_access_tokens';

        
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom plugin table; name prefixed by $wpdb->prefix.
        $consent = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT wp_user_id, client_id FROM {$consents_table} WHERE id = %d",
                $consent_id
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        if ( ! $consent ) {
            \wp_safe_redirect( \admin_url( 'admin.php?page=rankout-connector-oauth' ) );
            exit;
        }

        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $consents_table,
            array(
                'scope'      => $new_scope,
                'updated_at' => \current_time( 'mysql', true ),
            ),
            array( 'id' => $consent_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        
        
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $token_update_result = $wpdb->update(
            $tokens_table,
            array( 'scope' => $new_scope ),
            array(
                'client_id'  => $consent->client_id,
                'wp_user_id' => $consent->wp_user_id,
                'is_active'  => 1,
            ),
            array( '%s' ),
            array( '%s', '%d', '%d' )
        );

        if ( false === $token_update_result && ! empty( $wpdb->last_error ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            
            
            
            error_log( 'RankOut Connector: token scope update failed after consent update — ' . $wpdb->last_error ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }

        \wp_safe_redirect( \admin_url( 'admin.php?page=rankout-connector-oauth&updated=1' ) );
        exit;
    }

    
    
    

    





    private function summarize_scope( $scope ) {
        $parts = array_filter( array_map( 'trim', explode( ' ', $scope ) ) );

        if ( empty( $parts ) ) {
            return __( 'None', 'rankout-connector' );
        }

        if ( in_array( 'mcp', $parts, true ) ) {
            return __( 'Full access', 'rankout-connector' );
        }

        $read_count  = 0;
        $write_count = 0;

        foreach ( $parts as $part ) {
            if ( substr( $part, -5 ) === ':read' ) {
                ++$read_count;
            } elseif ( substr( $part, -6 ) === ':write' ) {
                ++$write_count;
            }
        }

        $summary = array();
        if ( $read_count > 0 ) {
            /* translators: %d: number of read scope categories */
            $summary[] = sprintf( _n( '%d read', '%d read', $read_count, 'rankout-connector' ), $read_count );
        }
        if ( $write_count > 0 ) {
            /* translators: %d: number of write scope categories */
            $summary[] = sprintf( _n( '%d write', '%d write', $write_count, 'rankout-connector' ), $write_count );
        }

        return implode( ', ', $summary );
    }
}
