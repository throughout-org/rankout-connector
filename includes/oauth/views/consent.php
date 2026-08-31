<?php




















if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


$rankout_connector_brand_bg         = '#f5f5ef'; 
$rankout_connector_brand_surface    = '#ffffff';
$rankout_connector_brand_ink        = '#0b1220'; 
$rankout_connector_brand_ink_soft   = '#4b5563';
$rankout_connector_brand_border     = '#e5e7eb';
$rankout_connector_brand_accent     = '#c8f542'; 
$rankout_connector_brand_accent_ink = '#0b1220';
$rankout_connector_brand_danger     = '#b42318';
$rankout_connector_brand_warn_bg    = '#fff8e1';
$rankout_connector_brand_warn_br    = '#e6c64a';
$rankout_connector_brand_info_bg    = '#f1f6ff';
$rankout_connector_brand_info_br    = '#c9dcf5';
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo esc_html__( 'Authorize Application', 'rankout-connector' ); ?> &mdash; <?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
</head>
<body style="font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',sans-serif;background:<?php echo esc_attr( $rankout_connector_brand_bg ); ?>;color:<?php echo esc_attr( $rankout_connector_brand_ink ); ?>;line-height:1.55;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:40px 20px;margin:0;">

<div style="background:<?php echo esc_attr( $rankout_connector_brand_surface ); ?>;border:1px solid <?php echo esc_attr( $rankout_connector_brand_border ); ?>;border-radius:14px;box-shadow:0 10px 40px rgba(11,18,32,0.06);max-width:620px;width:100%;overflow:hidden;">

    <div style="background:<?php echo esc_attr( $rankout_connector_brand_ink ); ?>;color:#fff;padding:28px 36px;display:flex;align-items:center;gap:14px;">
        <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;background:<?php echo esc_attr( $rankout_connector_brand_accent ); ?>;color:<?php echo esc_attr( $rankout_connector_brand_accent_ink ); ?>;font-weight:800;font-size:18px;letter-spacing:-0.5px;">W</span>
        <div>
            <h1 style="font-size:20px;font-weight:700;margin:0;letter-spacing:-0.3px;"><?php echo esc_html__( 'Authorize Application', 'rankout-connector' ); ?></h1>
            <span style="font-size:13px;opacity:0.7;"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
        </div>
    </div>

    <div style="padding:28px 36px;">

        <div style="border:1px solid <?php echo esc_attr( $rankout_connector_brand_border ); ?>;border-radius:10px;padding:18px 20px;margin-bottom:18px;background:#fafafa;">
            <div style="font-size:17px;font-weight:700;color:<?php echo esc_attr( $rankout_connector_brand_ink ); ?>;letter-spacing:-0.2px;"><?php echo esc_html( $client_name ); ?></div>
            <div style="font-size:12px;color:<?php echo esc_attr( $rankout_connector_brand_ink_soft ); ?>;margin-top:8px;">
                <?php echo esc_html__( 'Client ID:', 'rankout-connector' ); ?>
                <code style="background:#eef0f3;padding:2px 6px;border-radius:4px;font-size:11px;"><?php echo esc_html( $client_id_prefix ); ?>&hellip;</code>
                &nbsp;&middot;&nbsp;
                <?php echo esc_html__( 'Redirects to:', 'rankout-connector' ); ?>
                <code style="background:#eef0f3;padding:2px 6px;border-radius:4px;font-size:11px;"><?php echo esc_html( $redirect_host ); ?></code>
            </div>
        </div>

        <div style="background:<?php echo esc_attr( $rankout_connector_brand_warn_bg ); ?>;border:1px solid <?php echo esc_attr( $rankout_connector_brand_warn_br ); ?>;border-radius:10px;padding:14px 18px;margin-bottom:18px;font-size:13px;color:<?php echo esc_attr( $rankout_connector_brand_ink ); ?>;">
            <?php
            printf(
                /* translators: 1: user display name, 2: comma-separated role list */
                esc_html__( 'Acting as: %1$s (%2$s)', 'rankout-connector' ),
                '<strong>' . esc_html( $user_display_name ) . '</strong>',
                esc_html( $user_roles )
            );
            ?>
        </div>

        <div style="background:<?php echo esc_attr( $rankout_connector_brand_info_bg ); ?>;border:1px solid <?php echo esc_attr( $rankout_connector_brand_info_br ); ?>;border-radius:10px;padding:14px 18px;margin-bottom:22px;font-size:13px;color:<?php echo esc_attr( $rankout_connector_brand_ink ); ?>;line-height:1.6;">
            <?php
            printf(
                /* translators: 1: application name, 2: user display name */
                esc_html__( 'You are about to grant %1$s the permissions selected below. It will act on your behalf as %2$s and can read, create, update, or delete content on this site according to those permissions.', 'rankout-connector' ),
                '<strong>' . esc_html( $client_name ) . '</strong>',
                '<strong>' . esc_html( $user_display_name ) . '</strong>'
            );
            ?>
        </div>

        <?php if ( ! empty( $is_mcp_wildcard ) ) : ?>
        <div id="full-access-banner" style="background:#f9ffe6;border:1.5px solid <?php echo esc_attr( $rankout_connector_brand_accent ); ?>;border-radius:10px;padding:14px 18px;margin-bottom:16px;">
            <div style="font-size:14px;font-weight:700;color:<?php echo esc_attr( $rankout_connector_brand_ink ); ?>;margin-bottom:4px;">⚡ <?php echo esc_html__( 'Grant Full Access', 'rankout-connector' ); ?></div>
            <div style="font-size:12px;color:<?php echo esc_attr( $rankout_connector_brand_ink_soft ); ?>;margin-bottom:10px;line-height:1.6;"><?php echo esc_html__( 'Allow all tools — current & future. Includes all plugin integrations, external data connections (GA, GSC, SEMrush, DataforSEO), and WordPress Abilities.', 'rankout-connector' ); ?></div>
            <button type="button" id="btn-full-access" style="background:<?php echo esc_attr( $rankout_connector_brand_accent ); ?>;color:<?php echo esc_attr( $rankout_connector_brand_accent_ink ); ?>;border:none;border-radius:999px;padding:7px 18px;font-size:12px;font-weight:700;cursor:pointer;"><?php echo esc_html__( 'Select Full Access', 'rankout-connector' ); ?></button>
            <div id="full-access-note" style="display:none;margin-top:8px;font-size:11px;font-weight:600;color:<?php echo esc_attr( $rankout_connector_brand_ink ); ?>;"><?php echo esc_html__( 'Submitting with full access — all current and future tools will be allowed.', 'rankout-connector' ); ?></div>
        </div>
        <?php endif; ?>

        <div id="custom-scope-section">

        <div style="display:flex;align-items:center;gap:10px;margin:16px 0 14px;">
            <div style="flex:1;height:1px;background:<?php echo esc_attr( $rankout_connector_brand_border ); ?>;"></div>
            <div style="font-size:11px;color:<?php echo esc_attr( $rankout_connector_brand_ink_soft ); ?>;white-space:nowrap;font-weight:500;"><?php echo esc_html__( '— or choose specific scopes —', 'rankout-connector' ); ?></div>
            <div style="flex:1;height:1px;background:<?php echo esc_attr( $rankout_connector_brand_border ); ?>;"></div>
        </div>

        <div style="font-size:13px;font-weight:700;margin-bottom:10px;color:<?php echo esc_attr( $rankout_connector_brand_ink ); ?>;text-transform:uppercase;letter-spacing:0.6px;"><?php echo esc_html__( 'Requested Permissions', 'rankout-connector' ); ?></div>

        <table id="scope-table" style="width:100%;border-collapse:separate;border-spacing:0;margin-bottom:14px;font-size:13px;border:1px solid <?php echo esc_attr( $rankout_connector_brand_border ); ?>;border-radius:10px;overflow:hidden;">
            <thead>
                <tr>
                    <th style="background:#fafafa;border-bottom:1px solid <?php echo esc_attr( $rankout_connector_brand_border ); ?>;padding:10px 14px;text-align:left;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.6px;color:<?php echo esc_attr( $rankout_connector_brand_ink_soft ); ?>;"><?php echo esc_html__( 'Category', 'rankout-connector' ); ?></th>
                    <th style="background:#fafafa;border-bottom:1px solid <?php echo esc_attr( $rankout_connector_brand_border ); ?>;padding:10px 14px;text-align:center;width:80px;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.6px;color:<?php echo esc_attr( $rankout_connector_brand_ink_soft ); ?>;"><?php echo esc_html__( 'Read', 'rankout-connector' ); ?></th>
                    <th style="background:#fafafa;border-bottom:1px solid <?php echo esc_attr( $rankout_connector_brand_border ); ?>;padding:10px 14px;text-align:center;width:80px;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.6px;color:<?php echo esc_attr( $rankout_connector_brand_ink_soft ); ?>;"><?php echo esc_html__( 'Write', 'rankout-connector' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Loop-local variables inside a view template; not global scope.
                $has_ability_row = false;
                $row_index       = 0;
                foreach ( $categories as $category ) :
                    if ( ! empty( $category['plugin_required'] ) && ! \RankOut_Connector\OAuth\Scope_Map::is_plugin_category_active( $category['slug'] ) ) {
                        continue;
                    }
                    if ( ! empty( $category['is_ability'] ) ) {
                        $has_ability_row = true;
                    }

                    $read_scope  = isset( $category['read_scope'] ) ? $category['read_scope'] : '';
                    $write_scope = isset( $category['write_scope'] ) ? $category['write_scope'] : '';
                    $label       = isset( $category['label'] ) ? $category['label'] : '';

                    $read_checked  = $is_mcp_wildcard
                        || in_array( $read_scope, $scope_list, true )
                        || ( ! empty( $read_scope ) && false !== strpos( $default_scope, $read_scope ) );
                    $write_checked = $is_mcp_wildcard
                        || in_array( $write_scope, $scope_list, true )
                        || ( ! empty( $write_scope ) && false !== strpos( $default_scope, $write_scope ) );
                    $row_bg = ( $row_index % 2 === 0 ) ? '#ffffff' : '#fafafa';
                    $row_index++;
                ?>
                <tr>
                    <td style="border-top:1px solid <?php echo esc_attr( $rankout_connector_brand_border ); ?>;padding:10px 14px;font-weight:500;background:<?php echo esc_attr( $row_bg ); ?>;"><?php echo esc_html( $label ); ?></td>
                    <td style="border-top:1px solid <?php echo esc_attr( $rankout_connector_brand_border ); ?>;padding:10px 14px;text-align:center;background:<?php echo esc_attr( $row_bg ); ?>;">
                        <?php if ( ! empty( $read_scope ) ) : ?>
                            <input type="checkbox" name="scopes[]" value="<?php echo esc_attr( $read_scope ); ?>" class="scope-checkbox scope-read" style="width:16px;height:16px;cursor:pointer;accent-color:<?php echo esc_attr( $rankout_connector_brand_ink ); ?>;" <?php checked( $read_checked ); ?>>
                        <?php else : ?>
                            <span style="color:#9ca3af;">&mdash;</span>
                        <?php endif; ?>
                    </td>
                    <td style="border-top:1px solid <?php echo esc_attr( $rankout_connector_brand_border ); ?>;padding:10px 14px;text-align:center;background:<?php echo esc_attr( $row_bg ); ?>;">
                        <?php if ( ! empty( $write_scope ) ) : ?>
                            <input type="checkbox" name="scopes[]" value="<?php echo esc_attr( $write_scope ); ?>" class="scope-checkbox scope-write" style="width:16px;height:16px;cursor:pointer;accent-color:<?php echo esc_attr( $rankout_connector_brand_ink ); ?>;" <?php checked( $write_checked ); ?>>
                        <?php else : ?>
                            <span style="color:#9ca3af;">&mdash;</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
            </tbody>
        </table>

        <div style="display:flex;gap:8px;margin-bottom:0;flex-wrap:wrap;">
            <button type="button" id="btn-select-all" style="background:#fff;border:1px solid <?php echo esc_attr( $rankout_connector_brand_border ); ?>;border-radius:999px;padding:6px 14px;font-size:12px;color:<?php echo esc_attr( $rankout_connector_brand_ink ); ?>;cursor:pointer;font-weight:500;"><?php echo esc_html__( 'Select All', 'rankout-connector' ); ?></button>
            <button type="button" id="btn-read-only" style="background:#fff;border:1px solid <?php echo esc_attr( $rankout_connector_brand_border ); ?>;border-radius:999px;padding:6px 14px;font-size:12px;color:<?php echo esc_attr( $rankout_connector_brand_ink ); ?>;cursor:pointer;font-weight:500;"><?php echo esc_html__( 'Read Only', 'rankout-connector' ); ?></button>
            <button type="button" id="btn-reset-defaults" style="background:#fff;border:1px solid <?php echo esc_attr( $rankout_connector_brand_border ); ?>;border-radius:999px;padding:6px 14px;font-size:12px;color:<?php echo esc_attr( $rankout_connector_brand_ink ); ?>;cursor:pointer;font-weight:500;"><?php echo esc_html__( 'Reset to Defaults', 'rankout-connector' ); ?></button>
        </div>

        <?php if ( ! empty( $has_ability_row ) ) : ?>
        <div style="background:<?php echo esc_attr( $rankout_connector_brand_info_bg ); ?>;border:1px solid <?php echo esc_attr( $rankout_connector_brand_info_br ); ?>;border-radius:8px;padding:8px 12px;margin-top:10px;font-size:11px;color:<?php echo esc_attr( $rankout_connector_brand_ink ); ?>;">
            <?php echo esc_html__( 'Only abilities or plugins enabled by an administrator in RankOut Connector → Abilities or Plugins will be callable.', 'rankout-connector' ); ?>
        </div>
        <?php endif; ?>

        </div><!-- /custom-scope-section -->
    </div>

    <form method="post" action="<?php echo esc_url( home_url( '?rankout_connector_oauth=authorize' ) ); ?>">
        <?php wp_nonce_field( 'rankout_connector_oauth_consent_' . $client_id ); ?>

        <input type="hidden" name="response_type" value="<?php echo esc_attr( $request_params['response_type'] ); ?>">
        <input type="hidden" name="client_id" value="<?php echo esc_attr( $request_params['client_id'] ); ?>">
        <input type="hidden" name="redirect_uri" value="<?php echo esc_attr( $request_params['redirect_uri'] ); ?>">
        <input type="hidden" name="code_challenge" value="<?php echo esc_attr( $request_params['code_challenge'] ); ?>">
        <input type="hidden" name="code_challenge_method" value="<?php echo esc_attr( $request_params['code_challenge_method'] ); ?>">
        <input type="hidden" name="state" value="<?php echo esc_attr( $request_params['state'] ); ?>">
        <input type="hidden" name="resource" value="<?php echo esc_attr( $request_params['resource'] ); ?>">
        <input type="hidden" name="scope" value="<?php echo esc_attr( $request_params['scope'] ); ?>">
        <input type="hidden" name="scope_sig" value="<?php echo esc_attr( isset( $request_params['scope_sig'] ) ? $request_params['scope_sig'] : '' ); ?>">

        <div id="scope-hidden-fields"></div>

        <div style="padding:10px 36px 0;font-size:11px;color:<?php echo esc_attr( $rankout_connector_brand_ink_soft ); ?>;line-height:1.6;">
            <?php echo esc_html__( 'Approving lets this app act on your site within the permissions above. Ensure you review auto execution settings within your AI client and make necessary adjustments for each tool based on your risk tolerance. Post and page edits can be undone via WordPress revisions.', 'rankout-connector' ); ?>
        </div>

        <div style="display:flex;gap:12px;justify-content:flex-end;padding:20px 36px 28px;">
            <button type="submit" name="consent_action" value="deny" style="display:inline-block;padding:10px 22px;font-size:14px;font-weight:600;border-radius:999px;border:1px solid <?php echo esc_attr( $rankout_connector_brand_border ); ?>;cursor:pointer;background:#fff;color:<?php echo esc_attr( $rankout_connector_brand_ink ); ?>;">
                <?php echo esc_html__( 'Deny', 'rankout-connector' ); ?>
            </button>
            <button type="submit" name="consent_action" value="approve" style="display:inline-block;padding:10px 26px;font-size:14px;font-weight:700;border-radius:999px;border:1px solid <?php echo esc_attr( $rankout_connector_brand_ink ); ?>;cursor:pointer;background:<?php echo esc_attr( $rankout_connector_brand_accent ); ?>;color:<?php echo esc_attr( $rankout_connector_brand_accent_ink ); ?>;box-shadow:0 1px 0 <?php echo esc_attr( $rankout_connector_brand_ink ); ?>;">
                <?php echo esc_html__( 'Approve &amp; Continue', 'rankout-connector' ); ?>
            </button>
        </div>
    </form>
</div>

<div style="margin-top:18px;font-size:12px;color:<?php echo esc_attr( $rankout_connector_brand_ink_soft ); ?>;">
    <?php echo esc_html__( 'Powered by RankOut Connector', 'rankout-connector' ); ?>
</div>

<script nonce="<?php echo esc_attr( $script_nonce ); ?>">
(function() {
    'use strict';
    var table = document.getElementById('scope-table');
    var form = document.querySelector('form');
    var hiddenArea = document.getElementById('scope-hidden-fields');
    var customScopeSection = document.getElementById('custom-scope-section');
    var fullAccessMode = false;
    var btnFullAccess = document.getElementById('btn-full-access');
    var fullAccessNote = document.getElementById('full-access-note');
    var accentColor = '<?php echo esc_js( $rankout_connector_brand_accent ); ?>';
    var inkColor = '<?php echo esc_js( $rankout_connector_brand_ink ); ?>';
    var savedCheckboxState = null;

    function getCheckboxes(selector) { return table.querySelectorAll(selector || '.scope-checkbox'); }
    function setAll(checked) { var b = getCheckboxes(); for (var i = 0; i < b.length; i++) b[i].checked = checked; }
    function setReadOnly() { var r = getCheckboxes('.scope-read'), w = getCheckboxes('.scope-write'); for (var i = 0; i < r.length; i++) r[i].checked = true; for (var i = 0; i < w.length; i++) w[i].checked = false; }
    function resetDefaults() {
        var defaults = <?php echo wp_json_encode( array_filter( array_map( 'trim', explode( ' ', $default_scope ) ) ) ); ?>;
        var b = getCheckboxes(); for (var i = 0; i < b.length; i++) b[i].checked = (defaults.indexOf(b[i].value) !== -1) || (defaults.indexOf('mcp') !== -1);
    }
    function setCustomScopeVisible(visible) {
        customScopeSection.style.display = visible ? '' : 'none';
    }
    function resetFullAccessBanner() {
        fullAccessMode = false;
        if (!btnFullAccess) { setCustomScopeVisible(true); return; }
        btnFullAccess.textContent = '<?php echo esc_js( __( 'Select Full Access', 'rankout-connector' ) ); ?>';
        btnFullAccess.style.background = accentColor;
        btnFullAccess.style.color = inkColor;
        fullAccessNote.style.display = 'none';
        setCustomScopeVisible(true);
    }
    function syncScopes() {
        while (hiddenArea.firstChild) hiddenArea.removeChild(hiddenArea.firstChild);
        if (fullAccessMode) {
            var inp = document.createElement('input'); inp.type = 'hidden'; inp.name = 'scopes[]'; inp.value = 'mcp'; hiddenArea.appendChild(inp);
        } else {
            var b = getCheckboxes(); for (var i = 0; i < b.length; i++) { if (b[i].checked) { var inp = document.createElement('input'); inp.type = 'hidden'; inp.name = 'scopes[]'; inp.value = b[i].value; hiddenArea.appendChild(inp); } }
        }
    }

    if (btnFullAccess) btnFullAccess.addEventListener('click', function() {
        if (fullAccessMode) {
            resetFullAccessBanner();
            
            if (savedCheckboxState !== null) {
                var b = getCheckboxes();
                for (var i = 0; i < b.length; i++) { b[i].checked = savedCheckboxState[b[i].value] || false; }
                savedCheckboxState = null;
            }
            return;
        }
        
        savedCheckboxState = {};
        var b = getCheckboxes();
        for (var i = 0; i < b.length; i++) { savedCheckboxState[b[i].value] = b[i].checked; }
        fullAccessMode = true;
        setAll(true);
        setCustomScopeVisible(false);
        btnFullAccess.textContent = '✓ <?php echo esc_js( __( 'Full Access Selected — click to undo', 'rankout-connector' ) ); ?>';
        btnFullAccess.style.background = inkColor;
        btnFullAccess.style.color = '#ffffff';
        fullAccessNote.style.display = 'block';
    });
    document.getElementById('btn-select-all').addEventListener('click', function() { resetFullAccessBanner(); setAll(true); });
    document.getElementById('btn-read-only').addEventListener('click', function() { resetFullAccessBanner(); setReadOnly(); });
    document.getElementById('btn-reset-defaults').addEventListener('click', function() { resetFullAccessBanner(); resetDefaults(); });
    form.addEventListener('submit', syncScopes);
})();
</script>

</body>
</html>
