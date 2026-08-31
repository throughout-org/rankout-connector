<?php
namespace RankOut_Connector\Tools\GSC;

use RankOut_Connector\Tools\Base_Tool;
use RankOut_Connector\GSC\GSC_Client;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Inspect_Url extends Base_Tool {

    public function get_name() { return 'wp_gsc_inspect_url'; }

    public function get_description() {
        return 'Inspects a URL in Google Search Console. Returns index status (verdict, coverage state, crawl info, canonical URLs), mobile usability issues, rich results (structured data) issues, and AMP status.';
    }

    public function get_category() { return 'gsc'; }

    public function get_required_capability() { return 'manage_options'; }

    public function get_annotations() {
        return array(
            'title'           => 'Inspect URL indexing',
            'readOnlyHint'    => true,
            'destructiveHint' => false,
            'openWorldHint'   => true,
        );
    }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'site_url'       => array( 'type' => 'string', 'description' => 'The Search Console property URL the inspection_url belongs to.' ),
                'inspection_url' => array( 'type' => 'string', 'description' => 'The fully qualified URL to inspect.' ),
                'language_code'  => array( 'type' => 'string', 'description' => 'Optional BCP-47 language code for issue messages, e.g. en.' ),
            ),
            'required' => array( 'site_url', 'inspection_url' ),
        );
    }

    public function execute( array $arguments ) {
        try {
            $this->validate_required( $arguments, array( 'site_url', 'inspection_url' ) );

            $inspection_url = trim( (string) $arguments['inspection_url'] );
            if ( ! preg_match( '#^https?://\S+$#i', $inspection_url ) ) {
                throw new \InvalidArgumentException( 'inspection_url must be a full http(s) URL.' );
            }

            $body = array(
                'siteUrl'       => GSC_Client::validate_site_url( (string) $arguments['site_url'] ),
                'inspectionUrl' => $inspection_url,
            );
            if ( ! empty( $arguments['language_code'] ) ) {
                $body['languageCode'] = trim( $arguments['language_code'] );
            }

            $data = GSC_Client::post(
                'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect',
                $body
            );
        } catch ( \Exception $e ) {
            throw $e;
        }

        return $data['inspectionResult'] ?? $data;
    }
}
