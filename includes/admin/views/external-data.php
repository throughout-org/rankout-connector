<?php






if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap">
    <h1><?php esc_html_e( 'RankOut Connector - External Data', 'rankout-connector' ); ?></h1>

    <?php include __DIR__ . '/partials/page-nav.php'; ?>

    <?php if ( 'semrush_invalid_key' === $message ) : ?>
        <?php // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template partial, $err is include-scoped not global. ?>
        <?php $err = sanitize_text_field( wp_unslash( $_GET['error'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of redirect param set by the save handler, no form processing. ?>
        <div style="margin:1em 0; padding:1em 1.5em; background:#fef7f7; border-left:4px solid #d63638; border-radius:2px;">
            <p style="margin:0; font-size:1em;"><strong style="color:#d63638;"><?php esc_html_e( '⚠ Semrush API key is invalid — the key was not saved.', 'rankout-connector' ); ?></strong><?php if ( $err ) { echo ' <span style="color:#50575e;">(' . esc_html( $err ) . ')</span>'; } ?></p>
            <p style="margin:.5em 0 0; color:#50575e; font-size:.9em;"><?php esc_html_e( 'Please check your API key and try again.', 'rankout-connector' ); ?></p>
        </div>
        <?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
    <?php elseif ( 'dfs_invalid_credentials' === $message ) : ?>
        <?php // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template partial, $err is include-scoped not global. ?>
        <?php $err = sanitize_text_field( wp_unslash( $_GET['error'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of redirect param set by the save handler, no form processing. ?>
        <div style="margin:1em 0; padding:1em 1.5em; background:#fef7f7; border-left:4px solid #d63638; border-radius:2px;">
            <p style="margin:0; font-size:1em;"><strong style="color:#d63638;"><?php esc_html_e( '⚠ DataforSEO credentials are invalid — they were not saved.', 'rankout-connector' ); ?></strong><?php if ( $err ) { echo ' <span style="color:#50575e;">(' . esc_html( $err ) . ')</span>'; } ?></p>
            <p style="margin:.5em 0 0; color:#50575e; font-size:.9em;"><?php esc_html_e( 'Please check your login and API password and try again.', 'rankout-connector' ); ?></p>
        </div>
        <?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
    <?php endif; ?>

    <?php if ( ! empty( $weak_salts ) ) : ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e( 'DataforSEO credentials cannot be saved: WordPress security salts are missing or set to placeholder values.', 'rankout-connector' ); ?></strong>
                <?php esc_html_e( 'Credential encryption requires unique SECURE_AUTH_KEY and SECURE_AUTH_SALT values in wp-config.php.', 'rankout-connector' ); ?>
            </p>
            <p>
                <?php esc_html_e( 'Fix options:', 'rankout-connector' ); ?>
                <strong><?php esc_html_e( 'WP-CLI:', 'rankout-connector' ); ?></strong> <code>wp config shuffle-salts</code> &mdash;
                <?php
                printf(
                    /* translators: %s: URL to WordPress salt generator */
                    esc_html__( 'or visit %s, copy all 8 lines, and replace the matching lines in wp-config.php.', 'rankout-connector' ),
                    '<a href="https://api.wordpress.org/secret-key/1.1/salt/" target="_blank" rel="noopener">api.wordpress.org/secret-key/1.1/salt/</a>'
                );
                ?>
                <?php esc_html_e( 'Note: rotating salts will log out all active WordPress sessions.', 'rankout-connector' ); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ( 'saved' === $message ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'removed' === $message ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Search Console credentials removed.', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'json_invalid' === $message ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Invalid JSON. Please re-paste the service account key from Google Cloud Console.', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'json_wrong_type' === $message ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'The pasted JSON is not a service account key (type must be "service_account"). Re-download the service account key from Google Cloud Console.', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'json_missing_field' === $message ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Search Console service account JSON is missing one of the required fields (private_key, client_email, token_uri).', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'ga_removed' === $message ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Google Analytics credentials removed.', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'ga_json_invalid' === $message ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Analytics: invalid JSON. Please re-paste the service account key from Google Cloud Console.', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'ga_json_wrong_type' === $message ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Analytics: the pasted JSON is not a service account key (type must be "service_account"). Re-download the service account key from Google Cloud Console.', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'ga_json_missing_field' === $message ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Analytics service account JSON is missing one of the required fields (private_key, client_email, token_uri).', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'ga_property_invalid' === $message ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Analytics: invalid property ID. Enter a numeric GA4 property ID (e.g. 123456789) or the full "properties/123456789" form.', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'gsc_site_url_invalid' === $message ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Search Console: invalid site URL. Use a full URL (https://example.com/) or a Domain Property (sc-domain:example.com).', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'dfs_removed' === $message ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'DataforSEO credentials removed.', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'dfs_partial_credentials' === $message ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'DataforSEO: both login and API password must be saved together.', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'dfs_saved' === $message ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'DataforSEO credentials saved.', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'semrush_removed' === $message ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Semrush API key removed.', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'semrush_saved' === $message ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Semrush API key saved.', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'semrush_weak_salts' === $message ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Semrush API key could not be saved: WordPress security salts are missing or set to placeholder values.', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'dfs_invalid_credentials' === $message ) : ?>
    <?php elseif ( 'dfs_weak_salts' === $message ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'DataforSEO credentials could not be saved: WordPress security salts are missing or set to placeholder values. Fix: run "wp config shuffle-salts" via WP-CLI, or visit api.wordpress.org/secret-key/1.1/salt/, copy all 8 lines, and replace the matching lines in wp-config.php. Then return here and re-enter your DataforSEO credentials.', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'ga_cache_cleared' === $message ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Google Analytics cache cleared. The next tool call will fetch fresh data from Google.', 'rankout-connector' ); ?></p></div>
    <?php elseif ( 'gsc_cache_cleared' === $message ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Search Console cache cleared. The next tool call will fetch fresh data from Google.', 'rankout-connector' ); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="rankout_connector_save_external_data">
        <?php wp_nonce_field( 'rankout_connector_external_data' ); ?>

        <div style="margin-top:1.5em; border:1px solid #c3c4c7; border-radius:4px; padding:1em 1.5em; background:#fff;">
            <h2 style="margin-top:0; font-size:1.1em; font-weight:600; padding-bottom:.5em; border-bottom:1px solid #f0f0f1; display:flex; align-items:center; justify-content:space-between; cursor:pointer;" onclick="(function(el){var body=document.getElementById('dfs-section-body');var open=body.style.display!=='none';body.style.display=open?'none':'';el.querySelector('.easy-mcp-toggle-icon').textContent=open?'▶':'▼';})(this)">
                <span>
                    <?php esc_html_e( 'DataforSEO', 'rankout-connector' ); ?>
                    <?php if ( $has_dfs_credentials ) : ?>
                        <span style="color:#00a32a; font-size:.85em; font-weight:normal; margin-left:.5em;"><?php esc_html_e( 'Credentials saved', 'rankout-connector' ); ?></span>
                        <?php if ( null !== $dfs_balance ) : ?>
                            <span style="font-size:.85em; font-weight:normal; margin-left:1em; color:#646970;">
                                <?php echo esc_html( 'Balance: $' . number_format( (float) $dfs_balance['balance'], 2 ) . ' USD' ); ?>
                                <button type="button" id="dfs-refresh-balance-btn" class="button button-small" style="margin-left:.5em;"><?php esc_html_e( 'Refresh', 'rankout-connector' ); ?></button>
                                <span id="dfs-balance-result" style="margin-left:.25em;"></span>
                            </span>
                        <?php else : ?>
                            <span style="font-size:.85em; font-weight:normal; margin-left:1em; color:#646970;"><?php esc_html_e( 'Balance: —', 'rankout-connector' ); ?></span>
                        <?php endif; ?>
                    <?php else : ?>
                        <span style="color:#d63638; font-size:.85em; font-weight:normal; margin-left:.5em;"><?php esc_html_e( 'Not configured', 'rankout-connector' ); ?></span>
                        <span style="color:#2271b1; font-size:.8em; font-weight:normal; margin-left:.75em; background:#f0f6fc; border:1px solid #c2d4e8; border-radius:3px; padding:1px 6px;"><?php esc_html_e( '$1 trial credit available', 'rankout-connector' ); ?></span>
                    <?php endif; ?>
                </span>
                <span class="easy-mcp-toggle-icon" style="font-size:.8em; color:#646970; user-select:none;">▶</span>
            </h2>

            <div id="dfs-section-body" style="display:none;">
            <p style="color:#646970; font-size:.9em; margin-top:0;">
                <?php esc_html_e( 'Sign up at', 'rankout-connector' ); ?> <a href="https://app.dataforseo.com/?aff=118024" target="_blank" rel="noopener noreferrer">app.dataforseo.com</a><span aria-hidden="true">*</span> <?php esc_html_e( 'and generate an API password at', 'rankout-connector' ); ?> <a href="https://app.dataforseo.com/api-access?aff=118024" target="_blank" rel="noopener noreferrer">app.dataforseo.com/api-access</a><span aria-hidden="true">*</span>. <?php esc_html_e( 'The API password is different from your account password.', 'rankout-connector' ); ?>
            </p>

            <div style="background:#fff8e5; border-left:4px solid #dba617; margin:.75em 0 1em; border-radius:0 4px 4px 0; overflow:hidden;">
                <button type="button" onclick="(function(btn){var body=btn.nextElementSibling;var open=body.style.display!=='none';body.style.display=open?'none':'block';btn.querySelector('.dfs-billing-icon').textContent=open?'▶':'▼';})(this)" style="width:100%; text-align:left; background:none; border:none; padding:.75em 1em; font-weight:600; color:#3c434a; cursor:pointer; display:flex; align-items:center; justify-content:space-between;">
                    <span><?php esc_html_e( '💰 Billing & Cost Monitoring', 'rankout-connector' ); ?></span>
                    <span class="dfs-billing-icon" style="font-size:.75em; color:#646970;">▶</span>
                </button>
                <div style="display:none; padding:0 1em .75em;">
                <p style="margin:0; font-size:.9em; color:#3c434a; line-height:1.6;">
                    <?php esc_html_e( 'DataforSEO is a paid, metered service — every API call deducts from your prepaid account balance. Each tool response includes a', 'rankout-connector' ); ?> <code>_cost_usd</code> <?php esc_html_e( 'field showing the exact cost of that call. Per-endpoint pricing varies — check the pricing page below for current rates before enabling tools.', 'rankout-connector' ); ?>
                </p>
                <p style="margin:.5em 0 0; font-size:.9em; color:#3c434a;">
                    <?php
                    echo wp_kses(
                        sprintf(
                            /* translators: 1: pricing page URL, 2: billing dashboard URL, 3: API stats page URL */
                            __( 'Full pricing: <a href="%1$s" target="_blank" rel="noopener noreferrer">dataforseo.com/pricing</a><span aria-hidden="true">*</span> · Top up balance & set cost limits: <a href="%2$s" target="_blank" rel="noopener noreferrer">app.dataforseo.com/billing</a><span aria-hidden="true">*</span> · Per-endpoint usage stats: <a href="%3$s" target="_blank" rel="noopener noreferrer">app.dataforseo.com/api-stats</a><span aria-hidden="true">*</span>', 'rankout-connector' ),
                            'https://dataforseo.com/pricing?aff=118024',
                            'https://app.dataforseo.com/billing?aff=118024',
                            'https://app.dataforseo.com/api-stats?aff=118024'
                        ),
                        array(
                            'a'    => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
                            'span' => array( 'aria-hidden' => array() ),
                        )
                    );
                    ?>
                </p>
                <?php if ( $has_dfs_credentials ) : ?>
                <p style="margin:.5em 0 0; font-size:.85em; color:#646970;">
                    <?php esc_html_e( 'Tip: use the wp_dfs_account_balance tool (or the Refresh button above) to monitor your remaining balance from inside an AI assistant. The balance is cached for 60 seconds.', 'rankout-connector' ); ?>
                </p>
                <?php endif; ?>
                </div>
            </div>

            <?php if ( ! $has_dfs_credentials ) : ?>
            <p style="margin-top:0; margin-bottom:1em;">
                <a href="https://app.dataforseo.com/?aff=118024" target="_blank" rel="noopener noreferrer" class="button button-secondary"><?php esc_html_e( 'Claim Your $1 Trial Credit *', 'rankout-connector' ); ?></a>
                <span style="display:block; margin-top:.4em; color:#646970; font-size:.85em;"><?php esc_html_e( 'No account yet? Start with $1 and explore the full DataforSEO API — no commitment required.', 'rankout-connector' ); ?></span>
            </p>
            <?php endif; ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Login (email)', 'rankout-connector' ); ?></th>
                    <td>
                        <?php if ( $has_dfs_credentials ) : ?>
                            <div id="dfs-key-saved" style="display:flex; align-items:center; gap:1em; flex-wrap:wrap;">
                                <span style="color:#00a32a; font-weight:600;"><?php echo esc_html( $dfs_login_display ); ?></span>
                                <button type="button" id="dfs-test-btn" class="button button-small"><?php esc_html_e( 'Test Connection', 'rankout-connector' ); ?></button>
                                <button type="button" class="button button-small" onclick="document.getElementById('dfs-key-saved').style.display='none'; document.getElementById('dfs-key-edit').style.display='block';"><?php esc_html_e( 'Replace credentials', 'rankout-connector' ); ?></button>
                                <button type="button" class="button button-small button-link-delete" onclick="if(confirm('<?php echo esc_js( __( 'Remove the saved DataforSEO credentials? AI clients will lose access to DataforSEO tools.', 'rankout-connector' ) ); ?>')){document.getElementById('dfs-remove-key-form').submit();}"><?php esc_html_e( 'Remove credentials', 'rankout-connector' ); ?></button>
                                <span id="dfs-test-result" style="margin-left:.25em;"></span>
                            </div>
                            <div id="dfs-key-edit" style="display:none;">
                                <input type="text" name="dfs_login" placeholder="<?php esc_attr_e( 'user@example.com', 'rankout-connector' ); ?>" class="regular-text">
                                <p class="description"><?php esc_html_e( 'Enter new login to replace.', 'rankout-connector' ); ?></p>
                            </div>
                        <?php else : ?>
                            <input type="text" name="dfs_login" placeholder="<?php esc_attr_e( 'user@example.com', 'rankout-connector' ); ?>" class="regular-text">
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'API Password', 'rankout-connector' ); ?></th>
                    <td>
                        <?php if ( $has_dfs_credentials ) : ?>
                            <div id="dfs-pwd-saved">
                                <span style="color:#00a32a; font-weight:600;"><?php esc_html_e( '••••••••', 'rankout-connector' ); ?></span>
                            </div>
                            <div id="dfs-pwd-edit" style="display:none;">
                                <input type="password" name="dfs_api_password" placeholder="<?php esc_attr_e( 'Enter your API password here', 'rankout-connector' ); ?>" class="regular-text">
                            </div>
                        <?php else : ?>
                            <input type="password" name="dfs_api_password" placeholder="<?php esc_attr_e( 'Enter your API password here', 'rankout-connector' ); ?>" class="regular-text">
                            <p class="description">
                                <?php
                                echo wp_kses(
                                    sprintf(
                                        /* translators: %s: URL to DataforSEO API access page */
                                        __( 'Get your API password at <a href="%s" target="_blank" rel="noopener noreferrer">app.dataforseo.com/api-access</a><span aria-hidden="true">*</span>. The API password is different from your account login password.', 'rankout-connector' ),
                                        'https://app.dataforseo.com/api-access?aff=118024'
                                    ),
                                    array(
                                        'a'    => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
                                        'span' => array( 'aria-hidden' => array() ),
                                    )
                                );
                                ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <p style="margin-top:1.25em; padding:.75em 1em; background:#f6f7f7; border-left:3px solid #c3c4c7; border-radius:0 4px 4px 0; font-size:.85em; color:#646970; line-height:1.6;">
                <strong>*</strong>
                <?php esc_html_e( 'Affiliate disclosure: links marked with an asterisk include a referral code. If you sign up to DataforSEO through one of these links we may receive a small commission, at no additional cost to you. These commissions help fund the ongoing development and maintenance of RankOut Connector, which is free to use.', 'rankout-connector' ); ?>
            </p>

            <?php submit_button( __( 'Save DataforSEO Settings', 'rankout-connector' ), 'primary', 'submit', false, array( 'style' => 'margin-top:1em;' ) ); ?>

            <?php if ( $has_dfs_credentials ) : ?>
                <script>
                (function(){
                    
                    var testBtn = document.getElementById('dfs-test-btn');
                    if ( testBtn ) {
                        testBtn.addEventListener('click', function() {
                            var btn    = this;
                            var result = document.getElementById('dfs-test-result');
                            btn.disabled = true;
                            result.textContent = '<?php echo esc_js( __( 'Testing…', 'rankout-connector' ) ); ?>';
                            result.style.color = '#646970';
                            fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                body: 'action=rankout_connector_dfs_test&nonce=<?php echo esc_js( wp_create_nonce( 'rankout_connector_dfs_test' ) ); ?>'
                            })
                            .then(function(r){
                                if ( ! r.ok ) { return r.text().then(function(t){ throw new Error( 'Server error ' + r.status + ': ' + t.substring(0,200) ); }); }
                                return r.json();
                            })
                            .then(function(data) {
                                result.textContent = data.data.message;
                                result.style.color = data.success ? '#00a32a' : '#d63638';
                            })
                            .catch(function(err) {
                                result.textContent = err.message || '<?php echo esc_js( __( 'Request failed.', 'rankout-connector' ) ); ?>';
                                result.style.color = '#d63638';
                            })
                            .finally(function() { btn.disabled = false; });
                        });
                    }
                    
                    var refreshBtn = document.getElementById('dfs-refresh-balance-btn');
                    if ( refreshBtn ) {
                        refreshBtn.addEventListener('click', function() {
                            var btn    = this;
                            var result = document.getElementById('dfs-balance-result');
                            btn.disabled = true;
                            result.textContent = '<?php echo esc_js( __( 'Refreshing…', 'rankout-connector' ) ); ?>';
                            result.style.color = '#646970';
                            fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                body: 'action=rankout_connector_dfs_refresh_balance&nonce=<?php echo esc_js( wp_create_nonce( 'rankout_connector_dfs_refresh_balance' ) ); ?>'
                            })
                            .then(function(r){ return r.json(); })
                            .then(function(data) {
                                if ( data.success ) {
                                    result.textContent = '$' + parseFloat(data.data.balance).toFixed(2) + ' USD';
                                    result.style.color = '#00a32a';
                                } else {
                                    result.textContent = data.data.message;
                                    result.style.color = '#d63638';
                                }
                            })
                            .catch(function(err) {
                                result.textContent = '<?php echo esc_js( __( 'Request failed.', 'rankout-connector' ) ); ?>';
                                result.style.color = '#d63638';
                            })
                            .finally(function() { btn.disabled = false; });
                        });
                    }
                })();
                </script>
            <?php endif; ?>

            <div style="margin-top:1.5em; border-top:1px solid #f0f0f1; padding-top:1em;">
                <button type="button" style="width:100%; text-align:left; background:#f6f7f7; border:1px solid #c3c4c7; border-radius:4px; padding:.6em .9em; cursor:pointer; display:flex; align-items:baseline; gap:.5em;" onmouseover="this.style.background='#edeeee'" onmouseout="this.style.background='#f6f7f7'" onclick="(function(el){var body=document.getElementById('dfs-tools-body');var open=body.style.display!=='none';body.style.display=open?'none':'block';el.querySelector('.easy-mcp-tools-toggle').textContent=open?'▶':'▼';el.querySelector('.easy-mcp-tools-hint').style.display=open?'inline':'none';})(this)">
                    <span class="easy-mcp-tools-toggle" style="font-size:.75em; color:#646970; user-select:none; flex-shrink:0;">▶</span>
                    <span style="font-size:1em; font-weight:600; color:#1d2327;"><?php esc_html_e( 'Available Tools', 'rankout-connector' ); ?></span>
                    <span class="easy-mcp-tools-hint" style="font-size:.85em; font-weight:normal; color:#646970;"><?php echo $has_dfs_credentials ? esc_html__( 'click to expand', 'rankout-connector' ) : esc_html__( 'Save credentials above to enable or disable individual tools. Tools are shown below for reference.', 'rankout-connector' ); ?></span>
                </button>
                <div id="dfs-tools-body" style="display:none;">
                <?php if ( $has_dfs_credentials ) : ?>
                <p style="color:#646970; margin-top:0; margin-bottom:.75em; font-size:.9em;"><?php esc_html_e( 'Enable or disable individual DataforSEO tools.', 'rankout-connector' ); ?></p>
                <?php endif; ?>
                <fieldset<?php if ( ! $has_dfs_credentials ) : ?> style="opacity:.5; pointer-events:none;"<?php endif; ?>>
                    <?php // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
                    <?php foreach ( $dfs_tools as $tool_name => $tool_label ) : ?>
                        <?php $enabled = $has_dfs_credentials && ! in_array( $tool_name, $dfs_disabled_tools, true ); ?>
                        <label style="display:block; margin-bottom:.5em;">
                            <input type="checkbox" name="dfs_enabled_tools[]" value="<?php echo esc_attr( $tool_name ); ?>"<?php checked( $enabled ); ?><?php disabled( ! $has_dfs_credentials ); ?>>
                            <strong><?php echo esc_html( $tool_name ); ?></strong>
                            <span style="color:#646970; margin-left:.25em;">&mdash; <?php echo esc_html( $tool_label ); ?></span>
                        </label>
                    <?php endforeach; ?>
                    <?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
                </fieldset>
                </div>
            </div>
            </div><!-- /dfs-section-body -->
        </div>

        <div style="margin-top:1.5em; border:1px solid #c3c4c7; border-radius:4px; padding:1em 1.5em; background:#fff;">
            <h2 style="margin-top:0; font-size:1.1em; font-weight:600; padding-bottom:.5em; border-bottom:1px solid #f0f0f1; display:flex; align-items:center; justify-content:space-between; cursor:pointer;" onclick="(function(el){var body=document.getElementById('semrush-section-body');var open=body.style.display!=='none';body.style.display=open?'none':'';el.querySelector('.easy-mcp-toggle-icon').textContent=open?'▶':'▼';})(this)">
                <span>
                    <?php esc_html_e( 'Semrush', 'rankout-connector' ); ?>
                    <?php if ( $has_semrush_credentials ) : ?>
                        <span style="color:#00a32a; font-size:.85em; font-weight:normal; margin-left:.5em;"><?php esc_html_e( 'API key saved', 'rankout-connector' ); ?></span>
                        <?php if ( null !== $semrush_balance ) : ?>
                            <span style="font-size:.85em; font-weight:normal; margin-left:1em; color:#646970;">
                                <?php echo esc_html( 'Balance: ' . (int) $semrush_balance['balance'] . ' API units' ); ?>
                                <button type="button" id="semrush-refresh-balance-btn" class="button button-small" style="margin-left:.5em;"><?php esc_html_e( 'Refresh', 'rankout-connector' ); ?></button>
                                <span id="semrush-balance-result" style="margin-left:.25em;"></span>
                            </span>
                        <?php else : ?>
                            <span style="font-size:.85em; font-weight:normal; margin-left:1em; color:#646970;"><?php esc_html_e( 'Balance: —', 'rankout-connector' ); ?></span>
                        <?php endif; ?>
                    <?php else : ?>
                        <span style="color:#d63638; font-size:.85em; font-weight:normal; margin-left:.5em;"><?php esc_html_e( 'Not configured', 'rankout-connector' ); ?></span>
                        <span style="color:#2271b1; font-size:.8em; font-weight:normal; margin-left:.75em; background:#f0f6fc; border:1px solid #c2d4e8; border-radius:3px; padding:1px 6px;"><?php esc_html_e( '14-day free trial available', 'rankout-connector' ); ?></span>
                    <?php endif; ?>
                </span>
                <span class="easy-mcp-toggle-icon" style="font-size:.8em; color:#646970; user-select:none;">▶</span>
            </h2>

            <div id="semrush-section-body" style="display:none;">
            <p style="color:#646970; font-size:.9em; margin-top:0;">
                <?php esc_html_e( 'Sign up at', 'rankout-connector' ); ?> <a href="https://easymcpai.com/go/semrush" target="_blank" rel="noopener noreferrer">semrush.com</a><span aria-hidden="true">*</span> <?php esc_html_e( 'on a plan that includes API access — the Semrush One plan is recommended. Retrieve the key at Profile → Subscription info → API units.', 'rankout-connector' ); ?>
            </p>

            <?php if ( ! $has_semrush_credentials ) : ?>
            <p style="margin-top:0; margin-bottom:1em;">
                <a href="https://easymcpai.com/go/semrush" target="_blank" rel="noopener noreferrer" class="button button-secondary"><?php esc_html_e( 'Start Your Free 14-Day Trial *', 'rankout-connector' ); ?></a>
                <span style="display:block; margin-top:.4em; color:#646970; font-size:.85em;"><?php esc_html_e( 'No API key yet? Try Semrush free for 14 days — no commitment required.', 'rankout-connector' ); ?></span>
            </p>
            <?php endif; ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e( 'API Key', 'rankout-connector' ); ?></th>
                    <td>
                        <?php if ( $has_semrush_credentials ) : ?>
                            <div id="semrush-key-saved" style="display:flex; align-items:center; gap:1em; flex-wrap:wrap;">
                                <span style="color:#00a32a; font-weight:600;"><?php echo esc_html( $semrush_api_key_masked ); ?></span>
                                <button type="button" id="semrush-test-btn" class="button button-small"><?php esc_html_e( 'Test Connection', 'rankout-connector' ); ?></button>
                                <button type="button" class="button button-small" onclick="document.getElementById('semrush-key-saved').style.display='none'; document.getElementById('semrush-key-edit').style.display='block';"><?php esc_html_e( 'Replace API key', 'rankout-connector' ); ?></button>
                                <button type="button" class="button button-small button-link-delete" onclick="if(confirm('<?php echo esc_js( __( 'Remove the saved Semrush API key? AI clients will lose access to Semrush tools.', 'rankout-connector' ) ); ?>')){document.getElementById('semrush-remove-key-form').submit();}"><?php esc_html_e( 'Remove API key', 'rankout-connector' ); ?></button>
                                <span id="semrush-test-result" style="margin-left:.25em;"></span>
                            </div>
                            <div id="semrush-key-edit" style="display:none;">
                                <input type="password" name="semrush_api_key" placeholder="<?php esc_attr_e( 'Enter your API key here', 'rankout-connector' ); ?>" class="regular-text">
                                <p class="description"><?php esc_html_e( 'Enter new API key to replace.', 'rankout-connector' ); ?></p>
                            </div>
                        <?php else : ?>
                            <input type="password" name="semrush_api_key" placeholder="<?php esc_attr_e( 'Enter your API key here', 'rankout-connector' ); ?>" class="regular-text">
                            <p class="description" style="margin-top:.4em;">
                                <?php
                                echo wp_kses(
                                    sprintf(
                                        /* translators: %s: Semrush affiliate URL */
                                        __( 'Get your API key from <a href="%s" target="_blank" rel="noopener noreferrer">Semrush</a><span aria-hidden="true">*</span> — go to Profile → Subscription info → API units.', 'rankout-connector' ),
                                        'https://easymcpai.com/go/semrush'
                                    ),
                                    array(
                                        'a'    => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
                                        'span' => array( 'aria-hidden' => array() ),
                                    )
                                );
                                ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <?php submit_button( __( 'Save Semrush Settings', 'rankout-connector' ), 'primary', 'submit', false, array( 'style' => 'margin-top:1em;' ) ); ?>

            <?php if ( $has_semrush_credentials ) : ?>
                <script>
                (function(){
                    var testBtn = document.getElementById('semrush-test-btn');
                    if ( testBtn ) {
                        testBtn.addEventListener('click', function() {
                            var btn    = this;
                            var result = document.getElementById('semrush-test-result');
                            btn.disabled = true;
                            result.textContent = '<?php echo esc_js( __( 'Testing…', 'rankout-connector' ) ); ?>';
                            result.style.color = '#646970';
                            fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                body: 'action=rankout_connector_semrush_test&nonce=<?php echo esc_js( wp_create_nonce( 'rankout_connector_semrush_test' ) ); ?>'
                            })
                            .then(function(r){ return r.json(); })
                            .then(function(data) {
                                result.textContent = data.data.message;
                                result.style.color = data.success ? '#00a32a' : '#d63638';
                            })
                            .catch(function(){ result.textContent = '<?php echo esc_js( __( 'Request failed.', 'rankout-connector' ) ); ?>'; result.style.color = '#d63638'; })
                            .finally(function(){ btn.disabled = false; });
                        });
                    }
                    var refreshBtn = document.getElementById('semrush-refresh-balance-btn');
                    if ( refreshBtn ) {
                        refreshBtn.addEventListener('click', function() {
                            var btn    = this;
                            var result = document.getElementById('semrush-balance-result');
                            btn.disabled = true;
                            result.textContent = '<?php echo esc_js( __( 'Refreshing…', 'rankout-connector' ) ); ?>';
                            result.style.color = '#646970';
                            fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                body: 'action=rankout_connector_semrush_refresh_balance&nonce=<?php echo esc_js( wp_create_nonce( 'rankout_connector_semrush_refresh_balance' ) ); ?>'
                            })
                            .then(function(r){ return r.json(); })
                            .then(function(data) {
                                if ( data.success ) {
                                    result.textContent = data.data.balance + ' API units';
                                    result.style.color = '#00a32a';
                                } else {
                                    result.textContent = data.data.message;
                                    result.style.color = '#d63638';
                                }
                            })
                            .catch(function(){ result.textContent = '<?php echo esc_js( __( 'Request failed.', 'rankout-connector' ) ); ?>'; result.style.color = '#d63638'; })
                            .finally(function(){ btn.disabled = false; });
                        });
                    }
                })();
                </script>
            <?php endif; ?>

            <div style="margin-top:1.5em; border-top:1px solid #f0f0f1; padding-top:1em;">
                <button type="button" style="width:100%; text-align:left; background:#f6f7f7; border:1px solid #c3c4c7; border-radius:4px; padding:.6em .9em; cursor:pointer; display:flex; align-items:baseline; gap:.5em;" onmouseover="this.style.background='#edeeee'" onmouseout="this.style.background='#f6f7f7'" onclick="(function(el){var body=document.getElementById('semrush-tools-body');var open=body.style.display!=='none';body.style.display=open?'none':'block';el.querySelector('.easy-mcp-tools-toggle').textContent=open?'▶':'▼';el.querySelector('.easy-mcp-tools-hint').style.display=open?'inline':'none';})(this)">
                    <span class="easy-mcp-tools-toggle" style="font-size:.75em; color:#646970; user-select:none; flex-shrink:0;">▶</span>
                    <span style="font-size:1em; font-weight:600; color:#1d2327;"><?php esc_html_e( 'Available Tools', 'rankout-connector' ); ?></span>
                    <span class="easy-mcp-tools-hint" style="font-size:.85em; font-weight:normal; color:#646970;"><?php echo $has_semrush_credentials ? esc_html__( 'click to expand', 'rankout-connector' ) : esc_html__( 'Save credentials above to enable or disable individual tools. Tools are shown below for reference.', 'rankout-connector' ); ?></span>
                </button>
                <div id="semrush-tools-body" style="display:none;">
                <?php if ( $has_semrush_credentials ) : ?>
                <p style="color:#646970; margin-top:0; margin-bottom:.75em; font-size:.9em;"><?php esc_html_e( 'Enable or disable individual Semrush tools.', 'rankout-connector' ); ?></p>
                <?php endif; ?>
                <fieldset<?php if ( ! $has_semrush_credentials ) : ?> style="opacity:.5; pointer-events:none;"<?php endif; ?>>
                    <?php // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
                    <?php foreach ( $semrush_tools as $tool_name => $tool_label ) : ?>
                        <?php $enabled = $has_semrush_credentials && ! in_array( $tool_name, $semrush_disabled_tools, true ); ?>
                        <label style="display:block; margin-bottom:.5em;">
                            <input type="checkbox" name="semrush_enabled_tools[]" value="<?php echo esc_attr( $tool_name ); ?>"<?php checked( $enabled ); ?><?php disabled( ! $has_semrush_credentials ); ?>>
                            <strong><?php echo esc_html( $tool_name ); ?></strong>
                            <span style="color:#646970; margin-left:.25em;">&mdash; <?php echo esc_html( $tool_label ); ?></span>
                        </label>
                    <?php endforeach; ?>
                    <?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
                </fieldset>
                </div>
            </div>

            <p style="margin-top:1.25em; padding:.75em 1em; background:#f6f7f7; border-left:3px solid #c3c4c7; border-radius:0 4px 4px 0; font-size:.85em; color:#646970; line-height:1.6;">
                <strong>*</strong>
                <?php esc_html_e( 'Affiliate disclosure: links marked with an asterisk include a referral code. If you sign up to Semrush through one of these links we may receive a small commission, at no additional cost to you. These commissions help fund the ongoing development and maintenance of RankOut Connector, which is free to use.', 'rankout-connector' ); ?>
            </p>
            </div><!-- /semrush-section-body -->
        </div>

        <div style="margin-top:1.5em; border:1px solid #c3c4c7; border-radius:4px; padding:1em 1.5em; background:#fff;">
            <h2 style="margin-top:0; font-size:1.1em; font-weight:600; padding-bottom:.5em; border-bottom:1px solid #f0f0f1; display:flex; align-items:center; justify-content:space-between; cursor:pointer;" onclick="(function(el){var body=document.getElementById('gsc-section-body');var open=body.style.display!=='none';body.style.display=open?'none':'';el.querySelector('.easy-mcp-toggle-icon').textContent=open?'▶':'▼';})(this)">
                <span>
                    <?php esc_html_e( 'Google Search Console', 'rankout-connector' ); ?>
                    <?php if ( $has_json ) : ?>
                        <span style="color:#00a32a; font-size:.85em; font-weight:normal; margin-left:.5em;"><?php esc_html_e( 'Credentials saved', 'rankout-connector' ); ?></span>
                    <?php else : ?>
                        <span style="color:#d63638; font-size:.85em; font-weight:normal; margin-left:.5em;"><?php esc_html_e( 'Not configured', 'rankout-connector' ); ?></span>
                    <?php endif; ?>
                </span>
                <span class="easy-mcp-toggle-icon" style="font-size:.8em; color:#646970; user-select:none;">▶</span>
            </h2>

            <div id="gsc-section-body" style="display:none;">
            <div style="background:#f6f7f7; border-left:4px solid #72aee6; margin:.5em 0 1em; border-radius:0 4px 4px 0; overflow:hidden;">
                <button type="button" onclick="(function(btn){var body=btn.nextElementSibling;var open=body.style.display!=='none';body.style.display=open?'none':'block';btn.querySelector('.easy-mcp-setup-icon').textContent=open?'▶':'▼';btn.querySelector('.easy-mcp-setup-label').textContent=open?'<?php echo esc_js( __( 'Click here to see how to set up Search Console', 'rankout-connector' ) ); ?>':'<?php echo esc_js( __( 'Hide setup instructions', 'rankout-connector' ) ); ?>';})(this)" style="width:100%; text-align:left; background:none; border:none; padding:.75em 1em; font-weight:600; color:#2271b1; cursor:pointer; display:flex; align-items:center; gap:.5em; text-decoration:underline; text-underline-offset:2px;">
                    <span class="easy-mcp-setup-icon" style="font-size:.75em; color:#2271b1; text-decoration:none;">▶</span>
                    <span class="easy-mcp-setup-label"><?php esc_html_e( 'Click here to see how to set up Search Console', 'rankout-connector' ); ?></span>
                </button>
                <div style="display:none; padding:0 1em .75em;">
                <ol style="margin:.25em 0 0 1.25em; padding:0; color:#3c434a; font-size:.9em; line-height:1.7;">
                    <?php /* translators: %s: URL to the Google Cloud Console Search Console API page */ ?>
                    <li><?php echo wp_kses( sprintf( __( '<a href="%s" target="_blank" rel="noopener noreferrer">Click here to enable the Search Console API</a> in Google Cloud Console.', 'rankout-connector' ), 'https://console.cloud.google.com/marketplace/product/google/searchconsole.googleapis.com' ), array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ); ?></li>
                    <li><?php esc_html_e( 'Click "Manage API", then navigate to Credentials → Manage Service Accounts.', 'rankout-connector' ); ?></li>
                    <li><?php esc_html_e( 'Create a service account (any name), then click Create and Done. No roles need to be assigned.', 'rankout-connector' ); ?></li>
                    <li><?php esc_html_e( 'Click the service account email → Keys tab → Add Key → Create New Key → JSON. Save the downloaded file.', 'rankout-connector' ); ?></li>
                    <li><?php esc_html_e( 'Open the JSON file in any text editor, then copy and paste its entire contents into the field below.', 'rankout-connector' ); ?></li>
                    <li><?php esc_html_e( 'In Google Search Console, go to Settings → Users and permissions and add the service account email as a User (Restricted or Full permissions).', 'rankout-connector' ); ?></li>
                </ol>
                <?php /* translators: %s: URL to the official Google Search Console setup guide */ ?>
                <p style="margin:.5em 0 0; font-size:.85em; color:#646970;"><?php echo wp_kses( sprintf( __( 'Need more detail? <a href="%s" target="_blank" rel="noopener noreferrer">View the official setup guide</a>.', 'rankout-connector' ), 'https://developers.google.com/webmaster-tools/v1/prereqs' ), array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ); ?></p>
                </div>
            </div>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Service Account Key', 'rankout-connector' ); ?></th>
                    <td>
                        <?php if ( $has_json ) : ?>
                            <div id="gsc-key-saved" style="display:flex; align-items:center; gap:1em; flex-wrap:wrap;">
                                <span style="color:#00a32a; font-weight:600;"><?php esc_html_e( 'Key saved', 'rankout-connector' ); ?></span>
                                <button type="button" id="gsc-test-btn" class="button button-small">
                                    <?php esc_html_e( 'Test Connection', 'rankout-connector' ); ?>
                                </button>
                                <button type="button" class="button button-small" onclick="document.getElementById('gsc-key-saved').style.display='none'; document.getElementById('gsc-key-edit').style.display='block';">
                                    <?php esc_html_e( 'Replace key', 'rankout-connector' ); ?>
                                </button>
                                <button type="button" class="button button-small" onclick="if(confirm('<?php echo esc_js( __( 'Clear all cached Search Console data? Credentials and settings stay intact. The next tool call will fetch fresh data from Google.', 'rankout-connector' ) ); ?>')){document.getElementById('gsc-clear-cache-form').submit();}" title="<?php esc_attr_e( 'Force-refresh all cached GSC responses (sites, sitemaps, query results) and OAuth token. Useful when external Google permissions change.', 'rankout-connector' ); ?>">
                                    <?php esc_html_e( 'Clear cache', 'rankout-connector' ); ?>
                                </button>
                                <button type="button" class="button button-small button-link-delete" onclick="if(confirm('<?php echo esc_js( __( 'Remove the saved Search Console service account key? AI clients will lose access to GSC tools until a new key is saved.', 'rankout-connector' ) ); ?>')){document.getElementById('gsc-remove-key-form').submit();}">
                                    <?php esc_html_e( 'Remove key', 'rankout-connector' ); ?>
                                </button>
                                <span id="gsc-test-result" style="margin-left:.25em;"></span>
                            </div>
                            <div id="gsc-key-edit" style="display:none;">
                                <textarea name="gsc_service_account_json" rows="8" cols="60" class="large-text code" placeholder='{"type":"service_account","project_id":"...","private_key":"...","client_email":"...@....iam.gserviceaccount.com",...}'></textarea>
                                <p class="description"><?php esc_html_e( 'Paste a new key to replace the existing one.', 'rankout-connector' ); ?></p>
                            </div>
                        <?php else : ?>
                            <textarea id="gsc_service_account_json" name="gsc_service_account_json" rows="8" cols="60" class="large-text code" placeholder='{"type":"service_account","project_id":"...","private_key":"...","client_email":"...@....iam.gserviceaccount.com",...}'></textarea>
                            <p class="description"><?php esc_html_e( 'Paste the full JSON key file from Google Cloud Console.', 'rankout-connector' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gsc_default_site_url"><?php esc_html_e( 'Default Property URL', 'rankout-connector' ); ?></label></th>
                    <td>
                        <?php if ( ! empty( $gsc_sites ) ) : ?>
                            <select id="gsc_default_site_url" name="gsc_default_site_url" class="regular-text">
                                <?php foreach ( $gsc_sites as $gsc_site ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
                                    <option value="<?php echo esc_attr( $gsc_site ); ?>" <?php selected( $site_url, $gsc_site ); ?>>
                                        <?php echo esc_html( $gsc_site ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ( $has_json ) : ?>
                            <input type="text" id="gsc_default_site_url" name="gsc_default_site_url" value="<?php echo esc_attr( $site_url ); ?>" class="regular-text" placeholder="sc-domain:example.com or https://example.com/">
                        <?php else : ?>
                            <input type="text" disabled class="regular-text" placeholder="<?php esc_attr_e( 'Save a service account key above to auto-populate', 'rankout-connector' ); ?>">
                        <?php endif; ?>
                        <p class="description"><?php esc_html_e( 'Required unless the AI assistant supplies a site_url on each call. Fallback used by GSC tools when site_url is omitted.', 'rankout-connector' ); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button( __( 'Save Search Console Settings', 'rankout-connector' ), 'primary', 'submit', false, array( 'style' => 'margin-top:1em;' ) ); ?>

            <?php if ( $has_json ) : ?>
                <script>
                (function(){
                    var testBtn = document.getElementById('gsc-test-btn');
                    if ( ! testBtn ) { return; }
                    testBtn.addEventListener('click', function() {
                            var btn    = this;
                            var result = document.getElementById('gsc-test-result');
                            btn.disabled = true;
                            result.textContent = '<?php echo esc_js( __( 'Testing\xe2\x80\xa6', 'rankout-connector' ) ); ?>';
                            result.style.color = '#646970';
                            fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                body: 'action=rankout_connector_gsc_test&nonce=<?php echo esc_js( wp_create_nonce( 'rankout_connector_gsc_test' ) ); ?>'
                            })
                            .then(function(r){
                                if ( ! r.ok ) {
                                    return r.text().then(function(t){ throw new Error( 'Server error ' + r.status + ': ' + t.substring(0,200) ); });
                                }
                                return r.json();
                            })
                            .then(function(data) {
                                result.textContent = data.data.message;
                                result.style.color = data.success ? '#00a32a' : '#d63638';
                            })
                            .catch(function(err) {
                                result.textContent = err.message || '<?php echo esc_js( __( 'Request failed.', 'rankout-connector' ) ); ?>';
                                result.style.color = '#d63638';
                            })
                            .finally(function() { testBtn.disabled = false; });
                    });
                })();
                </script>
            <?php endif; ?>

            <div style="margin-top:1.5em; border-top:1px solid #f0f0f1; padding-top:1em;">
                <button type="button" style="width:100%; text-align:left; background:#f6f7f7; border:1px solid #c3c4c7; border-radius:4px; padding:.6em .9em; cursor:pointer; display:flex; align-items:baseline; gap:.5em;" onmouseover="this.style.background='#edeeee'" onmouseout="this.style.background='#f6f7f7'" onclick="(function(el){var body=document.getElementById('gsc-tools-body');var open=body.style.display!=='none';body.style.display=open?'none':'block';el.querySelector('.easy-mcp-tools-toggle').textContent=open?'▶':'▼';el.querySelector('.easy-mcp-tools-hint').style.display=open?'inline':'none';})(this)">
                    <span class="easy-mcp-tools-toggle" style="font-size:.75em; color:#646970; user-select:none; flex-shrink:0;">▶</span>
                    <span style="font-size:1em; font-weight:600; color:#1d2327;"><?php esc_html_e( 'Available Tools', 'rankout-connector' ); ?></span>
                    <span class="easy-mcp-tools-hint" style="font-size:.85em; font-weight:normal; color:#646970;"><?php echo $has_json ? esc_html__( 'click to expand', 'rankout-connector' ) : esc_html__( 'Save credentials above to enable or disable individual tools. Tools are shown below for reference.', 'rankout-connector' ); ?></span>
                </button>
                <div id="gsc-tools-body" style="display:none;">
                <?php if ( $has_json ) : ?>
                <p style="color:#646970; margin-top:0; margin-bottom:.75em; font-size:.9em;">
                    <?php esc_html_e( 'Enable or disable individual Search Console tools.', 'rankout-connector' ); ?>
                </p>
                <?php endif; ?>
                <fieldset<?php if ( ! $has_json ) : ?> style="opacity:.5; pointer-events:none;"<?php endif; ?>>
                    <?php // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Loop-local view vars, not globals; PHPCS flags them only because view files have no enclosing function. ?>
                    <?php foreach ( $gsc_tools as $tool_name => $tool_label ) : ?>
                        <?php $enabled = $has_json && ! in_array( $tool_name, $gsc_disabled_tools, true ); ?>
                        <label style="display:block; margin-bottom:.5em;">
                            <input type="checkbox" name="gsc_enabled_tools[]" value="<?php echo esc_attr( $tool_name ); ?>"<?php checked( $enabled ); ?><?php disabled( ! $has_json ); ?>>
                            <strong><?php echo esc_html( $tool_name ); ?></strong>
                            <span style="color:#646970; margin-left:.25em;">&mdash; <?php echo esc_html( $tool_label ); ?></span>
                        </label>
                    <?php endforeach; ?>
                    <?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
                </fieldset>
                </div>
            </div>
            </div><!-- /gsc-section-body -->
        </div>

        <div style="margin-top:1.5em; border:1px solid #c3c4c7; border-radius:4px; padding:1em 1.5em; background:#fff;">
            <h2 style="margin-top:0; font-size:1.1em; font-weight:600; padding-bottom:.5em; border-bottom:1px solid #f0f0f1; display:flex; align-items:center; justify-content:space-between; cursor:pointer;" onclick="(function(el){var body=document.getElementById('ga-section-body');var open=body.style.display!=='none';body.style.display=open?'none':'';el.querySelector('.easy-mcp-toggle-icon').textContent=open?'▶':'▼';})(this)">
                <span>
                    <?php esc_html_e( 'Google Analytics 4', 'rankout-connector' ); ?>
                    <?php if ( $has_ga_json ) : ?>
                        <span style="color:#00a32a; font-size:.85em; font-weight:normal; margin-left:.5em;"><?php esc_html_e( 'Credentials saved', 'rankout-connector' ); ?></span>
                    <?php else : ?>
                        <span style="color:#d63638; font-size:.85em; font-weight:normal; margin-left:.5em;"><?php esc_html_e( 'Not configured', 'rankout-connector' ); ?></span>
                    <?php endif; ?>
                </span>
                <span class="easy-mcp-toggle-icon" style="font-size:.8em; color:#646970; user-select:none;">▶</span>
            </h2>

            <div id="ga-section-body" style="display:none;">
            <div style="background:#f6f7f7; border-left:4px solid #72aee6; margin:.5em 0 1em; border-radius:0 4px 4px 0; overflow:hidden;">
                <button type="button" onclick="(function(btn){var body=btn.nextElementSibling;var open=body.style.display!=='none';body.style.display=open?'none':'block';btn.querySelector('.easy-mcp-setup-icon').textContent=open?'▶':'▼';btn.querySelector('.easy-mcp-setup-label').textContent=open?'<?php echo esc_js( __( 'Click here to see how to set up Google Analytics', 'rankout-connector' ) ); ?>':'<?php echo esc_js( __( 'Hide setup instructions', 'rankout-connector' ) ); ?>';})(this)" style="width:100%; text-align:left; background:none; border:none; padding:.75em 1em; font-weight:600; color:#2271b1; cursor:pointer; display:flex; align-items:center; gap:.5em; text-decoration:underline; text-underline-offset:2px;">
                    <span class="easy-mcp-setup-icon" style="font-size:.75em; color:#2271b1; text-decoration:none;">▶</span>
                    <span class="easy-mcp-setup-label"><?php esc_html_e( 'Click here to see how to set up Google Analytics', 'rankout-connector' ); ?></span>
                </button>
                <div style="display:none; padding:0 1em .75em;">
                <ol style="margin:.25em 0 0 1.25em; padding:0; color:#3c434a; font-size:.9em; line-height:1.7;">
                    <?php /* translators: 1: URL to Google Analytics Data API page, 2: URL to Google Analytics Admin API page */ ?>
                    <li><?php echo wp_kses( sprintf( __( 'Enable both required APIs in Google Cloud Console: <a href="%1$s" target="_blank" rel="noopener noreferrer">Google Analytics Data API</a> and <a href="%2$s" target="_blank" rel="noopener noreferrer">Google Analytics Admin API</a>. All tools are read-only — Viewer access is sufficient.', 'rankout-connector' ), 'https://console.cloud.google.com/marketplace/product/google/analyticsdata.googleapis.com', 'https://console.cloud.google.com/marketplace/product/google/analyticsadmin.googleapis.com' ), array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ); ?></li>
                    <li><?php esc_html_e( 'In either API page, click "Manage API", then navigate to Credentials → Manage Service Accounts.', 'rankout-connector' ); ?></li>
                    <li><?php esc_html_e( 'Create a service account (any name), then click Create and Done. No roles need to be assigned.', 'rankout-connector' ); ?></li>
                    <li><?php esc_html_e( 'Click the service account email → Keys tab → Add Key → Create New Key → JSON. Save the downloaded file.', 'rankout-connector' ); ?></li>
                    <li><?php esc_html_e( 'Open the JSON file in any text editor, then copy and paste its entire contents into the field below.', 'rankout-connector' ); ?></li>
                    <li><?php esc_html_e( 'In Google Analytics 4, go to Admin → Property Access Management and add the service account email with Viewer (or higher) permissions.', 'rankout-connector' ); ?></li>
                </ol>
                <?php /* translators: %s: URL to the official Google Analytics setup guide */ ?>
                <p style="margin:.5em 0 0; font-size:.85em; color:#646970;"><?php echo wp_kses( sprintf( __( 'Need more detail? <a href="%s" target="_blank" rel="noopener noreferrer">View the official setup guide</a>.', 'rankout-connector' ), 'https://developers.google.com/analytics/devguides/reporting/data/v1/quickstart-client-libraries' ), array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ); ?></p>
                </div>
            </div>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Service Account Key', 'rankout-connector' ); ?></th>
                    <td>
                        <?php if ( $has_ga_json ) : ?>
                            <div id="ga-key-saved" style="display:flex; align-items:center; gap:1em; flex-wrap:wrap;">
                                <span style="color:#00a32a; font-weight:600;"><?php esc_html_e( 'Key saved', 'rankout-connector' ); ?></span>
                                <button type="button" id="ga-test-btn" class="button button-small">
                                    <?php esc_html_e( 'Test Connection', 'rankout-connector' ); ?>
                                </button>
                                <button type="button" class="button button-small" onclick="document.getElementById('ga-key-saved').style.display='none'; document.getElementById('ga-key-edit').style.display='block';">
                                    <?php esc_html_e( 'Replace key', 'rankout-connector' ); ?>
                                </button>
                                <button type="button" class="button button-small" onclick="if(confirm('<?php echo esc_js( __( 'Clear all cached Google Analytics data? Credentials and settings stay intact. The next tool call will fetch fresh data from Google.', 'rankout-connector' ) ); ?>')){document.getElementById('ga-clear-cache-form').submit();}" title="<?php esc_attr_e( 'Force-refresh all cached GA responses (account summaries, dimensions, metrics, data streams, metadata) and OAuth token. Useful when external Google permissions change.', 'rankout-connector' ); ?>">
                                    <?php esc_html_e( 'Clear cache', 'rankout-connector' ); ?>
                                </button>
                                <button type="button" class="button button-small button-link-delete" onclick="if(confirm('<?php echo esc_js( __( 'Remove the saved Google Analytics service account key? AI clients will lose access to GA tools until a new key is saved.', 'rankout-connector' ) ); ?>')){document.getElementById('ga-remove-key-form').submit();}">
                                    <?php esc_html_e( 'Remove key', 'rankout-connector' ); ?>
                                </button>
                                <span id="ga-test-result" style="margin-left:.25em;"></span>
                            </div>
                            <div id="ga-key-edit" style="display:none;">
                                <textarea name="ga_service_account_json" rows="8" cols="60" class="large-text code" placeholder='{"type":"service_account","project_id":"...","private_key":"...","client_email":"...@....iam.gserviceaccount.com",...}'></textarea>
                                <p class="description"><?php esc_html_e( 'Paste a new key to replace the existing one.', 'rankout-connector' ); ?></p>
                            </div>
                        <?php else : ?>
                            <textarea id="ga_service_account_json" name="ga_service_account_json" rows="8" cols="60" class="large-text code" placeholder='{"type":"service_account","project_id":"...","private_key":"...","client_email":"...@....iam.gserviceaccount.com",...}'></textarea>
                            <p class="description"><?php esc_html_e( 'Paste the full JSON key file from Google Cloud Console.', 'rankout-connector' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="ga_default_property_id"><?php esc_html_e( 'Default Property ID', 'rankout-connector' ); ?></label></th>
                    <td>
                        <?php if ( ! empty( $ga_properties ) ) : ?>
                            <?php
                            
                            
                            $ga_saved_id = ltrim( str_replace( 'properties/', '', $ga_property_id ), '/' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                            ?>
                            <select id="ga_default_property_id" name="ga_default_property_id" class="regular-text">
                                <?php foreach ( $ga_properties as $ga_prop ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
                                    <option value="<?php echo esc_attr( $ga_prop['id'] ); ?>" <?php selected( $ga_saved_id, $ga_prop['id'] ); ?>>
                                        <?php echo esc_html( $ga_prop['label'] ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ( $has_ga_json ) : ?>
                            <input type="text" id="ga_default_property_id" name="ga_default_property_id" value="<?php echo esc_attr( $ga_property_id ); ?>" class="regular-text" placeholder="123456789">
                        <?php else : ?>
                            <input type="text" disabled class="regular-text" placeholder="<?php esc_attr_e( 'Save a service account key above to auto-populate', 'rankout-connector' ); ?>">
                        <?php endif; ?>
                        <p class="description"><?php esc_html_e( 'Numeric GA4 property ID (found in GA4 Admin → Property details). Required unless the AI assistant supplies a property_id on each call. Fallback used by GA tools when property_id is omitted.', 'rankout-connector' ); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button( __( 'Save Google Analytics Settings', 'rankout-connector' ), 'primary', 'submit', false, array( 'style' => 'margin-top:1em;' ) ); ?>

            <?php if ( $has_ga_json ) : ?>
                <script>
                (function(){
                    var testBtn = document.getElementById('ga-test-btn');
                    if ( ! testBtn ) { return; }
                    testBtn.addEventListener('click', function() {
                            var btn    = this;
                            var result = document.getElementById('ga-test-result');
                            btn.disabled = true;
                            result.textContent = '<?php echo esc_js( __( 'Testing…', 'rankout-connector' ) ); ?>';
                            result.style.color = '#646970';
                            fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                body: 'action=rankout_connector_ga_test&nonce=<?php echo esc_js( wp_create_nonce( 'rankout_connector_ga_test' ) ); ?>'
                            })
                            .then(function(r){
                                if ( ! r.ok ) {
                                    return r.text().then(function(t){ throw new Error( 'Server error ' + r.status + ': ' + t.substring(0,200) ); });
                                }
                                return r.json();
                            })
                            .then(function(data) {
                                result.textContent = data.data.message;
                                result.style.color = data.success ? '#00a32a' : '#d63638';
                            })
                            .catch(function(err) {
                                result.textContent = err.message || '<?php echo esc_js( __( 'Request failed.', 'rankout-connector' ) ); ?>';
                                result.style.color = '#d63638';
                            })
                            .finally(function() { testBtn.disabled = false; });
                    });
                })();
                </script>
            <?php endif; ?>

            <div style="margin-top:1.5em; border-top:1px solid #f0f0f1; padding-top:1em;">
                <button type="button" style="width:100%; text-align:left; background:#f6f7f7; border:1px solid #c3c4c7; border-radius:4px; padding:.6em .9em; cursor:pointer; display:flex; align-items:baseline; gap:.5em;" onmouseover="this.style.background='#edeeee'" onmouseout="this.style.background='#f6f7f7'" onclick="(function(el){var body=document.getElementById('ga-tools-body');var open=body.style.display!=='none';body.style.display=open?'none':'block';el.querySelector('.easy-mcp-tools-toggle').textContent=open?'▶':'▼';el.querySelector('.easy-mcp-tools-hint').style.display=open?'inline':'none';})(this)">
                    <span class="easy-mcp-tools-toggle" style="font-size:.75em; color:#646970; user-select:none; flex-shrink:0;">▶</span>
                    <span style="font-size:1em; font-weight:600; color:#1d2327;"><?php esc_html_e( 'Available Tools', 'rankout-connector' ); ?></span>
                    <span class="easy-mcp-tools-hint" style="font-size:.85em; font-weight:normal; color:#646970;"><?php echo $has_ga_json ? esc_html__( 'click to expand', 'rankout-connector' ) : esc_html__( 'Save credentials above to enable or disable individual tools. Tools are shown below for reference.', 'rankout-connector' ); ?></span>
                </button>
                <div id="ga-tools-body" style="display:none;">
                <?php if ( $has_ga_json ) : ?>
                <p style="color:#646970; margin-top:0; margin-bottom:.75em; font-size:.9em;">
                    <?php esc_html_e( 'Enable or disable individual Google Analytics tools.', 'rankout-connector' ); ?>
                </p>
                <?php endif; ?>
                <fieldset<?php if ( ! $has_ga_json ) : ?> style="opacity:.5; pointer-events:none;"<?php endif; ?>>
                    <?php // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Loop-local view vars, not globals. ?>
                    <?php foreach ( $ga_tools as $tool_name => $tool_label ) : ?>
                        <?php $enabled = $has_ga_json && ! in_array( $tool_name, $ga_disabled_tools, true ); ?>
                        <label style="display:block; margin-bottom:.5em;">
                            <input type="checkbox" name="ga_enabled_tools[]" value="<?php echo esc_attr( $tool_name ); ?>"<?php checked( $enabled ); ?><?php disabled( ! $has_ga_json ); ?>>
                            <strong><?php echo esc_html( $tool_name ); ?></strong>
                            <span style="color:#646970; margin-left:.25em;">&mdash; <?php echo esc_html( $tool_label ); ?></span>
                        </label>
                    <?php endforeach; ?>
                    <?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
                </fieldset>
                </div>
            </div>
            </div><!-- /ga-section-body -->
        </div>

        <?php if ( 'semrush_invalid_key' === $message ) : ?>
            <?php // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template partial, include-scoped. ?>
            <?php $err = sanitize_text_field( wp_unslash( $_GET['error'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of redirect param, no form processing. ?>
            <div style="margin:0 0 1em; padding:.75em 1em; background:#fef7f7; border-left:4px solid #d63638; border-radius:2px;">
                <strong style="color:#d63638;"><?php esc_html_e( '⚠ Semrush API key is invalid — the key was not saved.', 'rankout-connector' ); ?></strong><?php if ( $err ) { echo ' <span style="color:#50575e; font-size:.9em;">(' . esc_html( $err ) . ')</span>'; } ?>
            </div>
            <?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
        <?php elseif ( 'dfs_invalid_credentials' === $message ) : ?>
            <?php // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template partial, include-scoped. ?>
            <?php $err = sanitize_text_field( wp_unslash( $_GET['error'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of redirect param, no form processing. ?>
            <div style="margin:0 0 1em; padding:.75em 1em; background:#fef7f7; border-left:4px solid #d63638; border-radius:2px;">
                <strong style="color:#d63638;"><?php esc_html_e( '⚠ DataforSEO credentials are invalid — they were not saved.', 'rankout-connector' ); ?></strong><?php if ( $err ) { echo ' <span style="color:#50575e; font-size:.9em;">(' . esc_html( $err ) . ')</span>'; } ?>
            </div>
            <?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
        <?php endif; ?>
    </form>

    <?php if ( $has_json ) : ?>
    <form id="gsc-remove-key-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="rankout_connector_remove_gsc_key">
        <?php wp_nonce_field( 'rankout_connector_remove_gsc_key' ); ?>
    </form>
    <form id="gsc-clear-cache-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="rankout_connector_clear_gsc_cache">
        <?php wp_nonce_field( 'rankout_connector_clear_gsc_cache' ); ?>
    </form>
    <?php endif; ?>

    <?php if ( $has_ga_json ) : ?>
    <form id="ga-remove-key-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="rankout_connector_remove_ga_key">
        <?php wp_nonce_field( 'rankout_connector_remove_ga_key' ); ?>
    </form>
    <form id="ga-clear-cache-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="rankout_connector_clear_ga_cache">
        <?php wp_nonce_field( 'rankout_connector_clear_ga_cache' ); ?>
    </form>
    <?php endif; ?>

    <?php if ( $has_dfs_credentials ) : ?>
    <form id="dfs-remove-key-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="rankout_connector_remove_dfs_key">
        <?php wp_nonce_field( 'rankout_connector_remove_dfs_key' ); ?>
    </form>
    <?php endif; ?>

    <?php if ( $has_semrush_credentials ) : ?>
    <form id="semrush-remove-key-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="rankout_connector_remove_semrush_key">
        <?php wp_nonce_field( 'rankout_connector_remove_semrush_key' ); ?>
    </form>
    <?php endif; ?>
</div>
