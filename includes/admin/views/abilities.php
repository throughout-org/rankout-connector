<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function rankout_connector_view_abilities( $has_abilities_api, $enabled_abilities, $message, $search_query, $flat_abilities, $page_abilities, $page_links, $total_items, $current_page ) {
?>
<div class="wrap wp-mcp-admin">
    <h1><?php esc_html_e( 'RankOut Connector - Abilities Browser', 'rankout-connector' ); ?></h1>

    <?php include __DIR__ . '/partials/page-nav.php'; ?>

    <?php if ( 'saved' === $message ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Ability settings saved. Enabled abilities are now available as individual MCP tools.', 'rankout-connector' ); ?></p>
        </div>
    <?php endif; ?>

    <p class="description wp-mcp-mb-16">
        <?php esc_html_e( 'Each enabled ability becomes its own MCP tool, discoverable by AI assistants via tools/list.', 'rankout-connector' ); ?>
    </p>

    <!-- ===== ABILITIES ===== -->
    <div id="wp-mcp-abilities-tab">

        <?php if ( ! $has_abilities_api ) : ?>
            <div class="wp-mcp-card wp-mcp-mt-16">
                <div class="notice notice-warning inline wp-mcp-m-0 wp-mcp-p-12-16">
                    <h3 class="wp-mcp-m-0-0-8"><?php esc_html_e( 'WordPress 6.9+ Required', 'rankout-connector' ); ?></h3>
                    <p class="wp-mcp-m-0">
                        <?php
                        printf(
                            /* translators: %s: current WordPress version */
                            esc_html__( 'The WordPress Abilities API requires WordPress 6.9 or later. Your current version is %s.', 'rankout-connector' ),
                            esc_html( $GLOBALS['wp_version'] )
                        );
                        ?>
                    </p>
                </div>
            </div>
        <?php else : ?>

            <?php if ( empty( $flat_abilities ) && empty( $search_query ) ) : ?>
                <div class="wp-mcp-card wp-mcp-mt-16">
                    <p><?php esc_html_e( 'No WordPress Abilities registered yet.', 'rankout-connector' ); ?></p>
                </div>
            <?php else : ?>

                <div class="wp-mcp-card wp-mcp-mt-16">
                    <div class="notice notice-info inline wp-mcp-m-0 wp-mcp-p-10-14">
                        <p class="wp-mcp-m-0">
                            <?php esc_html_e( 'Select the abilities you want to expose to AI assistants. They will be registered as tools prefixed with', 'rankout-connector' ); ?>
                            <code>wp_ability_</code>.
                        </p>
                    </div>
                </div>

                <div class="wp-mcp-my-16">
                    <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
                        <input type="hidden" name="page" value="rankout-connector-abilities">
                        <p class="search-box">
                            <label class="screen-reader-text" for="wp-mcp-ability-search"><?php esc_html_e( 'Search Abilities:', 'rankout-connector' ); ?></label>
                            <input type="search" id="wp-mcp-ability-search" name="s" class="regular-text wp-mcp-w-320"
                                value="<?php echo esc_attr( $search_query ); ?>"
                                placeholder="<?php esc_attr_e( 'Filter abilities by name…', 'rankout-connector' ); ?>">
                            <?php submit_button( __( 'Search Abilities', 'rankout-connector' ), '', '', false, array( 'id' => 'search-submit' ) ); ?>
                        </p>
                    </form>
                </div>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=rankout-connector-abilities' ) ); ?>">
                    <?php wp_nonce_field( 'rankout_connector_save_abilities' ); ?>
                    <input type="hidden" name="rankout_connector_save_abilities" value="1">
                    <input type="hidden" name="s" value="<?php echo esc_attr( $search_query ); ?>">
                    <input type="hidden" name="paged" value="<?php echo esc_attr( $current_page ); ?>">

                    <?php if ( ! empty($page_links) ) : ?>
                        <div class="tablenav top">
                            <div class="tablenav-pages">
                                <span class="displaying-num"><?php
                                /* translators: %s: number of items */
                                echo esc_html( sprintf( _n( '%s item', '%s items', $total_items, 'rankout-connector' ), number_format_i18n( $total_items ) ) ); ?></span>
                                <span class="pagination-links"><?php echo wp_kses_post( $page_links ); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php
                    
                    
                    foreach ( $page_abilities as $item ) {
                        echo '<input type="hidden" name="abilities_on_page[]" value="' . esc_attr( $item['ability']->get_name() ) . '">';
                    }
                    ?>

                    <?php if ( empty( $page_abilities ) ) : ?>
                        <div class="wp-mcp-card wp-mcp-mt-16">
                            <p><?php esc_html_e( 'No abilities found matching your search.', 'rankout-connector' ); ?></p>
                        </div>
                    <?php else : ?>
                        <div class="wp-mcp-card wp-mcp-plugin-section">
                            <div class="wp-mcp-plugin-body">
                                <table class="widefat striped">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e( 'Prefix', 'rankout-connector' ); ?></th>
                                            <th><?php esc_html_e( 'Slug', 'rankout-connector' ); ?></th>
                                            <th><?php esc_html_e( 'Label', 'rankout-connector' ); ?></th>
                                            <th><?php esc_html_e( 'Description', 'rankout-connector' ); ?></th>
                                            <th><?php esc_html_e( 'Read-Only', 'rankout-connector' ); ?></th>
                                            <th><?php esc_html_e( 'MCP Tool', 'rankout-connector' ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( $page_abilities as $item ) :
                                            $prefix      = $item['prefix'];
                                            $ability     = $item['ability'];
                                            $slug        = $ability->get_name();
                                            $annotations = array();
                                            if ( method_exists( $ability, 'get_meta_item' ) ) {
                                                $annotations = (array) $ability->get_meta_item( 'annotations' );
                                            } elseif ( method_exists( $ability, 'get_annotations' ) ) {
                                                $annotations = (array) $ability->get_annotations();
                                            }
                                            $readonly    = isset( $annotations['readonly'] ) && $annotations['readonly'];
                                            $tool_name   = 'wp_ability_' . \RankOut_Connector\Tools\Dynamic_Tool_Registrar::normalize_identifier( $slug );
                                        ?>
                                            <tr>
                                                <td><strong><?php echo esc_html( ucfirst( $prefix ) ); ?></strong></td>
                                                <td><code><?php echo esc_html( $slug ); ?></code></td>
                                                <td><?php echo esc_html( $ability->get_label() ?: $slug ); ?></td>
                                                <td class="wp-mcp-mw-280"><?php echo esc_html( $ability->get_description() ); ?></td>
                                                <td>
                                                    <?php if ( $readonly ) : ?>
                                                        <span class="wp-mcp-badge wp-mcp-badge-ok"><?php esc_html_e( 'Yes', 'rankout-connector' ); ?></span>
                                                    <?php else : ?>
                                                        <span class="wp-mcp-badge wp-mcp-badge-inactive"><?php esc_html_e( 'No', 'rankout-connector' ); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <label>
                                                        <input type="checkbox"
                                                            name="enabled_abilities[]"
                                                            value="<?php echo esc_attr( $slug ); ?>"
                                                            <?php checked( in_array( $slug, $enabled_abilities, true ) ); ?>>
                                                        <code><?php echo esc_html( $tool_name ); ?></code>
                                                    </label>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty($page_links) ) : ?>
                        <div class="tablenav bottom">
                            <div class="tablenav-pages">
                                <span class="displaying-num"><?php
                                /* translators: %s: number of items */
                                echo esc_html( sprintf( _n( '%s item', '%s items', $total_items, 'rankout-connector' ), number_format_i18n( $total_items ) ) ); ?></span>
                                <span class="pagination-links"><?php echo wp_kses_post( $page_links ); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php submit_button( __( 'Save Ability Settings', 'rankout-connector' ) ); ?>
                </form>

            <?php endif; ?>
        <?php endif; ?>
    </div><!-- /

</div><!-- /.wrap -->
<?php
}
rankout_connector_view_abilities( $has_abilities_api, $enabled_abilities, $message, $search_query, $flat_abilities, $page_abilities, $page_links, $total_items, $current_page );
