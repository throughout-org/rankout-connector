<?php
namespace RankOut_Connector\Tools\EEAT;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Get_Content_Freshness extends Base_Tool {

	public function get_name() {
		return 'wp_get_content_freshness';
	}

	public function get_description() {
		return 'Lists published posts that have not been updated within a given number of days, sorted by staleness (oldest first). Useful for identifying content that needs a freshness review to maintain E-E-A-T signals. Parameters: post_type (default "post"), days (default 180), limit (default 50, max 200), offset (default 0). Returns { stale_count, threshold_days, posts: [{ id, title, url, published, last_modified, days_stale }] }.';
	}

	public function get_category() {
		return 'eeat';
	}

	public function get_required_capability() {
		return 'edit_posts';
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
				'post_type' => array(
					'type'        => 'string',
					'description' => 'Post type to check. Default: "post".',
				),
				'days'      => array(
					'type'        => 'integer',
					'description' => 'Consider content stale if not updated within this many days. Default: 180.',
					'minimum'     => 1,
				),
				'limit'     => array(
					'type'        => 'integer',
					'description' => 'Max number of stale posts to return (1–200). Default: 50.',
					'minimum'     => 1,
					'maximum'     => 200,
				),
				'offset'    => array(
					'type'        => 'integer',
					'description' => 'Pagination offset. Default: 0.',
					'minimum'     => 0,
				),
			),
		);
	}

	public function execute( array $arguments ) {
		$post_type = ! empty( $arguments['post_type'] ) ? sanitize_key( $arguments['post_type'] ) : 'post';
		$days      = isset( $arguments['days'] ) ? max( 1, (int) $arguments['days'] ) : 180;
		$limit     = isset( $arguments['limit'] ) ? min( max( 1, (int) $arguments['limit'] ), 200 ) : 50;
		$offset    = isset( $arguments['offset'] ) ? max( 0, (int) $arguments['offset'] ) : 0;

		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		$posts = get_posts( array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'offset'         => $offset,
			'orderby'        => 'modified',
			'order'          => 'ASC',
			'date_query'     => array(
				array(
					'column' => 'post_modified_gmt',
					'before' => $cutoff,
				),
			),
		) );

		$stale_posts = array();
		$now         = time();

		foreach ( $posts as $post ) {
			$modified_ts = strtotime( $post->post_modified_gmt );
			$days_stale  = (int) floor( ( $now - $modified_ts ) / DAY_IN_SECONDS );
			$stale_posts[] = array(
				'id'            => $post->ID,
				'title'         => get_the_title( $post->ID ),
				'url'           => get_permalink( $post->ID ),
				'published'     => gmdate( 'Y-m-d', strtotime( $post->post_date_gmt ) ),
				'last_modified' => gmdate( 'Y-m-d', $modified_ts ),
				'days_stale'    => $days_stale,
				'author'        => get_the_author_meta( 'display_name', (int) $post->post_author ),
			);
		}

		return array(
			'post_type'      => $post_type,
			'threshold_days' => $days,
			'stale_count'    => count( $stale_posts ),
			'offset'         => $offset,
			'posts'          => $stale_posts,
		);
	}
}
