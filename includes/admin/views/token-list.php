<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function rankout_connector_view_token_list( $tokens, $message, $new_raw_token, $total_tokens, $tokens_truncated, $endpoint_url = '', $client_guides = array() ) {
?>
<div class="wrap wp-mcp-admin">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'RankOut Connector - API Token & OAuth', 'rankout-connector' ); ?></h1>
    <hr class="wp-header-end">

    <?php include __DIR__ . '/partials/page-nav.php'; ?>

    <?php
    $tokens_link            = '<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector-tokens' ) ) . '">' . esc_html__( 'API Tokens', 'rankout-connector' ) . '</a>';
    $quickstart_collapsible = true;
    include __DIR__ . '/partials/quickstart-card.php';
    ?>

    <h2 class="nav-tab-wrapper">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rankout-connector-oauth' ) ); ?>" class="nav-tab"><?php esc_html_e( 'OAuth', 'rankout-connector' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rankout-connector-tokens' ) ); ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'API Token', 'rankout-connector' ); ?></a>
    </h2>

    <div style="margin: 12px 0;">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rankout-connector-tokens&action=new' ) ); ?>" class="button button-primary">
            <?php esc_html_e( 'Create New Token', 'rankout-connector' ); ?>
        </a>
    </div>

    <?php if ( 'created' === $message ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Token created successfully.', 'rankout-connector' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( 'revoked' === $message ) : ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php esc_html_e( 'Token has been revoked.', 'rankout-connector' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( 'deleted' === $message ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Token has been deleted.', 'rankout-connector' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( 'updated' === $message ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Token has been updated.', 'rankout-connector' ); ?></p>
        </div>
    <?php endif; ?>

    <?php
    $error_msg = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( 'token_not_found' === $error_msg ) :
    ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e( 'Token not found.', 'rankout-connector' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $tokens_truncated ) ) : ?>
        <div class="notice notice-warning">
            <p>
                <?php
                printf(
                    /* translators: 1: number shown, 2: total count */
                    esc_html__( 'Showing the %1$d most recent tokens. Your installation has %2$d tokens in total. Use the API to manage tokens beyond this limit.', 'rankout-connector' ),
                    200,
                    (int) $total_tokens
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $new_raw_token ) ) : ?>
        <div class="notice notice-warning">
            <p><strong><?php esc_html_e( 'Your new API token is shown below. Copy it now -- you will not be able to see it again.', 'rankout-connector' ); ?></strong></p>
            <div class="wp-mcp-endpoint-box">
                <code id="mcp-new-token"><?php echo esc_html( $new_raw_token ); ?></code>
                <button type="button" class="button button-small wp-mcp-copy-btn" data-copy-target="mcp-new-token">
                    <?php esc_html_e( 'Copy', 'rankout-connector' ); ?>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-name column-primary"><?php esc_html_e( 'Name', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-prefix"><?php esc_html_e( 'Token Prefix', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-user"><?php esc_html_e( 'User', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-tools"><?php esc_html_e( 'Allowed Tools', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-last-used"><?php esc_html_e( 'Last Used', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-expires"><?php esc_html_e( 'Expires', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-status"><?php esc_html_e( 'Status', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-actions"><?php esc_html_e( 'Actions', 'rankout-connector' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $tokens ) ) : ?>
                <tr>
                    <td colspan="8"><?php esc_html_e( 'No tokens found. Create your first API token to get started.', 'rankout-connector' ); ?></td>
                </tr>
            <?php else : ?>
                <?php
                
                $token_user_ids = array_unique( array_column( $tokens, 'wp_user_id' ) );
                $token_users    = array();
                if ( ! empty( $token_user_ids ) ) {
                    $fetched = get_users( array( 'include' => $token_user_ids, 'fields' => array( 'ID', 'display_name' ) ) );
                    foreach ( $fetched as $u ) {
                        $token_users[ (int) $u->ID ] = $u->display_name;
                    }
                }
                ?>
                <?php foreach ( $tokens as $token ) : ?>
                    <?php
                    $user_display  = isset( $token_users[ (int) $token['wp_user_id'] ] ) ? $token_users[ (int) $token['wp_user_id'] ] : __( 'Unknown', 'rankout-connector' );
                    $allowed_tools = isset( $token['allowed_tools'] ) ? $token['allowed_tools'] : array();
                    if ( is_string( $allowed_tools ) ) {
                        $allowed_tools = json_decode( $allowed_tools, true );
                    }
                    if ( ! is_array( $allowed_tools ) ) {
                        $allowed_tools = array();
                    }
                    $is_all_tools  = in_array( '*', $allowed_tools, true );
                    $tools_display = $is_all_tools ? __( 'All tools', 'rankout-connector' ) : count( $allowed_tools ) . ' ' . _n( 'tool', 'tools', count( $allowed_tools ), 'rankout-connector' );
                    $is_expired    = ! empty( $token['expires_at'] ) && strtotime( $token['expires_at'] . ' UTC' ) < time();
                    $is_active     = ! empty( $token['is_active'] ) && ! $is_expired;
                    ?>
                    <tr>
                        <td class="column-name column-primary">
                            <strong><?php echo esc_html( $token['name'] ); ?></strong>
                        </td>
                        <td class="column-prefix">
                            <code><?php echo esc_html( $token['token_prefix'] ); ?>...</code>
                        </td>
                        <td class="column-user">
                            <?php echo esc_html( $user_display ); ?>
                        </td>
                        <td class="column-tools">
                            <?php echo esc_html( $tools_display ); ?>
                        </td>
                        <td class="column-last-used">
                            <?php if ( ! empty( $token['last_used_at'] ) ) : ?>
                                <?php
                                /* translators: %s: human-readable time difference */
                                printf( esc_html__( '%s ago', 'rankout-connector' ), esc_html( human_time_diff( strtotime( $token['last_used_at'] . ' UTC' ) ) ) );
                                ?>
                            <?php else : ?>
                                <?php esc_html_e( 'Never', 'rankout-connector' ); ?>
                            <?php endif; ?>
                        </td>
                        <td class="column-expires">
                            <?php if ( ! empty( $token['expires_at'] ) ) : ?>
                                <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $token['expires_at'] . ' UTC' ) ) ); ?>
                                <?php if ( $is_expired ) : ?>
                                    <span class="wp-mcp-badge wp-mcp-badge-expired"><?php esc_html_e( 'Expired', 'rankout-connector' ); ?></span>
                                <?php endif; ?>
                            <?php else : ?>
                                <?php esc_html_e( 'Never', 'rankout-connector' ); ?>
                            <?php endif; ?>
                        </td>
                        <td class="column-status">
                            <?php if ( $is_active ) : ?>
                                <span class="wp-mcp-badge wp-mcp-badge-active"><?php esc_html_e( 'Active', 'rankout-connector' ); ?></span>
                            <?php else : ?>
                                <span class="wp-mcp-badge wp-mcp-badge-inactive"><?php esc_html_e( 'Inactive', 'rankout-connector' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="column-actions">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=rankout-connector-tokens&action=edit&token_id=' . $token['id'] ) ); ?>">
                                <?php esc_html_e( 'Edit', 'rankout-connector' ); ?>
                            </a>
                            <?php if ( $is_active ) : ?>
                                |
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=rankout-connector-tokens&action=revoke&token_id=' . $token['id'] ), 'revoke_token_' . $token['id'] ) ); ?>"
                                   onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to revoke this token? It will no longer be usable for authentication.', 'rankout-connector' ) ); ?>');"
                                   class="wp-mcp-action-revoke">
                                    <?php esc_html_e( 'Revoke', 'rankout-connector' ); ?>
                                </a>
                            <?php endif; ?>
                            |
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=rankout-connector-tokens&action=delete&token_id=' . $token['id'] ), 'delete_token_' . $token['id'] ) ); ?>"
                               onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to permanently delete this token? This action cannot be undone.', 'rankout-connector' ) ); ?>');"
                               class="wp-mcp-action-delete">
                                <?php esc_html_e( 'Delete', 'rankout-connector' ); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col" class="manage-column column-name column-primary"><?php esc_html_e( 'Name', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-prefix"><?php esc_html_e( 'Token Prefix', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-user"><?php esc_html_e( 'User', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-tools"><?php esc_html_e( 'Allowed Tools', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-last-used"><?php esc_html_e( 'Last Used', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-expires"><?php esc_html_e( 'Expires', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-status"><?php esc_html_e( 'Status', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-actions"><?php esc_html_e( 'Actions', 'rankout-connector' ); ?></th>
            </tr>
        </tfoot>
    </table>
</div>
<?php
}
rankout_connector_view_token_list( $tokens, $message, $new_raw_token, $total_tokens, $tokens_truncated, $endpoint_url, $client_guides );
