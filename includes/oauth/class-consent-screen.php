<?php
namespace RankOut_Connector\OAuth;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}








class Consent_Screen {

    









    public static function render( $client, $user, $scope, array $request_params, $script_nonce = '' ) {

        $client_name       = isset( $client->client_name ) ? $client->client_name : __( 'Unknown Application', 'rankout-connector' );
        $client_id         = isset( $client->client_id ) ? $client->client_id : '';
        $client_id_prefix  = substr( $client_id, 0, 8 );
        $redirect_host     = wp_parse_url( $request_params['redirect_uri'], PHP_URL_HOST );
        $user_display_name = $user->display_name;
        $user_roles        = implode( ', ', $user->roles );
        $categories        = Scope_Map::get_categories();
        $default_scope     = Scope_Map::get_default_scope();
        $scope_list        = array_filter( array_map( 'trim', explode( ' ', $scope ) ) );
        $is_mcp_wildcard   = in_array( 'mcp', $scope_list, true );

        
        $template_vars = array(
            'client_name'       => $client_name,
            'client_id'         => $client_id,
            'client_id_prefix'  => $client_id_prefix,
            'redirect_host'     => $redirect_host,
            'user_display_name' => $user_display_name,
            'user_roles'        => $user_roles,
            'categories'        => $categories,
            'scope_list'        => $scope_list,
            'is_mcp_wildcard'   => $is_mcp_wildcard,
            'default_scope'     => $default_scope,
            'request_params'    => $request_params,
            'script_nonce'      => $script_nonce,
        );

        
        // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
        extract( $template_vars );

        ob_start();
        include __DIR__ . '/views/consent.php';
        return ob_get_clean();
    }
}
