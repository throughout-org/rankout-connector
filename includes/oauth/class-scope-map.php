<?php
namespace RankOut_Connector\OAuth;

use RankOut_Connector\Tools\Dynamic_Tool_Registrar;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Scope_Map {

    const SCOPE_MAP = array(
        'mcp:posts:read'         => array( 'wp_get_post', 'wp_list_posts', 'wp_get_page', 'wp_list_pages', 'wp_list_revisions', 'wp_get_revision', 'wp_get_post_meta', 'wp_get_post_types', 'wp_count_posts', 'wp_get_post_full' ),
        'mcp:posts:write'        => array( 'wp_create_post', 'wp_update_post', 'wp_delete_post', 'wp_create_page', 'wp_update_page', 'wp_delete_page', 'wp_delete_revision', 'wp_restore_revision', 'wp_update_post_meta', 'wp_delete_post_meta', 'wp_get_post_statuses', 'wp_add_post_terms', 'wp_replace_in_post' ),
        'mcp:media:read'         => array( 'wp_get_media', 'wp_list_media', 'wp_count_media' ),
        'mcp:media:write'        => array( 'wp_upload_media', 'wp_update_media', 'wp_delete_media', 'wp_upload_media_from_url' ),
        'mcp:taxonomies:read'    => array( 'wp_get_category', 'wp_list_categories', 'wp_get_tag', 'wp_list_tags', 'wp_get_taxonomies', 'wp_count_terms', 'wp_get_term' ),
        'mcp:taxonomies:write'   => array( 'wp_create_category', 'wp_update_category', 'wp_delete_category', 'wp_create_tag', 'wp_update_tag', 'wp_delete_tag', 'wp_create_term', 'wp_update_term', 'wp_delete_term' ),
        'mcp:term_meta:read'     => array( 'wp_get_term_meta' ),
        'mcp:term_meta:write'    => array( 'wp_update_term_meta', 'wp_delete_term_meta' ),
        'mcp:user_meta:read'     => array( 'wp_get_user_meta' ),
        'mcp:user_meta:write'    => array( 'wp_update_user_meta', 'wp_delete_user_meta' ),
        'mcp:comments:read'      => array( 'wp_get_comment', 'wp_list_comments' ),
        'mcp:comments:write'     => array( 'wp_create_comment', 'wp_update_comment', 'wp_delete_comment' ),
        'mcp:users:read'         => array( 'wp_get_user', 'wp_list_users' ),
        'mcp:user_create:write'  => array( 'wp_create_user' ),
        'mcp:user_update:write'  => array( 'wp_update_user' ),
        'mcp:user_delete:write'  => array( 'wp_delete_user' ),
        'mcp:menus:read'         => array( 'wp_get_menu', 'wp_list_menus', 'wp_list_menu_items' ),
        'mcp:menus:write'        => array( 'wp_create_menu', 'wp_update_menu', 'wp_delete_menu', 'wp_create_menu_item', 'wp_update_menu_item', 'wp_delete_menu_item' ),
        'mcp:blocks:read'        => array( 'wp_get_block', 'wp_list_blocks', 'wp_get_template', 'wp_list_templates', 'wp_get_global_styles', 'wp_get_active_theme', 'wp_list_themes' ),
        'mcp:blocks:write'       => array( 'wp_create_block', 'wp_update_block', 'wp_delete_block' ),
        
        
        
        
        'mcp:appearance:write'   => array( 'wp_update_template', 'wp_update_global_styles' ),
        'mcp:plugins:read'       => array( 'wp_list_plugins' ),
        'mcp:settings:read'      => array( 'wp_get_site_settings' ),
        'mcp:settings:write'     => array( 'wp_update_site_settings' ),
        'mcp:woocommerce:read'   => array( 'wp_wc_get_product', 'wp_wc_list_products', 'wp_wc_get_order', 'wp_wc_list_orders', 'wp_wc_get_customer', 'wp_wc_list_customers', 'wp_wc_list_coupons', 'wp_wc_list_order_notes', 'wp_wc_list_order_refunds', 'wp_wc_list_payment_gateways', 'wp_wc_list_shipping_methods', 'wp_wc_list_shipping_zones', 'wp_wc_list_tax_rates', 'wp_wc_list_webhooks', 'wp_wc_list_product_categories', 'wp_wc_list_product_variations', 'wp_wc_get_product_variation', 'wp_wc_list_product_attributes', 'wp_wc_report_sales', 'wp_wc_report_orders', 'wp_wc_report_products', 'wp_wc_report_customers', 'wp_wc_report_top_sellers' ),
        'mcp:woocommerce:write'  => array( 'wp_wc_create_product', 'wp_wc_update_product', 'wp_wc_delete_product', 'wp_wc_create_order', 'wp_wc_update_order', 'wp_wc_create_order_note', 'wp_wc_create_customer', 'wp_wc_update_customer', 'wp_wc_delete_customer', 'wp_wc_create_coupon', 'wp_wc_update_coupon', 'wp_wc_delete_coupon', 'wp_wc_create_product_variation', 'wp_wc_update_product_variation', 'wp_wc_delete_product_variation', 'wp_wc_batch_update_products', 'wp_wc_batch_update_orders', 'wp_wc_batch_update_variations', 'wp_wc_create_product_attribute', 'wp_wc_set_product_attributes' ),
        'mcp:wc_webhooks:write'  => array( 'wp_wc_create_webhook', 'wp_wc_update_webhook', 'wp_wc_delete_webhook' ),
        'mcp:acf:read'           => array( 'wp_acf_get_fields', 'wp_acf_get_term_fields', 'wp_acf_get_user_fields', 'wp_acf_list_field_groups' ),
        'mcp:acf:write'          => array( 'wp_acf_update_fields', 'wp_acf_update_user_fields' ),
        'mcp:buddypress:read'    => array( 'wp_bp_list_activity', 'wp_bp_list_groups', 'wp_bp_get_group', 'wp_bp_list_group_members', 'wp_bp_list_members', 'wp_bp_get_member', 'wp_bp_list_message_threads', 'wp_bp_get_message_thread' ),
        'mcp:buddypress:write'   => array( 'wp_bp_create_activity', 'wp_bp_delete_activity' ),
        'mcp:events:read'        => array( 'wp_tec_list_events', 'wp_tec_get_event', 'wp_tec_list_venues', 'wp_tec_get_venue', 'wp_tec_list_organizers' ),
        'mcp:events:write'       => array( 'wp_tec_create_event', 'wp_tec_update_event', 'wp_tec_delete_event', 'wp_tec_create_venue', 'wp_tec_create_organizer' ),
        'mcp:yoast:read'         => array( 'wp_yoast_get_post_seo', 'wp_yoast_get_head' ),
        'mcp:yoast:write'        => array( 'wp_yoast_update_post_seo' ),
        'mcp:aioseo:read'        => array( 'wp_aioseo_get_post_seo' ),
        'mcp:aioseo:write'       => array( 'wp_aioseo_update_post_seo' ),
        'mcp:rankmath:read'      => array( 'wp_rm_get_post_seo', 'wp_rm_get_head' ),
        'mcp:rankmath:write'     => array( 'wp_rm_update_post_seo' ),
        'mcp:gsc:read'           => array( 'wp_gsc_list_sites', 'wp_gsc_get_site', 'wp_gsc_query_performance', 'wp_gsc_list_sitemaps', 'wp_gsc_get_sitemap', 'wp_gsc_inspect_url' ),
        'mcp:ga:read'            => array( 'wp_ga_list_account_summaries', 'wp_ga_get_property', 'wp_ga_list_data_streams', 'wp_ga_list_custom_dimensions', 'wp_ga_list_custom_metrics', 'wp_ga_list_conversion_events', 'wp_ga_get_metadata', 'wp_ga_run_report', 'wp_ga_run_pivot_report', 'wp_ga_run_realtime_report', 'wp_ga_check_compatibility' ),
        'mcp:dfs:read'           => array(
            'wp_dfs_serp_google_organic_live',
            'wp_dfs_keywords_search_volume_live',
            'wp_dfs_labs_keywords_for_site_live',
            'wp_dfs_labs_ranked_keywords_live',
            'wp_dfs_backlinks_summary_live',
            'wp_dfs_backlinks_referring_domains_live',
            'wp_dfs_on_page_instant_pages',
            'wp_dfs_account_balance',
        ),
        'mcp:history:read'       => array( 'wp_history_list', 'wp_history_get', 'wp_history_diff' ),
        'mcp:semrush:read'       => array(
            'wp_semrush_domain_overview',
            'wp_semrush_domain_organic_keywords',
            'wp_semrush_competitors_organic',
            'wp_semrush_keyword_overview',
            'wp_semrush_related_keywords',
            'wp_semrush_keyword_difficulty',
            'wp_semrush_phrase_questions',
            'wp_semrush_backlinks_overview',
            'wp_semrush_backlinks',
            'wp_semrush_referring_domains',
            'wp_semrush_anchors',
            'wp_semrush_url_organic_keywords',
            'wp_semrush_api_units_balance',
        ),
    );

    


    const CROSS_CATEGORY_READ_TOOLS = array( 'wp_search', 'wp_search_posts' );

    





    const LEGACY_SCOPE_UPGRADES = array(
        'mcp:users:write' => array( 'mcp:user_create:write', 'mcp:user_update:write', 'mcp:user_delete:write' ),
    );

    


    const CORE_CATEGORIES = array( 'posts', 'media', 'taxonomies', 'term_meta', 'comments', 'users', 'user_meta', 'menus', 'blocks' );

    


    const PLUGIN_CATEGORIES = array(
        'woocommerce' => 'WooCommerce',
        'wc_webhooks' => 'WooCommerce',
        'acf'         => 'Advanced Custom Fields',
        'buddypress'  => 'BuddyPress',
        'events'      => 'The Events Calendar',
        'yoast'       => 'Yoast SEO',
        'aioseo'      => 'All in One SEO',
        'rankmath'    => 'Rank Math SEO',
        'gsc'         => 'Google Search Console',
        'ga'          => 'Google Analytics',
        'dfs'         => 'DataforSEO',
        'semrush'     => 'Semrush',
    );

    





    const PLUGIN_DETECTION_CLASSES = array(
        'woocommerce' => array( 'WooCommerce' ),
        'wc_webhooks' => array( 'WooCommerce' ),
        'acf'         => array( 'ACF' ),
        'buddypress'  => array( 'BuddyPress' ),
        'events'      => array( 'Tribe__Events__Main' ),
        'yoast'       => array( 'WPSEO_Options' ),
        'aioseo'      => array( 'AIOSEO\\Plugin\\AIOSEO' ),
        'rankmath'    => array( 'RankMath' ),
        'gsc'         => array(),
        'ga'          => array(),
        'dfs'         => array(),
        'semrush'     => array(),
    );

    







    public static function is_plugin_category_active( string $slug ): bool {
        if ( 'gsc' === $slug ) {
            return ! empty( \get_option( 'rankout_connector_gsc_service_account_json', '' ) );
        }
        if ( 'ga' === $slug ) {
            return ! empty( \get_option( 'rankout_connector_ga_service_account_json', '' ) );
        }
        if ( 'dfs' === $slug ) {
            return ! empty( \get_option( 'rankout_connector_dfs_login', '' ) )
                && ! empty( \get_option( 'rankout_connector_dfs_api_password', '' ) );
        }
        if ( 'semrush' === $slug ) {
            return ! empty( \get_option( 'rankout_connector_semrush_api_key', '' ) );
        }
        if ( ! isset( self::PLUGIN_DETECTION_CLASSES[ $slug ] ) ) {
            return false;
        }
        foreach ( self::PLUGIN_DETECTION_CLASSES[ $slug ] as $class ) {
            if ( class_exists( $class ) ) {
                return true;
            }
        }
        return false;
    }

    




    public static function get_map(): array {
        return self::SCOPE_MAP;
    }

    








    public static function apply_legacy_scope_upgrades( array $scopes ): array {
        $result = array();
        foreach ( $scopes as $scope ) {
            if ( isset( self::LEGACY_SCOPE_UPGRADES[ $scope ] ) ) {
                foreach ( self::LEGACY_SCOPE_UPGRADES[ $scope ] as $replacement ) {
                    if ( ! in_array( $replacement, $result, true ) ) {
                        $result[] = $replacement;
                    }
                }
            } elseif ( ! in_array( $scope, $result, true ) ) {
                $result[] = $scope;
            }
        }
        return $result;
    }

    




    public static function get_all_scopes(): array {
        $scopes     = array( 'mcp' );
        $categories = array();

        foreach ( array_keys( self::SCOPE_MAP ) as $leaf ) {
            $scopes[] = $leaf;
            
            $parts    = explode( ':', $leaf );
            $category = $parts[0] . ':' . $parts[1];
            $categories[ $category ] = true;
        }

        foreach ( array_keys( $categories ) as $cat ) {
            $scopes[] = $cat;
        }

        $scopes = array_merge( $scopes, self::get_dynamic_ability_scopes() );

        return array_values( array_unique( $scopes ) );
    }

    










    public static function get_public_scopes(): array {
        $scopes     = array( 'mcp' );
        $categories = array();

        foreach ( array_keys( self::SCOPE_MAP ) as $leaf ) {
            $scopes[] = $leaf;
            $parts    = explode( ':', $leaf );
            $category = $parts[0] . ':' . $parts[1];
            $categories[ $category ] = true;
        }

        foreach ( array_keys( $categories ) as $cat ) {
            $scopes[] = $cat;
        }

        if ( ! empty( self::get_cached_abilities() ) ) {
            $scopes[] = 'mcp:abilities';
        }

        return array_values( array_unique( $scopes ) );
    }

    









    private static function get_cached_abilities(): array {
        return function_exists( 'wp_get_abilities' ) ? (array) \wp_get_abilities() : array();
    }

    








    public static function get_dynamic_ability_scopes(): array {
        $abilities = self::get_cached_abilities();
        if ( empty( $abilities ) ) {
            return array();
        }

        if ( ! class_exists( '\\RankOut_Connector\\Tools\\Dynamic_Tool_Registrar' ) ) {
            require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/tools/class-dynamic-tool-registrar.php';
        }

        $prefixes = array();
        foreach ( $abilities as $ability ) {
            $name = is_object( $ability ) && method_exists( $ability, 'get_name' )
                ? $ability->get_name()
                : (string) ( is_string( $ability ) ? $ability : '' );
            if ( '' === $name ) {
                continue;
            }
            $parts  = explode( '/', $name, 2 );
            $prefix = count( $parts ) > 1
                ? Dynamic_Tool_Registrar::normalize_identifier( $parts[0] )
                : 'core';
            if ( '' === $prefix ) {
                continue;
            }
            $prefixes[ $prefix ] = true;
        }

        $scopes = array( 'mcp:abilities' );
        foreach ( array_keys( $prefixes ) as $prefix ) {
            $scopes[] = "mcp:abilities:{$prefix}";
        }
        return $scopes;
    }

    








    private static function resolve_core_ability_tools(): array {
        $tools = array();
        foreach ( self::get_cached_abilities() as $ability ) {
            $name = is_object( $ability ) && method_exists( $ability, 'get_name' )
                ? $ability->get_name()
                : (string) ( is_string( $ability ) ? $ability : '' );
            if ( '' === $name ) {
                continue;
            }
            
            
            
            
            
            
            
            $parts  = explode( '/', $name, 2 );
            $prefix = count( $parts ) > 1 ? $parts[0] : 'core';
            if ( 'core' === $prefix ) {
                $tools[] = 'wp_ability_' . Dynamic_Tool_Registrar::normalize_identifier( $name );
            }
        }
        return $tools;
    }

    








    public static function resolve_allowed_tools( string $scope ): array {
        $scope_parts    = array_filter( array_map( 'trim', explode( ' ', $scope ) ) );
        $scope_parts    = self::apply_legacy_scope_upgrades( array_values( $scope_parts ) );
        $tools          = array();
        $has_read_scope = false;

        foreach ( $scope_parts as $part ) {
            if ( 'mcp' === $part ) {
                return array( '*' );
            }

            
            if ( 'mcp:abilities' === $part ) {
                $tools[] = 'wp_ability_*';
                continue;
            }

            
            if ( 0 === strpos( $part, 'mcp:abilities:' ) ) {
                $prefix = substr( $part, strlen( 'mcp:abilities:' ) );
                if ( 'core' === $prefix ) {
                    
                    
                    
                    
                    
                    
                    $tools = array_merge( $tools, self::resolve_core_ability_tools() );
                } elseif ( '' !== $prefix ) {
                    $tools[] = "wp_ability_{$prefix}_*";
                }
                continue;
            }

            
            if ( isset( self::SCOPE_MAP[ $part ] ) ) {
                $tools = array_merge( $tools, self::SCOPE_MAP[ $part ] );
                if ( substr( $part, -5 ) === ':read' ) {
                    $has_read_scope = true;
                }
                continue;
            }

            
            foreach ( self::SCOPE_MAP as $leaf_scope => $leaf_tools ) {
                if ( 0 === strpos( $leaf_scope, $part . ':' ) ) {
                    $tools = array_merge( $tools, $leaf_tools );
                    if ( substr( $leaf_scope, -5 ) === ':read' ) {
                        $has_read_scope = true;
                    }
                }
            }
            
        }

        if ( $has_read_scope ) {
            $tools = array_merge( $tools, self::CROSS_CATEGORY_READ_TOOLS );
        }

        return array_values( array_unique( $tools ) );
    }

    







    public static function get_default_scope(): string {
        $scopes = array();

        
        foreach ( self::CORE_CATEGORIES as $cat ) {
            if ( 'users' === $cat ) {
                $scopes[] = 'mcp:users:read';
                continue;
            }
            $scopes[] = "mcp:{$cat}:read";
            $scopes[] = "mcp:{$cat}:write";
        }

        
        foreach ( array_keys( self::PLUGIN_CATEGORIES ) as $cat ) {
            if ( ! isset( self::SCOPE_MAP[ "mcp:{$cat}:read" ] ) ) {
                continue;
            }
            $scopes[] = "mcp:{$cat}:read";
        }

        return implode( ' ', $scopes );
    }

    






    public static function scope_permits_tool( string $scope, string $tool_name ): bool {
        $allowed = self::resolve_allowed_tools( $scope );

        if ( in_array( '*', $allowed, true ) ) {
            return true;
        }

        if ( in_array( $tool_name, $allowed, true ) ) {
            return true;
        }

        
        foreach ( $allowed as $entry ) {
            if ( is_string( $entry ) && false !== strpos( $entry, '*' ) && fnmatch( $entry, $tool_name ) ) {
                return true;
            }
        }

        return false;
    }

    




    public static function get_categories(): array {
        $categories = array();
        $seen       = array();

        foreach ( array_keys( self::SCOPE_MAP ) as $leaf ) {
            $parts = explode( ':', $leaf );
            $slug  = $parts[1];

            if ( isset( $seen[ $slug ] ) ) {
                continue;
            }
            $seen[ $slug ] = true;

            $labels = array(
                'posts'       => __( 'Posts & Pages', 'rankout-connector' ),
                'media'       => __( 'Media', 'rankout-connector' ),
                'taxonomies'  => __( 'Taxonomies', 'rankout-connector' ),
                'comments'    => __( 'Comments', 'rankout-connector' ),
                'users'       => __( 'Users', 'rankout-connector' ),
                'menus'       => __( 'Menus', 'rankout-connector' ),
                'blocks'      => __( 'Blocks & Themes', 'rankout-connector' ),
                'plugins'     => __( 'Plugins', 'rankout-connector' ),
                'settings'    => __( 'Settings', 'rankout-connector' ),
                'woocommerce' => 'WooCommerce',
                'acf'         => __( 'Advanced Custom Fields', 'rankout-connector' ),
                'buddypress'  => 'BuddyPress',
                'events'      => __( 'The Events Calendar', 'rankout-connector' ),
                'yoast'       => __( 'Yoast SEO', 'rankout-connector' ),
                'aioseo'      => __( 'All in One SEO', 'rankout-connector' ),
                'rankmath'    => __( 'Rank Math SEO', 'rankout-connector' ),
                'gsc'         => __( 'Google Search Console', 'rankout-connector' ),
                'ga'          => __( 'Google Analytics', 'rankout-connector' ),
                'dfs'         => __( 'DataforSEO', 'rankout-connector' ),
                'semrush'     => __( 'Semrush', 'rankout-connector' ),
                'term_meta'   => __( 'Term Meta', 'rankout-connector' ),
                'user_meta'   => __( 'User Meta', 'rankout-connector' ),
                'appearance'  => __( 'Site Appearance (templates & global styles)', 'rankout-connector' ),
                'wc_webhooks' => 'WooCommerce Webhooks',
                'user_create' => __( 'Users — Create', 'rankout-connector' ),
                'user_update' => __( 'Users — Update', 'rankout-connector' ),
                'user_delete' => __( 'Users — Delete', 'rankout-connector' ),
                'history'     => __( 'Change History (audit log of MCP edits)', 'rankout-connector' ),
            );

            $is_core        = in_array( $slug, self::CORE_CATEGORIES, true );
            $is_plugin      = isset( self::PLUGIN_CATEGORIES[ $slug ] );
            $plugin_required = $is_plugin ? self::PLUGIN_CATEGORIES[ $slug ] : null;

            
            if ( 'settings' === $slug || 'plugins' === $slug || 'appearance' === $slug || 'wc_webhooks' === $slug || 'user_create' === $slug || 'user_update' === $slug || 'user_delete' === $slug || 'history' === $slug ) {
                $default_read  = false;
                $default_write = false;
            } elseif ( 'users' === $slug ) {
                $default_read  = true;
                $default_write = false;
            } elseif ( $is_core ) {
                $default_read  = true;
                $default_write = true;
            } else {
                
                $default_read  = true;
                $default_write = false;
            }

            $read_scope  = isset( self::SCOPE_MAP[ "mcp:{$slug}:read" ] ) ? "mcp:{$slug}:read" : '';
            $write_scope = isset( self::SCOPE_MAP[ "mcp:{$slug}:write" ] ) ? "mcp:{$slug}:write" : '';

            $categories[] = array(
                'slug'            => $slug,
                'label'           => isset( $labels[ $slug ] ) ? $labels[ $slug ] : ucfirst( $slug ),
                'read_scope'      => $read_scope,
                'write_scope'     => $write_scope,
                'write_only'      => '' === $read_scope && '' !== $write_scope,
                'default_read'    => $default_read,
                'default_write'   => $default_write,
                'plugin_required' => $plugin_required,
                'is_ability'      => false,
            );
        }

        
        $abilities = self::get_cached_abilities();
        if ( ! empty( $abilities ) ) {
            $seen_prefixes = array();
            foreach ( (array) $abilities as $ability ) {
                $name = is_object( $ability ) && method_exists( $ability, 'get_name' )
                    ? $ability->get_name()
                    : (string) ( is_string( $ability ) ? $ability : '' );
                if ( '' === $name ) {
                    continue;
                }
                $parts = explode( '/', $name, 2 );
                if ( count( $parts ) > 1 ) {
                    $norm_prefix   = Dynamic_Tool_Registrar::normalize_identifier( $parts[0] );
                    $display_label = ucfirst( $parts[0] );
                } else {
                    $norm_prefix   = 'core';
                    $display_label = __( 'Core', 'rankout-connector' );
                }
                if ( '' === $norm_prefix || isset( $seen_prefixes[ $norm_prefix ] ) ) {
                    continue;
                }
                $seen_prefixes[ $norm_prefix ] = $display_label;
            }

            foreach ( $seen_prefixes as $norm_prefix => $display_label ) {
                $categories[] = array(
                    'slug'            => "abilities_{$norm_prefix}",
                    /* translators: %s: ability prefix (e.g. "Core", "Wpforms") */
                    'label'           => sprintf( __( 'Abilities — %s', 'rankout-connector' ), $display_label ),
                    'read_scope'      => "mcp:abilities:{$norm_prefix}",
                    'write_scope'     => '',     
                    'default_read'    => false,  
                    'default_write'   => false,
                    'plugin_required' => null,   
                    'is_ability'      => true,
                );
            }
        }

        return $categories;
    }
}
