<?php
/**
 * Plugin Name: RankOut Connector
 * Plugin URI:  https://rankout.app
 * Description: Connects your WordPress site to your RankOut account — RankOut's dashboard and AI agent can read your site's content, SEO, WooCommerce, and technical health, and (with your explicit approval on each change) apply optimizations directly.
 * Version:     2.0.1
 * Author:      RankOut
 * Author URI:  https://rankout.app
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rankout-connector
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Update URI: false
 */

// Update URI: false (above) tells WordPress core's own update-checker to
// never look for updates for this plugin from wordpress.org or any other
// source — this plugin ships its own update channel instead (see
// class-github-updater.php, pointed at RankOut's own GitHub releases).
// Without this header, WordPress core would still try to match this
// plugin against anything sharing its folder/slug on wordpress.org and
// silently offer that as an "update," which is never what should happen
// for an org-owned, privately-distributed plugin. This header is the
// officially documented way to opt out of that entirely (WP 5.8+, see
// https://make.wordpress.org/core/2021/06/29/introducing-update-uri-plugin-header-in-wordpress-5-8/).

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'RANKOUT_CONNECTOR_VERSION' ) ) {
    define( 'RANKOUT_CONNECTOR_VERSION', '2.0.1' );
}
if ( ! defined( 'RANKOUT_CONNECTOR_PLUGIN_FILE' ) ) {
    define( 'RANKOUT_CONNECTOR_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'RANKOUT_CONNECTOR_PLUGIN_DIR' ) ) {
    define( 'RANKOUT_CONNECTOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'RANKOUT_CONNECTOR_PLUGIN_URL' ) ) {
    define( 'RANKOUT_CONNECTOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'RANKOUT_CONNECTOR_PLUGIN_BASENAME' ) ) {
    define( 'RANKOUT_CONNECTOR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

require_once RANKOUT_CONNECTOR_PLUGIN_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'RankOut_Connector\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'RankOut_Connector\\Deactivator', 'deactivate' ) );

RankOut_Connector\Plugin::instance();

// Some hosts and security plugins (Wordfence, Solid Security, and several
// managed-WordPress hosts) disable Application Passwords by default as a
// hardening measure. RankOut's dashboard depends on one being generated
// for Site Health's WordPress REST checks, so this plugin — whose whole
// purpose is exposing site data to that dashboard — re-enables the
// feature unconditionally wherever it's installed, rather than requiring
// a separate manual fix on every client site.
add_filter( 'wp_is_application_passwords_available', '__return_true' );

add_filter(
    'plugin_action_links_' . RANKOUT_CONNECTOR_PLUGIN_BASENAME,
    function ( $links ) {
        $prepend = array(
            'dashboard' => '<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector' ) ) . '">' . esc_html__( 'Getting Started', 'rankout-connector' ) . '</a>',
            'plugins'   => '<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector-plugin-integrations' ) ) . '">' . esc_html__( 'Plugin', 'rankout-connector' ) . '</a>',
            'abilities'      => '<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector-abilities' ) ) . '">' . esc_html__( 'Abilities', 'rankout-connector' ) . '</a>',
            'external_data'  => '<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector-external-data' ) ) . '">' . esc_html__( 'External Data', 'rankout-connector' ) . '</a>',
        );
        $append = array(
            'settings' => '<a href="' . esc_url( admin_url( 'admin.php?page=rankout-connector-settings' ) ) . '">' . esc_html__( 'Settings', 'rankout-connector' ) . '</a>',
        );
        return array_merge( $prepend, $links, $append );
    }
);

add_filter(
    'plugin_row_meta',
    function ( $links, $file ) {
        if ( RANKOUT_CONNECTOR_PLUGIN_BASENAME !== $file ) {
            return $links;
        }
        $links[] = '<a href="https://wordpress.org/support/plugin/rankout-connector/reviews/" target="_blank" rel="noopener">' . esc_html__( 'Rate Plugin', 'rankout-connector' ) . '</a>';
        return $links;
    },
    10,
    2
);
