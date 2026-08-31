<?php
namespace RankOut_Connector\Tools\GSC;

use RankOut_Connector\Tools\Base_Tool;
use RankOut_Connector\GSC\GSC_Client;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Get_Site extends Base_Tool {

    public function get_name() { return 'wp_gsc_get_site'; }

    public function get_description() {
        return 'Gets details for a specific Google Search Console property. Required: `site_url` (exact property URL as registered in GSC — use "https://example.com/" with trailing slash for URL-prefix properties, or "sc-domain:example.com" for domain properties; get the exact string from `wp_gsc_list_sites`). Returns { siteUrl, permissionLevel (siteOwner/siteFullUser/siteRestrictedUser/siteUnverifiedUser) }.';
    }

    public function get_category() { return 'gsc'; }

    public function get_required_capability() { return 'manage_options'; }

    public function get_annotations() {
        return array(
            'title'           => 'Get Search Console site',
            'readOnlyHint'    => true,
            'destructiveHint' => false,
            'openWorldHint'   => true,
        );
    }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'site_url' => array(
                    'type'        => 'string',
                    'description' => 'The Search Console property URL, e.g. https://example.com/ or sc-domain:example.com',
                ),
            ),
            'required' => array( 'site_url' ),
        );
    }

    public function execute( array $arguments ) {
        try {
            $this->validate_required( $arguments, array( 'site_url' ) );
            $site_url = rawurlencode( GSC_Client::validate_site_url( (string) $arguments['site_url'] ) );
            $data = GSC_Client::get( "https://www.googleapis.com/webmasters/v3/sites/{$site_url}" );
        } catch ( \Exception $e ) {
            throw $e;
        }

        return array(
            'siteUrl'         => $data['siteUrl'] ?? '',
            'permissionLevel' => $data['permissionLevel'] ?? '',
        );
    }
}
