<?php
namespace RankOut_Connector\Tools\GA;

use RankOut_Connector\Tools\Base_Tool;
use RankOut_Connector\GA\GA_Client;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Get_Property extends Base_Tool {

    public function get_name() { return 'wp_ga_get_property'; }

    public function get_description() {
        return 'Gets full details for a Google Analytics 4 property — display name, timezone, currency code, industry category, service level, create/update time, and parent account.';
    }

    public function get_category() { return 'ga'; }

    public function get_required_capability() { return 'manage_options'; }

    public function get_annotations() {
        return array(
            'title'           => 'Get Analytics property',
            'readOnlyHint'    => true,
            'destructiveHint' => false,
            'openWorldHint'   => true,
        );
    }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'property_id' => array( 'type' => 'string', 'description' => 'Numeric GA4 property ID, or "properties/{id}". Falls back to the configured default property when omitted.' ),
            ),
        );
    }

    public function execute( array $arguments ) {
        try {
            $property = ! empty( $arguments['property_id'] )
                ? GA_Client::normalize_property( (string) $arguments['property_id'] )
                : GA_Client::default_property_id();
            $data = GA_Client::get( "https://analyticsadmin.googleapis.com/v1beta/{$property}" );
        } catch ( \Exception $e ) {
            throw $e;
        }
        return $data;
    }
}
