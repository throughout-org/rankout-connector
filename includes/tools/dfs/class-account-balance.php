<?php
namespace RankOut_Connector\Tools\DFS;

use RankOut_Connector\Tools\Base_Tool;
use RankOut_Connector\DFS\DataforSEO_Client;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Account_Balance extends Base_Tool {

	public function get_name() {
		return 'wp_dfs_account_balance';
	}

	public function get_description() {
		return 'Returns DataforSEO account balance and total deposit (USD). Always returns a live reading. (meter: $0 — free)';
	}

	public function get_category() {
		return 'dfs';
	}

	public function get_required_capability() {
		return 'manage_options';
	}

	public function get_annotations() {
		return array(
			'title'           => 'DataforSEO account balance',
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'openWorldHint'   => true,
		);
	}

	public function get_input_schema() {
		return array(
			'type'       => 'object',
			'properties' => (object) array(),
		);
	}

	public function execute( array $arguments ) {
		$client = new DataforSEO_Client();
		return $client->get_balance();
	}
}
