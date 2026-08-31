<?php
namespace RankOut_Connector\Tools\WooCommerce;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Update_Webhook extends Base_Tool {

    public function get_name() {
        return 'wp_wc_update_webhook';
    }

    public function get_description() {
        return 'Updates a WooCommerce webhook (PATCH semantics). Required: `id`. Optional: `name`, `delivery_url` (URL that receives the payload), `topic` (e.g. order.created/product.updated/customer.created), `status` (active/paused/disabled — use "paused" to temporarily stop deliveries without deleting). Returns { id, name, topic, delivery_url, status }. Use `wp_wc_list_webhooks` to find the ID. Requires WooCommerce active.';
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
            'readOnlyHint'    => false,
            'destructiveHint' => false,
            'openWorldHint'   => false,
        );
    }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'id'           => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the webhook to update.',
                ),
                'name'         => array(
                    'type'        => 'string',
                    'description' => 'Webhook name.',
                ),
                'delivery_url' => array(
                    'type'        => 'string',
                    'description' => 'URL where the webhook will deliver its payload.',
                ),
                'topic'        => array(
                    'type'        => 'string',
                    'description' => 'Event topic.',
                ),
                'status'       => array(
                    'type'        => 'string',
                    'description' => 'Webhook status.',
                    'enum'        => array( 'active', 'paused', 'disabled' ),
                ),
                'api_version'  => array(
                    'type'        => 'string',
                    'description' => 'WooCommerce REST API version to use for the webhook payload.',
                    'enum'        => array( 'v3', 'v2', 'v1', 'wp_api_v2', 'wp_api_v1', 'legacy_v3' ),
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
        $id     = $this->parse_required_id( $arguments['id'], 'id' );
        $params = array();

        if ( isset( $arguments['name'] ) ) {
            $params['name'] = sanitize_text_field( $arguments['name'] );
        }
        if ( isset( $arguments['delivery_url'] ) ) {
            $params['delivery_url'] = $this->validate_webhook_url( $arguments['delivery_url'] );
        }
        if ( isset( $arguments['topic'] ) ) {
            $params['topic'] = sanitize_text_field( $arguments['topic'] );
        }
        if ( isset( $arguments['status'] ) ) {
            $params['status'] = sanitize_text_field( $arguments['status'] );
        }
        if ( isset( $arguments['api_version'] ) ) {
            $params['api_version'] = sanitize_text_field( $arguments['api_version'] );
        }

        $data = $this->rest_request( 'PUT', '/wc/v3/webhooks/' . $id, $params );

        return array(
            'id'           => $data['id'],
            'name'         => $data['name'],
            'topic'        => $data['topic'],
            'delivery_url' => $data['delivery_url'],
            'status'       => $data['status'],
        );
    }
}
