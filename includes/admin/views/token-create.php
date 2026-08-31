<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function rankout_connector_view_token_create( $users, $tools_by_cat, $token = null ) {
	$is_edit        = isset( $token ) && ! empty( $token );
$current_tools  = array();
if ( $is_edit ) {
    $current_tools = isset( $token['allowed_tools'] ) ? $token['allowed_tools'] : array();
    if ( is_string( $current_tools ) ) {
        $current_tools = json_decode( $current_tools, true );
    }
    if ( ! is_array( $current_tools ) ) {
        $current_tools = array();
    }
}
$is_all_tools = empty( $current_tools ) || in_array( '*', $current_tools, true );
$error        = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap wp-mcp-admin">
    <h1>
        <?php
        if ( $is_edit ) {
            esc_html_e( 'Edit Token', 'rankout-connector' );
        } else {
            esc_html_e( 'Create New Token', 'rankout-connector' );
        }
        ?>
    </h1>

    <?php include __DIR__ . '/partials/page-nav.php'; ?>

    <?php if ( 'name_required' === $error ) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e( 'Token name is required.', 'rankout-connector' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( 'create_failed' === $error ) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e( 'Failed to create token. Please try again.', 'rankout-connector' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( 'invalid_user' === $error ) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e( 'Invalid user selected. Tokens can only be assigned to administrators, editors, or authors.', 'rankout-connector' ); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=rankout-connector-tokens' ) ); ?>" class="wp-mcp-token-form">
        <?php if ( $is_edit ) : ?>
            <?php wp_nonce_field( 'rankout_connector_update_token' ); ?>
            <input type="hidden" name="rankout_connector_update_token" value="1">
            <input type="hidden" name="token_id" value="<?php echo esc_attr( $token['id'] ); ?>">
        <?php else : ?>
            <?php wp_nonce_field( 'rankout_connector_create_token' ); ?>
            <input type="hidden" name="rankout_connector_create_token" value="1">
        <?php endif; ?>

        <!-- Placeholder removed: was always submitting "*" defeating per-tool permissions.
             JS form-submit handler adds "*" dynamically only when select-all is checked. -->

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="token_name"><?php esc_html_e( 'Token Name', 'rankout-connector' ); ?></label>
                </th>
                <td>
                    <input type="text" id="token_name" name="token_name" class="regular-text"
                        value="<?php echo $is_edit ? esc_attr( $token['name'] ) : ''; ?>"
                        placeholder="<?php esc_attr_e( 'e.g., Claude Code - Production', 'rankout-connector' ); ?>" required>
                    <p class="description"><?php esc_html_e( 'A descriptive name to identify this token.', 'rankout-connector' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="wp_user_id"><?php esc_html_e( 'WordPress User', 'rankout-connector' ); ?></label>
                </th>
                <td>
                    <select id="wp_user_id" name="wp_user_id" class="regular-text">
                        <?php foreach ( $users as $user ) : ?>
                            <?php $roles_display = implode( ', ', array_map( 'ucfirst', $user->roles ) ); ?>
                            <option value="<?php echo esc_attr( $user->ID ); ?>" <?php if ( $is_edit ) { selected( $token['wp_user_id'], $user->ID ); } else { selected( get_current_user_id(), $user->ID ); } ?>>
                                <?php echo esc_html( $user->display_name ); ?> (<?php echo esc_html( $roles_display ); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'The WordPress user whose permissions this token will use when executing tools.', 'rankout-connector' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="expires_at"><?php esc_html_e( 'Expiration Date', 'rankout-connector' ); ?></label>
                </th>
                <td>
                    <?php
                    if ( $is_edit ) {
                        $expires_value = ! empty( $token['expires_at'] ) ? gmdate( 'Y-m-d', strtotime( $token['expires_at'] . ' UTC' ) ) : '';
                    } else {
                        $expires_value = gmdate( 'Y-m-d', strtotime( '+30 days' ) );
                    }
                    ?>
                    <input type="date" id="expires_at" name="expires_at" class="regular-text"
                        value="<?php echo esc_attr( $expires_value ); ?>"
                        min="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( '+1 day' ) ) ); ?>">
                    <p class="description"><?php esc_html_e( 'Leave empty for a token that never expires.', 'rankout-connector' ); ?></p>
                </td>
            </tr>

            <?php if ( $is_edit ) : ?>
                <tr>
                    <th scope="row">
                        <label for="is_active"><?php esc_html_e( 'Active', 'rankout-connector' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="is_active" name="is_active" value="1"
                                <?php checked( ! empty( $token['is_active'] ) ); ?>>
                            <?php esc_html_e( 'Token is active and can be used for authentication.', 'rankout-connector' ); ?>
                        </label>
                    </td>
                </tr>
            <?php endif; ?>

            <tr>
                <th scope="row"><?php esc_html_e( 'Tool Permissions', 'rankout-connector' ); ?></th>
                <td>
                    <fieldset>
                        <label class="wp-mcp-select-all">
                            <input type="checkbox" id="wp-mcp-select-all-tools" <?php checked( $is_all_tools || ! $is_edit ); ?>>
                            <strong><?php esc_html_e( 'Select All Tools (full access)', 'rankout-connector' ); ?></strong>
                        </label>

                        <div class="wp-mcp-filters" <?php echo ( $is_all_tools || ! $is_edit ) ? 'style="display:none;"' : ''; ?>>
                            <div class="wp-mcp-multiselect" id="wp-mcp-filter-category" data-default-label="<?php esc_attr_e( 'All Categories', 'rankout-connector' ); ?>">
                                <button type="button" class="wp-mcp-multiselect-btn" aria-haspopup="true" aria-expanded="false">
                                    <span class="wp-mcp-multiselect-label"><?php esc_html_e( 'All Categories', 'rankout-connector' ); ?></span>
                                    <span class="wp-mcp-multiselect-arrow" aria-hidden="true">&
                                </button>
                                <div class="wp-mcp-multiselect-dropdown">
                                    <?php foreach ( $tools_by_cat as $category => $tools ) : ?>
                                        <label class="wp-mcp-multiselect-option">
                                            <input type="checkbox" value="<?php echo esc_attr( $category ); ?>">
                                            <?php echo esc_html( ucfirst( $category ) ); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="wp-mcp-multiselect" id="wp-mcp-filter-action" data-default-label="<?php esc_attr_e( 'All Actions', 'rankout-connector' ); ?>">
                                <button type="button" class="wp-mcp-multiselect-btn" aria-haspopup="true" aria-expanded="false">
                                    <span class="wp-mcp-multiselect-label"><?php esc_html_e( 'All Actions', 'rankout-connector' ); ?></span>
                                    <span class="wp-mcp-multiselect-arrow" aria-hidden="true">&
                                </button>
                                <div class="wp-mcp-multiselect-dropdown">
                                    <label class="wp-mcp-multiselect-option"><input type="checkbox" value="list"> <?php esc_html_e( 'List', 'rankout-connector' ); ?></label>
                                    <label class="wp-mcp-multiselect-option"><input type="checkbox" value="get"> <?php esc_html_e( 'Get', 'rankout-connector' ); ?></label>
                                    <label class="wp-mcp-multiselect-option"><input type="checkbox" value="create"> <?php esc_html_e( 'Create', 'rankout-connector' ); ?></label>
                                    <label class="wp-mcp-multiselect-option"><input type="checkbox" value="update"> <?php esc_html_e( 'Update', 'rankout-connector' ); ?></label>
                                    <label class="wp-mcp-multiselect-option"><input type="checkbox" value="delete"> <?php esc_html_e( 'Delete', 'rankout-connector' ); ?></label>
                                    <label class="wp-mcp-multiselect-option"><input type="checkbox" value="search"> <?php esc_html_e( 'Search', 'rankout-connector' ); ?></label>
                                    <label class="wp-mcp-multiselect-option"><input type="checkbox" value="upload"> <?php esc_html_e( 'Upload', 'rankout-connector' ); ?></label>
                                </div>
                            </div>
                        </div>

                        <div class="wp-mcp-tools-tree wp-mcp-tools-grid" <?php echo ( $is_all_tools || ! $is_edit ) ? 'style="display:none;"' : ''; ?>>
                            <?php if ( ! empty( $tools_by_cat ) ) : ?>
                                <?php foreach ( $tools_by_cat as $category => $tools ) : ?>
                                    <div class="wp-mcp-tool-category">
                                        <label class="wp-mcp-category-toggle">
                                            <input type="checkbox" class="wp-mcp-category-checkbox" data-category="<?php echo esc_attr( $category ); ?>"
                                                <?php
                                                if ( $is_all_tools || ! $is_edit ) {
                                                    echo 'checked';
                                                } elseif ( $is_edit ) {
                                                    $cat_tool_names = array_map( function( $t ) { return $t['name']; }, $tools );
                                                    $all_checked    = ! array_diff( $cat_tool_names, $current_tools );
                                                    if ( $all_checked ) {
                                                        echo 'checked';
                                                    }
                                                }
                                                ?>>
                                            <strong><?php echo esc_html( ucfirst( $category ) ); ?></strong>
                                        </label>
                                        <div class="wp-mcp-tool-items">
                                            <?php foreach ( $tools as $tool ) : ?>
                                                <?php
                                                $tool_parts = explode( '_', $tool['name'], 3 );
                                                $action = isset( $tool_parts[1] ) ? $tool_parts[1] : '';
                                                ?>
                                                <label class="wp-mcp-tool-item">
                                                    <input type="checkbox" name="allowed_tools[]"
                                                        value="<?php echo esc_attr( $tool['name'] ); ?>"
                                                        class="wp-mcp-tool-checkbox" data-category="<?php echo esc_attr( $category ); ?>" data-action="<?php echo esc_attr( $action ); ?>"
                                                        <?php
                                                        if ( $is_all_tools || ! $is_edit ) {
                                                            echo 'checked';
                                                        } elseif ( in_array( $tool['name'], $current_tools, true ) ) {
                                                            echo 'checked';
                                                        }
                                                        ?>>
                                                    <code><?php echo esc_html( $tool['name'] ); ?></code>
                                                    <?php if ( ! empty( $tool['description'] ) ) : ?>
                                                        <span class="description">&mdash; <?php echo esc_html( $tool['description'] ); ?></span>
                                                    <?php endif; ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p class="description"><?php esc_html_e( 'No tools are currently registered.', 'rankout-connector' ); ?></p>
                            <?php endif; ?>
                        </div>

                        <ul class="description" style="list-style:disc;padding-left:1.2em;margin:4px 0;">
                            <li><?php esc_html_e( 'Uncheck "Select All" to choose individual tools.', 'rankout-connector' ); ?></li>
                            <li><?php esc_html_e( 'Only select tools you actually use — saves tokens and limits unintended changes.', 'rankout-connector' ); ?></li>
                            <li><strong><?php esc_html_e( 'Security notice:', 'rankout-connector' ); ?></strong> <?php esc_html_e( 'Granting write or delete permissions allows the AI to create, modify, or permanently remove content on your site. By enabling these tools you accept the associated risk. We strongly recommend manually reviewing every non-read-only permission before granting it.', 'rankout-connector' ); ?></li>
                        </ul>
                    </fieldset>
                </td>
            </tr>
        </table>

        <?php
        if ( $is_edit ) {
            submit_button( __( 'Update Token', 'rankout-connector' ) );
        } else {
            submit_button( __( 'Create Token', 'rankout-connector' ) );
        }
        ?>
    </form>
</div>
<?php
}
rankout_connector_view_token_create( $users, $tools_by_cat, isset( $token ) ? $token : null );
