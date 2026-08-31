<?php
namespace RankOut_Connector\Tools\WooCommerce;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Get_Customer extends Base_Tool {

    public function get_name() {
        return 'wp_wc_get_customer';
    }

    public function get_description() {
        return 'Gets a single WooCommerce customer by ID. Required: `id` (WooCommerce customer ID — same as WordPress user ID; get it from `wp_wc_list_customers`). Returns the full customer object including: id, email, first_name, last_name, username, role, avatar_url, billing (address object), shipping (address object), orders_count, total_spent, date_created. Requires WooCommerce active.';
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
                'id' => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the customer to retrieve.',
                ),
            ),
            'required'   => array( 'id' ),
        );
    }

    public function execute( array $arguments ) {
        if ( ! class_exists( 'WooCommerce' ) ) {
            throw new \RuntimeException( 'WooCommerce is not active.' );
        }

        $this->validate_required( $arguments, array( 'id' ) );
        $id   = $this->parse_required_id( $arguments['id'], 'id' );
        $data = $this->rest_request( 'GET', '/wc/v3/customers/' . $id );

        return $data;
    }
}
