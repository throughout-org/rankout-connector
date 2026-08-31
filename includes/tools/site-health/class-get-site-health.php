<?php
namespace RankOut_Connector\Tools\Site_Health;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Everything here reads data WordPress core keeps to itself outside the
// admin area (update-availability flags, PHP version, cron health) — none
// of it is reachable through the public REST API, which is why this tool
// exists as plugin code rather than another REST-backed tool like
// List_Plugins. Read-only; no site-health remediation actions here.
class Get_Site_Health extends Base_Tool {

    public function get_name() {
        return 'wp_get_site_health';
    }

    public function get_description() {
        return 'Full technical/SEO site-health snapshot: WordPress core version and update status, PHP version, active theme and update status, every plugin with its update status, permalink structure, HTTPS status, cron health, XML sitemap and robots.txt reachability, detected SEO plugin, and whether the homepage emits JSON-LD structured data. Requires `manage_options` capability (administrators only).';
    }

    public function get_category() {
        return 'site-health';
    }

    public function get_required_capability() {
        return 'manage_options';
    }

    public function get_annotations() {
        return array(
            'title'           => $this->get_title(),
            'readOnlyHint'    => true,
            'destructiveHint' => false,
            'openWorldHint'   => false,
        );
    }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => new \stdClass(),
        );
    }

    public function execute( array $arguments ) {
        $this->ensure_update_functions_loaded();

        return array(
            'core'          => $this->get_core_info(),
            'php_version'   => phpversion(),
            'theme'         => $this->get_theme_info(),
            'plugins'       => $this->get_plugins_info(),
            'permalink_structure' => get_option( 'permalink_structure' ) ?: 'plain',
            'https'         => $this->is_https(),
            'cron'          => $this->get_cron_info(),
            'sitemap'       => $this->check_url_reachable( home_url( '/wp-sitemap.xml' ) ),
            'robots_txt'    => $this->get_robots_info(),
            'seo_plugin'    => $this->detect_seo_plugin(),
            'has_json_ld'   => $this->homepage_has_json_ld(),
        );
    }

    private function ensure_update_functions_loaded() {
        if ( ! function_exists( 'get_core_updates' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if ( ! function_exists( 'wp_get_theme' ) ) {
            require_once ABSPATH . 'wp-includes/theme.php';
        }
    }

    private function get_core_info() {
        global $wp_version;

        $update_available = false;
        $latest_version   = $wp_version;

        $core_updates = get_core_updates();
        if ( is_array( $core_updates ) && ! empty( $core_updates ) && isset( $core_updates[0]->response ) ) {
            if ( 'upgrade' === $core_updates[0]->response ) {
                $update_available = true;
                $latest_version   = $core_updates[0]->version;
            }
        }

        return array(
            'version'           => $wp_version,
            'update_available'  => $update_available,
            'latest_version'    => $latest_version,
        );
    }

    private function get_theme_info() {
        $theme          = wp_get_theme();
        $theme_updates  = get_theme_updates();
        $stylesheet     = get_stylesheet();

        return array(
            'name'             => $theme->get( 'Name' ),
            'version'          => $theme->get( 'Version' ),
            'update_available' => isset( $theme_updates[ $stylesheet ] ),
        );
    }

    private function get_plugins_info() {
        $all_plugins    = get_plugins();
        $plugin_updates = get_plugin_updates();
        $result         = array();

        foreach ( $all_plugins as $file => $data ) {
            $result[] = array(
                'file'             => $file,
                'name'             => $data['Name'] ?? '',
                'version'          => $data['Version'] ?? '',
                'active'           => is_plugin_active( $file ),
                'update_available' => isset( $plugin_updates[ $file ] ),
            );
        }

        $outdated = array_values( array_filter( $result, static function ( $p ) {
            return $p['update_available'];
        } ) );

        return array(
            'total'    => count( $result ),
            'active'   => count( array_filter( $result, static function ( $p ) {
                return $p['active'];
            } ) ),
            'outdated' => count( $outdated ),
            'plugins'  => $result,
        );
    }

    private function is_https() {
        if ( function_exists( 'wp_is_using_https' ) ) {
            return wp_is_using_https();
        }
        return is_ssl();
    }

    private function get_cron_info() {
        $disabled = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
        $next_run = wp_next_scheduled( 'wp_version_check' );

        return array(
            'disabled'        => $disabled,
            // A wp_version_check run scheduled far in the past (rather than
            // upcoming) is the classic symptom of cron silently not firing
            // — no visitor traffic to trigger WP's pseudo-cron, or a real
            // server cron job that was never set up.
            'next_run_overdue' => $next_run !== false && $next_run < time() - HOUR_IN_SECONDS,
        );
    }

    // A short-timeout loopback request — same technique WP core's own Site
    // Health screen uses for its "can this site reach itself" checks — so a
    // slow/unreachable site degrades this one field instead of the whole
    // tool call.
    private function check_url_reachable( $url ) {
        $response = wp_remote_get( $url, array( 'timeout' => 5, 'redirection' => 3 ) );
        if ( is_wp_error( $response ) ) {
            return array( 'reachable' => false, 'status' => null );
        }
        return array( 'reachable' => wp_remote_retrieve_response_code( $response ) === 200, 'status' => wp_remote_retrieve_response_code( $response ) );
    }

    private function get_robots_info() {
        $response = wp_remote_get( home_url( '/robots.txt' ), array( 'timeout' => 5, 'redirection' => 3 ) );
        if ( is_wp_error( $response ) ) {
            return array( 'reachable' => false, 'blocks_all_crawlers' => null );
        }

        $body = wp_remote_retrieve_body( $response );
        return array(
            'reachable'           => wp_remote_retrieve_response_code( $response ) === 200,
            // A bare "Disallow: /" (not scoped to a specific User-agent
            // section) blocks every well-behaved crawler from the whole
            // site — the single most common "why isn't this ranking"
            // technical cause worth surfacing on its own.
            'blocks_all_crawlers' => (bool) preg_match( '/^\s*Disallow:\s*\/\s*$/mi', $body ),
        );
    }

    private function detect_seo_plugin() {
        $known = array(
            'wordpress-seo/wp-seo.php'                 => 'Yoast SEO',
            'seo-by-rank-math/rank-math.php'            => 'Rank Math',
            'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO',
            'wp-seopress/seopress.php'                  => 'SEOPress',
        );

        foreach ( $known as $file => $label ) {
            if ( is_plugin_active( $file ) ) {
                return $label;
            }
        }
        return null;
    }

    private function homepage_has_json_ld() {
        $response = wp_remote_get( home_url( '/' ), array( 'timeout' => 5, 'redirection' => 3 ) );
        if ( is_wp_error( $response ) ) {
            return null;
        }
        return strpos( wp_remote_retrieve_body( $response ), 'application/ld+json' ) !== false;
    }
}
