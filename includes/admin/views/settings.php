<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function rankout_connector_view_settings( $settings, $all_tool_names, $message, $ip_invalid = '' ) {
?>
<div class="wrap wp-mcp-admin">
    <h1><?php esc_html_e( 'RankOut Connector - Settings', 'rankout-connector' ); ?></h1>

    <?php include __DIR__ . '/partials/page-nav.php'; ?>

    <p class="description" style="margin: 8px 0 16px; font-size: 13px;">
        <?php
        printf(
            /* translators: %s: link to the dashboard page with the AI client setup guide. */
            esc_html__( 'To connect an MCP client, see the %s.', 'rankout-connector' ),
            '<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector' ) ) . '">' . esc_html__( 'setup guide', 'rankout-connector' ) . '</a>'
        );
        ?>
    </p>

    <?php if ( 'saved' === $message ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'rankout-connector' ); ?></p></div>
    <?php endif; ?>

    <?php
    $ip_invalid_raw = $ip_invalid;
    if ( ! empty( $ip_invalid_raw ) ) :
        $ip_invalid_entries = array_filter( array_map( 'trim', explode( ',', $ip_invalid_raw ) ) );
        if ( ! empty( $ip_invalid_entries ) ) :
    ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <?php esc_html_e( 'The following IP whitelist entries were invalid and have been removed:', 'rankout-connector' ); ?>
                <?php foreach ( $ip_invalid_entries as $i => $inv ) : ?><?php if ( $i > 0 ) : ?>, <?php endif; ?><code><?php echo esc_html( $inv ); ?></code><?php endforeach; ?>
            </p>
        </div>
    <?php endif; endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=rankout-connector-settings' ) ); ?>">
        <?php wp_nonce_field( 'rankout_connector_save_settings' ); ?>
        <input type="hidden" name="rankout_connector_save_settings" value="1">

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="admin_language"><?php esc_html_e( 'Admin Language', 'rankout-connector' ); ?></label>
                </th>
                <td>
                    <div class="wp-mcp-lang-select" data-placeholder="<?php esc_attr_e( 'Search language…', 'rankout-connector' ); ?>">
                        <input type="hidden" id="admin_language" name="admin_language" value="<?php echo esc_attr( $settings['admin_language'] ); ?>" />
                        <input type="text" id="admin_language_display" class="wp-mcp-lang-display" autocomplete="off" placeholder="<?php esc_attr_e( 'Search language…', 'rankout-connector' ); ?>" value="<?php
                            $rankout_connector_current_lang = $settings['admin_language'];
                            $rankout_connector_languages = array(
                                ''      => __( 'Default (WordPress language)', 'rankout-connector' ),
                                'en_US' => 'English',
                                'ar'    => 'العربية',
                                'bg_BG' => 'Български',
                                'bn_BD' => 'বাংলা',
                                'cs_CZ' => 'Čeština',
                                'da_DK' => 'Dansk',
                                'de_DE' => 'Deutsch',
                                'el'    => 'Ελληνικά',
                                'es_ES' => 'Español',
                                'es_MX' => 'Español (México)',
                                'et'    => 'Eesti',
                                'fa_IR' => 'فارسی',
                                'fi'    => 'Suomi',
                                'fr_FR' => 'Français',
                                'gu'    => 'ગુજરાતી',
                                'he_IL' => 'עברית',
                                'hi_IN' => 'हिन्दी',
                                'hr'    => 'Hrvatski',
                                'hu_HU' => 'Magyar',
                                'id_ID' => 'Bahasa Indonesia',
                                'it_IT' => 'Italiano',
                                'ja'    => '日本語',
                                'kn'    => 'ಕನ್ನಡ',
                                'ko_KR' => '한국어',
                                'lt_LT' => 'Lietuvių',
                                'lv'    => 'Latviešu',
                                'ml_IN' => 'മലയാളം',
                                'mr'    => 'मराठी',
                                'ms_MY' => 'Bahasa Melayu',
                                'nb_NO' => 'Norsk (Bokmål)',
                                'nl_NL' => 'Nederlands',
                                'pa_IN' => 'ਪੰਜਾਬੀ',
                                'pl_PL' => 'Polski',
                                'pt_BR' => 'Português (Brasil)',
                                'pt_PT' => 'Português (Portugal)',
                                'ro_RO' => 'Română',
                                'ru_RU' => 'Русский',
                                'sk_SK' => 'Slovenčina',
                                'sr_RS' => 'Српски',
                                'sv_SE' => 'Svenska',
                                'sw'    => 'Kiswahili',
                                'ta_IN' => 'தமிழ்',
                                'te'    => 'తెలుగు',
                                'th'    => 'ไทย',
                                'tl'    => 'Filipino',
                                'tr_TR' => 'Türkçe',
                                'uk'    => 'Українська',
                                'ur'    => 'اردو',
                                'vi'    => 'Tiếng Việt',
                                'zh_CN' => '中文 (简体)',
                                'zh_TW' => '中文 (繁體)',
                            );
                            echo esc_attr( isset( $rankout_connector_languages[ $rankout_connector_current_lang ] ) ? $rankout_connector_languages[ $rankout_connector_current_lang ] : '' );
                        ?>" />
                        <ul class="wp-mcp-lang-options" role="listbox">
                            <?php foreach ( $rankout_connector_languages as $code => $name ) : ?>
                                <li role="option" data-value="<?php echo esc_attr( $code ); ?>" data-label="<?php echo esc_attr( $name ); ?>"<?php if ( $code === $rankout_connector_current_lang ) echo ' class="wp-mcp-lang-active"'; ?>><?php echo esc_html( $name ); ?> <span class="wp-mcp-lang-code"><?php echo esc_html( $code ); ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <p class="description"><?php esc_html_e( 'Language for the RankOut Connector admin interface. Defaults to the WordPress site language.', 'rankout-connector' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rate_limit_per_minute"><?php esc_html_e( 'Rate Limit (per minute)', 'rankout-connector' ); ?></label>
                </th>
                <td>
                    <input type="number" id="rate_limit_per_minute" name="rate_limit_per_minute" min="1" max="1000"
                        value="<?php echo esc_attr( $settings['rate_limit_per_minute'] ); ?>" class="small-text">
                    <p class="description"><?php esc_html_e( 'Maximum number of tool calls per token per minute.', 'rankout-connector' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Force Draft on Create', 'rankout-connector' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="force_draft_on_create" value="1"
                            <?php checked( $settings['force_draft_on_create'], true ); ?>>
                        <?php esc_html_e( 'Always save new posts and pages as draft, regardless of the status requested by the MCP tool.', 'rankout-connector' ); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="max_title_length"><?php esc_html_e( 'Max Title Length (characters)', 'rankout-connector' ); ?></label>
                </th>
                <td>
                    <input type="number" id="max_title_length" name="max_title_length" min="0" max="2000"
                        value="<?php echo esc_attr( $settings['max_title_length'] ); ?>" class="small-text">
                    <p class="description"><?php esc_html_e( 'Maximum character length for post and page titles. Set to 0 to disable the limit. If a title exceeds this length the tool returns an error.', 'rankout-connector' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Audit Log', 'rankout-connector' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="audit_log_enabled" value="1"
                            <?php checked( $settings['audit_log_enabled'], true ); ?>>
                        <?php esc_html_e( 'Enable audit logging of all tool calls. When disabled, no log entries are written.', 'rankout-connector' ); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="audit_log_retention"><?php esc_html_e( 'Audit Log Retention (days)', 'rankout-connector' ); ?></label>
                </th>
                <td>
                    <input type="number" id="audit_log_retention" name="audit_log_retention" min="1" max="365"
                        value="<?php echo esc_attr( $settings['audit_log_retention'] ); ?>" class="small-text">
                    <p class="description"><?php esc_html_e( 'Older audit log entries are pruned daily by cron.', 'rankout-connector' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Change History', 'rankout-connector' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="change_log_enabled" value="1"
                            <?php checked( $settings['change_log_enabled'], true ); ?>>
                        <?php esc_html_e( 'Record before/after snapshots of every write performed via MCP. When disabled, no change-history entries are written.', 'rankout-connector' ); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="change_log_retention"><?php esc_html_e( 'Change History Retention (days)', 'rankout-connector' ); ?></label>
                </th>
                <td>
                    <input type="number" id="change_log_retention" name="change_log_retention" min="1" max="3650"
                        value="<?php echo esc_attr( $settings['change_log_retention'] ); ?>" class="small-text">
                    <p class="description"><?php esc_html_e( 'Older change-history rows are pruned daily by cron.', 'rankout-connector' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Disabled Tools', 'rankout-connector' ); ?></th>
                <td>
                    <?php
                    $delete_tools = array(
                        'wp_delete_post',
                        'wp_delete_page',
                        'wp_delete_media',
                        'wp_delete_comment',
                        'wp_delete_category',
                        'wp_delete_tag',
                        'wp_delete_user',
                        'wp_delete_block',
                        'wp_delete_cpt_item',
                        'wp_delete_menu',
                        'wp_delete_menu_item',
                        'wp_delete_revision',
                        'wp_create_user',
                        'wp_update_user',
                    );
                    foreach ( $delete_tools as $tool_name ) :
                    ?>
                        <label class="wp-mcp-block-label">
                            <input type="checkbox" name="disabled_tools[]" value="<?php echo esc_attr( $tool_name ); ?>"
                                <?php checked( in_array( $tool_name, $settings['disabled_tools'], true ) ); ?>>
                            <code><?php echo esc_html( $tool_name ); ?></code>
                        </label>
                    <?php endforeach; ?>
                    <p class="description"><?php esc_html_e( 'Checked tools are globally disabled and will return an error when called, regardless of token permissions.', 'rankout-connector' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="allowed_tool_patterns"><?php esc_html_e( 'Whitelist Tools', 'rankout-connector' ); ?></label>
                </th>
                <td>
                    <div class="notice notice-warning inline wp-mcp-notice-warning">
                        <strong><?php esc_html_e( '⚠️ Be careful:', 'rankout-connector' ); ?></strong>
                        <?php esc_html_e( 'When set, only tools whose names match one of these glob patterns are accessible via MCP — for all tokens. This can break existing workflows. Leave empty to allow all tools.', 'rankout-connector' ); ?>
                    </div>
                    <input type="text" id="allowed_tool_patterns" name="allowed_tool_patterns"
                        class="regular-text"
                        value="<?php echo esc_attr( implode( ', ', $settings['allowed_tool_patterns'] ) ); ?>"
                        placeholder="<?php esc_attr_e( 'e.g. wp_get_*, wp_list_*, wp_search_*', 'rankout-connector' ); ?>">
                    <p class="description"><?php esc_html_e( 'Comma-separated glob patterns (e.g. wp_get_* or wp_list_*) matched against tool names using fnmatch(). A tool is accessible if its name matches any pattern. Use * as a wildcard.', 'rankout-connector' ); ?></p>

                    <div class="wp-mcp-mt-14">
                    <?php
                    if ( empty( $settings['allowed_tool_patterns'] ) ) :
                    ?>
                        <p><?php
                    /* translators: %d: number of registered tools */
                    printf( esc_html__( 'All %d tools are available (no filter applied).', 'rankout-connector' ), count( $all_tool_names ) ); ?></p>
                    <?php else :
                        $matching = array_values( array_filter( $all_tool_names, function ( $name ) use ( $settings ) {
                            foreach ( $settings['allowed_tool_patterns'] as $p ) {
                                $p = trim( $p );
                                if ( false === strpos( $p, '*' ) && false === strpos( $p, '?' ) ) {
                                    $p = '*' . $p . '*';
                                }
                                if ( fnmatch( $p, $name ) ) {
                                    return true;
                                }
                            }
                            return false;
                        } ) );
                        $blocked_count = count( $all_tool_names ) - count( $matching );
                    ?>
                        <p>
                            <strong><?php
                            /* translators: %d: number of available tools */
                            printf( esc_html__( '%d tools will be available', 'rankout-connector' ), count( $matching ) ); ?></strong>
                            <?php if ( $blocked_count > 0 ) : ?>
                                <span class="wp-mcp-text-danger"> &mdash; <?php
                                /* translators: %d: number of blocked tools */
                                printf( esc_html__( '%d blocked', 'rankout-connector' ), absint( $blocked_count ) ); ?></span>
                            <?php endif; ?>:
                        </p>
                        <?php if ( ! empty( $matching ) ) : ?>
                            <ul class="wp-mcp-column-list">
                                <?php foreach ( $matching as $tn ) : ?>
                                    <li><code><?php echo esc_html( $tn ); ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else : ?>
                            <p class="wp-mcp-text-danger"><strong><?php esc_html_e( 'Warning: No tools match the current filter. MCP clients will see an empty tool list.', 'rankout-connector' ); ?></strong></p>
                        <?php endif; ?>
                    <?php endif; ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="ip_whitelist"><?php esc_html_e( 'IP Whitelist', 'rankout-connector' ); ?></label>
                </th>
                <td>
                    <textarea id="ip_whitelist" name="ip_whitelist" rows="4" cols="50" class="large-text code"><?php echo esc_textarea( $settings['ip_whitelist'] ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'One IP address or CIDR range per line (e.g., 203.0.113.10 or 192.168.1.0/24). Leave empty to allow all IPs.', 'rankout-connector' ); ?></p>
                </td>
            </tr>
        </table>

        <?php submit_button( __( 'Save Settings', 'rankout-connector' ) ); ?>
    </form>
</div>
<?php
}
rankout_connector_view_settings( $settings, $all_tool_names, $message, $ip_invalid );
