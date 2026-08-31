<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}









?>
<div class="wrap wp-mcp-admin">
    <h1><?php esc_html_e( 'RankOut Connector - Change History', 'rankout-connector' ); ?></h1>

    <?php include __DIR__ . '/partials/page-nav.php'; ?>

    <?php
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only $_GET inspection for post-redirect notice.
    $rankout_connector_msg = isset( $_GET['message'] ) ? sanitize_key( $_GET['message'] ) : '';
    ?>
    <?php if ( 'cleaned' === $rankout_connector_msg ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Change history entries have been cleaned up.', 'rankout-connector' ); ?></p>
        </div>
    <?php elseif ( 'cleaned_more' === $rankout_connector_msg ) : ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php esc_html_e( 'Cleaned up 10,000 change history entries. More likely remain — click the button again to drain the rest.', 'rankout-connector' ); ?></p>
        </div>
    <?php endif; ?>

    <p class="description" style="margin: 8px 0 16px; font-size: 13px;">
        <?php esc_html_e( 'Before/after snapshots of every write performed through the MCP server. Direct wp-admin edits are NOT recorded here — see Audit Log for the per-call trace.', 'rankout-connector' ); ?>
    </p>

    <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="wp-mcp-history-filter">
        <input type="hidden" name="page" value="rankout-connector-history">
        <p>
            <label>
                <?php esc_html_e( 'Object type', 'rankout-connector' ); ?>
                <select name="object_type">
                    <option value=""><?php esc_html_e( 'All', 'rankout-connector' ); ?></option>
                    <?php foreach ( (array) $object_types as $rankout_connector_t ) : ?>
                        <option value="<?php echo esc_attr( $rankout_connector_t ); ?>" <?php selected( $filter_object_type, $rankout_connector_t ); ?>><?php echo esc_html( $rankout_connector_t ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            &nbsp;
            <label>
                <?php esc_html_e( 'Object ID', 'rankout-connector' ); ?>
                <input type="text" name="object_id" value="<?php echo esc_attr( $filter_object_id ); ?>" size="12">
            </label>
            &nbsp;
            <label>
                <?php esc_html_e( 'Tool', 'rankout-connector' ); ?>
                <input type="text" name="tool_name" value="<?php echo esc_attr( $filter_tool_name ); ?>" size="20">
            </label>
            &nbsp;
            <label>
                <?php esc_html_e( 'User ID', 'rankout-connector' ); ?>
                <input type="number" name="wp_user_id" value="<?php echo $filter_wp_user_id ? esc_attr( $filter_wp_user_id ) : ''; ?>" min="1" class="small-text">
            </label>
            &nbsp;
            <label>
                <?php esc_html_e( 'From', 'rankout-connector' ); ?>
                <input type="datetime-local" name="since" value="<?php echo esc_attr( str_replace( ' ', 'T', substr( $filter_since, 0, 16 ) ) ); ?>">
            </label>
            &nbsp;
            <label>
                <?php esc_html_e( 'To', 'rankout-connector' ); ?>
                <input type="datetime-local" name="until" value="<?php echo esc_attr( str_replace( ' ', 'T', substr( $filter_until, 0, 16 ) ) ); ?>">
            </label>
            <?php if ( $filter_audit_id > 0 ) : ?>
                <input type="hidden" name="audit_id" value="<?php echo esc_attr( $filter_audit_id ); ?>">
                <em>
                    <?php
                    printf(
                        /* translators: %d: audit log row id */
                        esc_html__( 'Filtered to audit row #%d.', 'rankout-connector' ),
                        absint( $filter_audit_id )
                    );
                    ?>
                </em>
            <?php endif; ?>
            <button class="button"><?php esc_html_e( 'Filter', 'rankout-connector' ); ?></button>
            <a class="button button-link" href="<?php echo esc_url( admin_url( 'admin.php?page=rankout-connector-history' ) ); ?>">
                <?php esc_html_e( 'Reset', 'rankout-connector' ); ?>
            </a>
        </p>
    </form>

    <?php $rankout_connector_retention = (int) get_option( 'rankout_connector_change_log_retention', 30 ); ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=rankout-connector-history' ) ); ?>" class="wp-mcp-cleanup-form">
        <?php wp_nonce_field( 'rankout_connector_cleanup_change_log' ); ?>
        <input type="hidden" name="rankout_connector_cleanup_change_log" value="1">
        <p>
            <?php
            printf(
                /* translators: %d: number of change-log entries */
                esc_html__( '%d total change-log entries match the current filter.', 'rankout-connector' ),
                absint( $total )
            );
            ?>
            <button type="submit" class="button button-secondary wp-mcp-confirm-submit" data-confirm="<?php echo esc_attr( sprintf( /* translators: %d: retention days */ __( 'Delete all change history entries older than %d days?', 'rankout-connector' ), absint( $rankout_connector_retention ) ) ); ?>">
                <?php
                printf(
                    /* translators: %d: retention days */
                    esc_html__( 'Clean Up Entries Older Than %d Days', 'rankout-connector' ),
                    absint( $rankout_connector_retention )
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
                        absint( $rankout_connector_retention )
                    )
                )
            );
            ?>
        </p>
    </form>

    <?php if ( $detail_row ) : ?>
        <div class="postbox" style="padding: 12px; margin: 16px 0;">
            <h2 style="margin-top:0;">
                <?php
                printf(
                    /* translators: 1: object type, 2: object id, 3: action verb */
                    esc_html__( 'Detail for change #%1$d: %2$s on %3$s/%4$s', 'rankout-connector' ),
                    absint( $detail_row['id'] ),
                    esc_html( $detail_row['action'] ),
                    esc_html( $detail_row['object_type'] ),
                    esc_html( $detail_row['object_id'] )
                );
                ?>
            </h2>
            <p>
                <strong><?php esc_html_e( 'Before', 'rankout-connector' ); ?></strong>
                <pre style="background:#f6f7f7; padding:8px; max-height:300px; overflow:auto;"><?php echo esc_html( (string) ( $detail_row['before_value'] ?? '' ) ); ?></pre>
            </p>
            <p>
                <strong><?php esc_html_e( 'After', 'rankout-connector' ); ?></strong>
                <pre style="background:#f6f7f7; padding:8px; max-height:300px; overflow:auto;"><?php echo esc_html( (string) ( $detail_row['after_value'] ?? '' ) ); ?></pre>
            </p>
            <?php if ( ! empty( $detail_row['revision_id'] ) ) : ?>
                <p>
                    <a class="button" href="<?php echo esc_url( admin_url( 'revision.php?revision=' . absint( $detail_row['revision_id'] ) ) ); ?>">
                        <?php esc_html_e( 'View WordPress revision', 'rankout-connector' ); ?>
                    </a>
                </p>
            <?php endif; ?>
            <p>
                <a class="button button-link" href="<?php echo esc_url( remove_query_arg( 'detail' ) ); ?>"><?php esc_html_e( 'Close detail', 'rankout-connector' ); ?></a>
            </p>
        </div>
    <?php endif; ?>

    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e( 'Date', 'rankout-connector' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Tool', 'rankout-connector' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Action', 'rankout-connector' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Object', 'rankout-connector' ); ?></th>
                <th scope="col"><?php esc_html_e( 'User', 'rankout-connector' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Auth', 'rankout-connector' ); ?></th>
                <th scope="col"><?php esc_html_e( 'Actions', 'rankout-connector' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $entries ) ) : ?>
                <tr><td colspan="7"><?php esc_html_e( 'No change-history entries yet.', 'rankout-connector' ); ?></td></tr>
            <?php else : foreach ( $entries as $rankout_connector_e ) : ?>
                <tr>
                    <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $rankout_connector_e['created_at'] . ' UTC' ) ) ); ?></td>
                    <td><code><?php echo esc_html( $rankout_connector_e['tool_name'] ); ?></code></td>
                    <td><?php echo esc_html( $rankout_connector_e['action'] ); ?></td>
                    <td>
                        <code><?php echo esc_html( $rankout_connector_e['object_type'] ); ?></code>:<code><?php echo esc_html( $rankout_connector_e['object_id'] ); ?></code>
                        <?php if ( ! empty( $rankout_connector_e['object_subtype'] ) ) : ?>
                            <br><small><?php echo esc_html( $rankout_connector_e['object_subtype'] ); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html( (string) ( $rankout_connector_e['wp_user_id'] ?? 0 ) ); ?></td>
                    <td><?php echo esc_html( $rankout_connector_e['auth_source'] ?? '' ); ?></td>
                    <td>
                        <a href="<?php echo esc_url( add_query_arg( 'detail', absint( $rankout_connector_e['id'] ) ) ); ?>"><?php esc_html_e( 'View before/after', 'rankout-connector' ); ?></a>
                        <?php if ( ! empty( $rankout_connector_e['audit_id'] ) ) : ?>
                            &nbsp;|&nbsp;
                            <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'rankout-connector-audit', 'audit_id' => absint( $rankout_connector_e['audit_id'] ) ), admin_url( 'admin.php' ) ) . '#audit-' . absint( $rankout_connector_e['audit_id'] ) ); ?>">
                                <?php esc_html_e( 'Originating call', 'rankout-connector' ); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

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
