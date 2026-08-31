<?php
namespace RankOut_Connector\Tools\GA;

use RankOut_Connector\Tools\Base_Tool;
use RankOut_Connector\GA\GA_Client;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class List_Conversion_Events extends Base_Tool {

    public function get_name() { return 'wp_ga_list_conversion_events'; }

    public function get_description() {
        return 'Lists conversion events (also known as "key events" in the GA4 UI) configured on a property. Call this so an AI agent can correctly query conversion-related metrics. Returns { keyEvents: [], count } — note the return key is `keyEvents` (Google renamed "conversion events" to "key events" in the GA4 UI in 2024).';
    }

    public function get_category() { return 'ga'; }

    public function get_required_capability() { return 'manage_options'; }

    public function get_annotations() {
        return array(
            'title'           => 'List Analytics conversion events',
            'readOnlyHint'    => true,
            'destructiveHint' => false,
            'openWorldHint'   => true,
        );
    }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'property_id' => array( 'type' => 'string', 'description' => 'Property id (numeric or properties/{id}). Falls back to default.' ),
                'page_size'   => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 200, 'default' => 200 ),
                'page_token'  => array( 'type' => 'string' ),
            ),
        );
    }

    public function execute( array $arguments ) {
        try {
            $property = ! empty( $arguments['property_id'] )
                ? GA_Client::normalize_property( (string) $arguments['property_id'] )
                : GA_Client::default_property_id();
            $size  = max( 1, min( 200, (int) ( $arguments['page_size'] ?? 200 ) ) );
            $token = isset( $arguments['page_token'] ) ? (string) $arguments['page_token'] : '';

            
            
            
            
            
            $url = "https://analyticsadmin.googleapis.com/v1beta/{$property}/keyEvents?pageSize=" . $size;
            if ( '' !== $token ) {
                $url .= '&pageToken=' . rawurlencode( $token );
            }
            $data = GA_Client::get( $url );
        } catch ( \Exception $e ) {
            throw $e;
        }

        
        
        
        $events = $data['keyEvents'] ?? array();
        $result = array(
            'keyEvents'     => $events,
            'nextPageToken' => $data['nextPageToken'] ?? null,
            'count'         => count( $events ),
        );
        return $result;
    }
}
