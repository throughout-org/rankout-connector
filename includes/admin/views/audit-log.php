<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function rankout_connector_view_audit_log( $total, $entries, $page, $total_pages, $message, $change_counts = array(), $changes_nonce = '' ) {
?>
<div class="wrap wp-mcp-admin">
    <h1><?php esc_html_e( 'RankOut Connector - Audit Log', 'rankout-connector' ); ?></h1>

    <?php include __DIR__ . '/partials/page-nav.php'; ?>

    <?php if ( 'cleaned' === $message ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Audit log entries have been cleaned up.', 'rankout-connector' ); ?></p>
        </div>
    <?php elseif ( 'cleaned_more' === $message ) : ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php esc_html_e( 'Cleaned up 10,000 audit log entries. More likely remain — click the button again to drain the rest.', 'rankout-connector' ); ?></p>
        </div>
    <?php endif; ?>

    <?php $retention = (int) get_option( 'rankout_connector_audit_log_retention', 30 ); ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=rankout-connector-audit' ) ); ?>" class="wp-mcp-cleanup-form">
        <?php wp_nonce_field( 'rankout_connector_cleanup_audit' ); ?>
        <input type="hidden" name="rankout_connector_cleanup_audit" value="1">
        <p>
            <?php
            printf(
                /* translators: %d: number of log entries */
                esc_html__( 'Showing %d total log entries.', 'rankout-connector' ),
                absint( $total )
            );
            ?>
            <button type="submit" class="button button-secondary wp-mcp-confirm-submit" data-confirm="<?php echo esc_attr( sprintf( /* translators: %d: retention days */ __( 'Delete all audit log entries older than %d days?', 'rankout-connector' ), absint( $retention ) ) ); ?>">
                <?php
                printf(
                    /* translators: %d: retention days */
                    esc_html__( 'Clean Up Entries Older Than %d Days', 'rankout-connector' ),
                    absint( $retention )
                );
                ?>
            </button>
        </p>
        <p class="description">
            <?php
            printf(
                /* translators: %s: link to settings page */
                wp_kses( __( 'Retention period is %s.', 'rankout-connector' ), array( 'a' => array( 'href' => array() ) ) ),
                sprintf(
                    '<a href="%s">%s</a>',
                    esc_url( admin_url( 'admin.php?page=rankout-connector-settings' ) ),
                    sprintf(
                        /* translators: %d: number of days */
                        esc_html__( 'set to %d days — change in Settings', 'rankout-connector' ),
                        absint( $retention )
                    )
                )
            );
            ?>
        </p>
    </form>

    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-date"><?php esc_html_e( 'Date', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-token"><?php esc_html_e( 'Token', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-tool"><?php esc_html_e( 'Tool', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-changes"><?php esc_html_e( 'Changes', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-arguments"><?php esc_html_e( 'Arguments', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-status"><?php esc_html_e( 'Status', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-ip"><?php esc_html_e( 'IP Address', 'rankout-connector' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $entries ) ) : ?>
                <tr>
                    <td colspan="7"><?php esc_html_e( 'No audit log entries found.', 'rankout-connector' ); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ( $entries as $entry ) : ?>
                    <?php
                    $args_display = '';
                    if ( ! empty( $entry['arguments'] ) ) {
                        $args = json_decode( $entry['arguments'], true );
                        if ( is_array( $args ) ) {
                            $pairs = array();
                            foreach ( $args as $key => $value ) {
                                if ( is_array( $value ) || is_object( $value ) ) {
                                    $value = wp_json_encode( $value );
                                }
                                $display_value = strlen( (string) $value ) > 50 ? substr( (string) $value, 0, 50 ) . '...' : (string) $value;
                                $pairs[]       = $key . '=' . $display_value;
                            }
                            $args_display = implode( ', ', $pairs );
                            if ( strlen( $args_display ) > 150 ) {
                                $args_display = substr( $args_display, 0, 150 ) . '...';
                            }
                        }
                    }
                    $is_error     = ! empty( $entry['result_status'] ) && 'error' === strtolower( $entry['result_status'] );
                    $token_display = ! empty( $entry['token_name'] ) ? $entry['token_name'] : '#' . $entry['token_id'];
                    ?>
                    <tr id="audit-<?php echo absint( $entry['id'] ); ?>">
                        <td class="column-date">
                            <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $entry['created_at'] . ' UTC' ) ) ); ?>
                        </td>
                        <td class="column-token">
                            <?php echo esc_html( $token_display ); ?>
                        </td>
                        <td class="column-tool">
                            <code><?php echo esc_html( $entry['tool_name'] ); ?></code>
                        </td>
                        <td class="column-changes">
                            <?php
                            $c = isset( $change_counts[ (int) $entry['id'] ] ) ? (int) $change_counts[ (int) $entry['id'] ] : 0;
                            
                            
                            $is_read_only_tool = isset( $entry['tool_name'] )
                                && preg_match( '/^wp_(list|get|search|count|history)_|^wp_search$/', $entry['tool_name'] );
                            if ( $c > 0 && ! $is_read_only_tool ) : ?>
                                <a href="#" class="emai-changes-toggle"
                                   data-audit-id="<?php echo absint( $entry['id'] ); ?>"
                                   aria-expanded="false">
                                    <?php
                                    /* translators: %d: change row count */
                                    echo esc_html( sprintf( _n( '%d change', '%d changes', $c, 'rankout-connector' ), $c ) );
                                    ?>
                                    <span class="emai-changes-caret" aria-hidden="true">▾</span>
                                </a>
                            <?php else : ?>
                                <span class="description">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="column-arguments">
                            <?php if ( ! empty( $args_display ) ) : ?>
                                <span title="<?php echo esc_attr( $entry['arguments'] ); ?>"><?php echo esc_html( $args_display ); ?></span>
                            <?php else : ?>
                                <span class="description"><?php esc_html_e( 'None', 'rankout-connector' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="column-status">
                            <?php if ( $is_error ) : ?>
                                <span class="wp-mcp-badge wp-mcp-badge-error"><?php esc_html_e( 'Error', 'rankout-connector' ); ?></span>
                            <?php else : ?>
                                <span class="wp-mcp-badge wp-mcp-badge-ok"><?php esc_html_e( 'OK', 'rankout-connector' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="column-ip">
                            <?php echo esc_html( $entry['ip_address'] ); ?>
                        </td>
                    </tr>
                    <tr class="emai-changes-detail" data-audit-id="<?php echo absint( $entry['id'] ); ?>" hidden>
                        <td colspan="7" style="background:#f6f7f7;">
                            <div class="emai-changes-detail-body">
                                <em><?php esc_html_e( 'Loading…', 'rankout-connector' ); ?></em>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col" class="manage-column column-date"><?php esc_html_e( 'Date', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-token"><?php esc_html_e( 'Token', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-tool"><?php esc_html_e( 'Tool', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-changes"><?php esc_html_e( 'Changes', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-arguments"><?php esc_html_e( 'Arguments', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-status"><?php esc_html_e( 'Status', 'rankout-connector' ); ?></th>
                <th scope="col" class="manage-column column-ip"><?php esc_html_e( 'IP Address', 'rankout-connector' ); ?></th>
            </tr>
        </tfoot>
    </table>

    <?php  ?>

    <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo wp_kses_post( paginate_links( array(
                    'base'      => add_query_arg( 'paged', '%#%' ),
                    'format'    => '',
                    'prev_text' => __( '&laquo; Previous', 'rankout-connector' ),
                    'next_text' => __( 'Next &raquo;', 'rankout-connector' ),
                    'total'     => $total_pages,
                    'current'   => $page,
                ) ) );
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php
}
rankout_connector_view_audit_log( $total, $entries, $page, $total_pages, $message, isset( $change_counts ) ? $change_counts : array(), isset( $changes_nonce ) ? $changes_nonce : '' );
