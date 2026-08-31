<?php
namespace RankOut_Connector\Tools\WooCommerce;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Report_Top_Sellers extends Base_Tool {

    public function get_name() {
        return 'wp_wc_report_top_sellers';
    }

    public function get_description() {
        return 'Gets the top-selling WooCommerce products ranked by quantity sold. Optional: `period` (week/month/last_month/year), `date_min` / `date_max` (YYYY-MM-DD for custom range), `per_page` (default 10). Returns array of { product_id, title, quantity } ordered by quantity descending. Requires WooCommerce active.';
    }

    public function get_category() {
        return 'woocommerce';
    }

    public function get_required_capability() {
        return 'manage_woocommerce';
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
            'properties' => array(
                'date_min' => array(
                    'type'        => 'string',
                    'description' => 'Start date YYYY-MM-DD.',
                ),
                'date_max' => array(
                    'type'        => 'string',
                    'description' => 'End date YYYY-MM-DD.',
                ),
                'period'   => array(
                    'type'        => 'string',
                    'description' => 'Reporting period.',
                    'enum'        => array( 'week', 'month', 'last_month', 'year' ),
                ),
                'per_page' => array(
                    'type'        => 'integer',
                    'description' => 'Number of results.',
                    'default'     => 10,
                    'minimum'     => 1,
                    'maximum'     => 100,
                ),
            ),
            'required'   => array(),
        );
    }

    public function execute( array $arguments ) {
        if ( ! class_exists( 'WooCommerce' ) ) {
            throw new \RuntimeException( 'WooCommerce is not active.' );
        }

        $params = array();

        if ( isset( $arguments['date_min'] ) ) {
            $params['date_min'] = sanitize_text_field( $arguments['date_min'] );
        }
        if ( isset( $arguments['date_max'] ) ) {
            $params['date_max'] = sanitize_text_field( $arguments['date_max'] );
        }
        if ( isset( $arguments['period'] ) ) {
            $params['period'] = sanitize_text_field( $arguments['period'] );
        }
        if ( isset( $arguments['per_page'] ) ) {
            $params['per_page'] = min( 100, max( 1, absint( $arguments['per_page'] ) ) );
        }

        $data = $this->rest_request( 'GET', '/wc/v3/reports/top_sellers', $params );

        return $data;
    }
}
