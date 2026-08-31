<?php
namespace RankOut_Connector\Tools\WooCommerce;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Batch_Update_Orders extends Base_Tool {

    public function get_name() {
        return 'wp_wc_batch_update_orders';
    }

    public function get_description() {
        return 'Create, update, and/or delete up to 100 WooCommerce orders in a single REST batch call. Provide any combination of `create`, `update` (each item needs `id`), `delete` (integer IDs). Default soft cap is 25 items per branch (raise via `rankout_connector_wc_batch_soft_cap` filter). WooCommerce caps total at 100 items per branch via `woocommerce_rest_batch_items_limit` filter. Pass-through WC response.';
    }

    public function get_category() {
        return 'woocommerce';
    }

    public function get_required_capability() {
        return 'edit_shop_orders';
    }

    public function get_annotations() {
        return array(
            'title'           => $this->get_title(),
            'readOnlyHint'    => false,
            'destructiveHint' => true,
            'openWorldHint'   => false,
        );
    }

    public function get_input_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'create' => array(
                    'type'        => 'array',
                    'description' => 'Array of order objects to create.',
                    'items'       => array(
                        'type'                 => 'object',
                        'additionalProperties' => true,
                    ),
                ),
                'update' => array(
                    'type'        => 'array',
                    'description' => 'Array of order objects to update, each with `id`.',
                    'items'       => array(
                        'type'                 => 'object',
                        'additionalProperties' => true,
                    ),
                ),
                'delete' => array(
                    'type'        => 'array',
                    'description' => 'Array of order IDs to delete.',
                    'items'       => array( 'type' => 'integer' ),
                ),
            ),
        );
    }

    public function execute( array $arguments ) {
        if ( ! class_exists( 'WooCommerce' ) ) {
            throw new \RuntimeException( 'WooCommerce is not active.' );
        }

        $soft_cap = (int) apply_filters( 'rankout_connector_wc_batch_soft_cap', 25 );

        $body = array();
        if ( ! empty( $arguments['create'] ) ) {
            if ( count( $arguments['create'] ) > $soft_cap ) {
                throw new \InvalidArgumentException( sprintf( 'create exceeds the %d-item soft cap. Use the rankout_connector_wc_batch_soft_cap filter to raise the limit.', $soft_cap ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            }
            $body['create'] = $arguments['create'];
        }
        if ( ! empty( $arguments['update'] ) ) {
            if ( count( $arguments['update'] ) > $soft_cap ) {
                throw new \InvalidArgumentException( sprintf( 'update exceeds the %d-item soft cap. Use the rankout_connector_wc_batch_soft_cap filter to raise the limit.', $soft_cap ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            }
            $body['update'] = $arguments['update'];
        }
        if ( ! empty( $arguments['delete'] ) ) {
            if ( count( $arguments['delete'] ) > $soft_cap ) {
                throw new \InvalidArgumentException( sprintf( 'delete exceeds the %d-item soft cap. Use the rankout_connector_wc_batch_soft_cap filter to raise the limit.', $soft_cap ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            }
            $body['delete'] = array_map( 'absint', $arguments['delete'] );
        }

        if ( empty( $body ) ) {
            throw new \InvalidArgumentException( 'At least one of `create`, `update`, or `delete` must be provided and non-empty.' );
        }

        wp_raise_memory_limit( 'admin' );
        set_time_limit( 300 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged

        return $this->rest_request( 'POST', '/wc/v3/orders/batch', $body );
    }
}
