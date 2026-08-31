<?php
namespace RankOut_Connector\Tools\Schema;

use RankOut_Connector\Tools\Base_Tool;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class List_Schema_Types extends Base_Tool {

	public function get_name() {
		return 'wp_list_schema_types';
	}

	public function get_description() {
		return 'Returns a curated list of supported schema.org types with their required and recommended fields. Useful for building valid JSON-LD to pass to wp_update_post_schema. No parameters required. Returns { types: [{ type, description, use_case, required_fields: [], recommended_fields: [], example }] }.';
	}

	public function get_category() {
		return 'schema';
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
			'properties' => new \stdClass(),
		);
	}

	public function execute( array $arguments ) {
		$types = array(
			array(
				'type'                => 'Article',
				'description'         => 'A news, blog, or general article.',
				'use_case'            => 'Blog posts, news articles, editorial content.',
				'required_fields'     => array( '@context', '@type', 'headline', 'author', 'datePublished' ),
				'recommended_fields'  => array( 'description', 'image', 'dateModified', 'publisher', 'url', 'mainEntityOfPage' ),
				'example'             => array(
					'@context'         => 'https://schema.org',
					'@type'            => 'Article',
					'headline'         => 'My Article Title',
					'author'           => array( '@type' => 'Person', 'name' => 'Author Name' ),
					'datePublished'    => '2025-01-01',
					'dateModified'     => '2025-06-01',
					'image'            => 'https://example.com/image.jpg',
					'publisher'        => array( '@type' => 'Organization', 'name' => 'My Site', 'logo' => array( '@type' => 'ImageObject', 'url' => 'https://example.com/logo.png' ) ),
				),
			),
			array(
				'type'                => 'BlogPosting',
				'description'         => 'A blog post (subtype of Article).',
				'use_case'            => 'Informal blog entries and personal posts.',
				'required_fields'     => array( '@context', '@type', 'headline', 'author', 'datePublished' ),
				'recommended_fields'  => array( 'description', 'image', 'dateModified', 'url' ),
				'example'             => array(
					'@context'      => 'https://schema.org',
					'@type'         => 'BlogPosting',
					'headline'      => 'Blog Post Title',
					'author'        => array( '@type' => 'Person', 'name' => 'Jane Doe' ),
					'datePublished' => '2025-01-15',
				),
			),
			array(
				'type'                => 'FAQPage',
				'description'         => 'A page containing frequently asked questions and answers.',
				'use_case'            => 'FAQ pages, help articles. Eligible for Google FAQ rich result.',
				'required_fields'     => array( '@context', '@type', 'mainEntity' ),
				'recommended_fields'  => array(),
				'example'             => array(
					'@context'   => 'https://schema.org',
					'@type'      => 'FAQPage',
					'mainEntity' => array(
						array(
							'@type'          => 'Question',
							'name'           => 'What is MCP?',
							'acceptedAnswer' => array( '@type' => 'Answer', 'text' => 'MCP stands for Model Context Protocol.' ),
						),
					),
				),
			),
			array(
				'type'                => 'HowTo',
				'description'         => 'Instructions for accomplishing a task.',
				'use_case'            => 'Tutorial posts, step-by-step guides. Eligible for Google HowTo rich result.',
				'required_fields'     => array( '@context', '@type', 'name', 'step' ),
				'recommended_fields'  => array( 'description', 'image', 'totalTime', 'supply', 'tool' ),
				'example'             => array(
					'@context' => 'https://schema.org',
					'@type'    => 'HowTo',
					'name'     => 'How to bake a cake',
					'step'     => array(
						array( '@type' => 'HowToStep', 'name' => 'Mix ingredients', 'text' => 'Combine flour, sugar, and eggs.' ),
						array( '@type' => 'HowToStep', 'name' => 'Bake', 'text' => 'Bake at 180°C for 30 minutes.' ),
					),
				),
			),
			array(
				'type'                => 'Product',
				'description'         => 'A product for sale.',
				'use_case'            => 'Product pages, WooCommerce items. Eligible for Google Shopping rich results.',
				'required_fields'     => array( '@context', '@type', 'name' ),
				'recommended_fields'  => array( 'description', 'image', 'sku', 'brand', 'offers', 'aggregateRating' ),
				'example'             => array(
					'@context'    => 'https://schema.org',
					'@type'       => 'Product',
					'name'        => 'Widget Pro',
					'description' => 'A professional widget.',
					'offers'      => array( '@type' => 'Offer', 'price' => '29.99', 'priceCurrency' => 'USD', 'availability' => 'https://schema.org/InStock' ),
				),
			),
			array(
				'type'                => 'LocalBusiness',
				'description'         => 'A physical business with a local presence.',
				'use_case'            => 'Business contact/about pages. Boosts local SEO and Google Business results.',
				'required_fields'     => array( '@context', '@type', 'name', 'address' ),
				'recommended_fields'  => array( 'telephone', 'openingHours', 'url', 'image', 'geo', 'priceRange', 'aggregateRating' ),
				'example'             => array(
					'@context'  => 'https://schema.org',
					'@type'     => 'LocalBusiness',
					'name'      => 'My Bakery',
					'address'   => array( '@type' => 'PostalAddress', 'streetAddress' => '123 Main St', 'addressLocality' => 'Springfield', 'addressCountry' => 'US' ),
					'telephone' => '+1-555-555-5555',
				),
			),
			array(
				'type'                => 'Event',
				'description'         => 'An event happening at a specific time and place.',
				'use_case'            => 'Event listings, conferences, webinars. Eligible for Google Events rich results.',
				'required_fields'     => array( '@context', '@type', 'name', 'startDate', 'location' ),
				'recommended_fields'  => array( 'endDate', 'description', 'image', 'organizer', 'offers', 'eventStatus', 'eventAttendanceMode' ),
				'example'             => array(
					'@context'  => 'https://schema.org',
					'@type'     => 'Event',
					'name'      => 'Annual Conference',
					'startDate' => '2025-09-15T09:00',
					'endDate'   => '2025-09-15T17:00',
					'location'  => array( '@type' => 'Place', 'name' => 'Convention Center', 'address' => '456 Event Ave, New York' ),
				),
			),
			array(
				'type'                => 'Recipe',
				'description'         => 'A cooking recipe.',
				'use_case'            => 'Food blogs and recipe posts. Eligible for Google Recipe rich results.',
				'required_fields'     => array( '@context', '@type', 'name', 'recipeIngredient', 'recipeInstructions' ),
				'recommended_fields'  => array( 'image', 'author', 'datePublished', 'description', 'prepTime', 'cookTime', 'totalTime', 'recipeYield', 'nutrition', 'aggregateRating' ),
				'example'             => array(
					'@context'            => 'https://schema.org',
					'@type'               => 'Recipe',
					'name'                => 'Chocolate Chip Cookies',
					'recipeIngredient'    => array( '2 cups flour', '1 cup sugar', '1 cup chocolate chips' ),
					'recipeInstructions'  => array( array( '@type' => 'HowToStep', 'text' => 'Mix dry ingredients.' ), array( '@type' => 'HowToStep', 'text' => 'Bake at 180°C for 12 minutes.' ) ),
					'totalTime'           => 'PT30M',
				),
			),
			array(
				'type'                => 'Review',
				'description'         => 'A review of a product, service, book, movie, etc.',
				'use_case'            => 'Review articles and posts. Eligible for Google Review Snippet.',
				'required_fields'     => array( '@context', '@type', 'itemReviewed', 'reviewRating', 'author' ),
				'recommended_fields'  => array( 'reviewBody', 'datePublished', 'publisher' ),
				'example'             => array(
					'@context'     => 'https://schema.org',
					'@type'        => 'Review',
					'itemReviewed' => array( '@type' => 'Product', 'name' => 'Widget Pro' ),
					'reviewRating' => array( '@type' => 'Rating', 'ratingValue' => '5', 'bestRating' => '5' ),
					'author'       => array( '@type' => 'Person', 'name' => 'Jane Doe' ),
					'reviewBody'   => 'Excellent product, highly recommended.',
				),
			),
			array(
				'type'                => 'BreadcrumbList',
				'description'         => 'Navigation breadcrumb trail.',
				'use_case'            => 'Any page with breadcrumbs. Displays breadcrumb path in Google search results.',
				'required_fields'     => array( '@context', '@type', 'itemListElement' ),
				'recommended_fields'  => array(),
				'example'             => array(
					'@context'        => 'https://schema.org',
					'@type'           => 'BreadcrumbList',
					'itemListElement' => array(
						array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://example.com' ),
						array( '@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => 'https://example.com/blog' ),
						array( '@type' => 'ListItem', 'position' => 3, 'name' => 'My Article' ),
					),
				),
			),
			array(
				'type'                => 'Organization',
				'description'         => 'An organization such as a company or non-profit.',
				'use_case'            => 'Homepage or about page. Helps Google associate your brand with a knowledge panel.',
				'required_fields'     => array( '@context', '@type', 'name', 'url' ),
				'recommended_fields'  => array( 'logo', 'sameAs', 'contactPoint', 'address', 'description' ),
				'example'             => array(
					'@context' => 'https://schema.org',
					'@type'    => 'Organization',
					'name'     => 'EasyMCP AI',
					'url'      => 'https://easymcpai.com',
					'logo'     => 'https://easymcpai.com/logo.png',
					'sameAs'   => array( 'https://twitter.com/easymcpai', 'https://linkedin.com/company/easymcpai' ),
				),
			),
			array(
				'type'                => 'Person',
				'description'         => 'A person (real or fictional).',
				'use_case'            => 'Author bio pages. Builds E-E-A-T author entity for GEO/AEO.',
				'required_fields'     => array( '@context', '@type', 'name' ),
				'recommended_fields'  => array( 'url', 'image', 'jobTitle', 'worksFor', 'sameAs', 'description' ),
				'example'             => array(
					'@context' => 'https://schema.org',
					'@type'    => 'Person',
					'name'     => 'Jane Doe',
					'jobTitle' => 'Lead Developer',
					'url'      => 'https://example.com/about/jane',
					'sameAs'   => array( 'https://twitter.com/janedoe', 'https://linkedin.com/in/janedoe' ),
				),
			),
			array(
				'type'                => 'WebSite',
				'description'         => 'Represents the website itself. Enables the Google Sitelinks Searchbox.',
				'use_case'            => 'Homepage only. One per site.',
				'required_fields'     => array( '@context', '@type', 'name', 'url' ),
				'recommended_fields'  => array( 'description', 'potentialAction' ),
				'example'             => array(
					'@context'        => 'https://schema.org',
					'@type'           => 'WebSite',
					'name'            => 'My WordPress Site',
					'url'             => 'https://example.com',
					'potentialAction' => array( '@type' => 'SearchAction', 'target' => 'https://example.com/?s={search_term_string}', 'query-input' => 'required name=search_term_string' ),
				),
			),
			array(
				'type'                => 'VideoObject',
				'description'         => 'A video file or stream.',
				'use_case'            => 'Posts with embedded video content. Eligible for Google Video rich results.',
				'required_fields'     => array( '@context', '@type', 'name', 'description', 'thumbnailUrl', 'uploadDate' ),
				'recommended_fields'  => array( 'contentUrl', 'embedUrl', 'duration', 'interactionStatistic' ),
				'example'             => array(
					'@context'     => 'https://schema.org',
					'@type'        => 'VideoObject',
					'name'         => 'How to use RankOut Connector',
					'description'  => 'A walkthrough of the RankOut Connector plugin.',
					'thumbnailUrl' => 'https://example.com/thumb.jpg',
					'uploadDate'   => '2025-01-01',
					'contentUrl'   => 'https://example.com/video.mp4',
				),
			),
			array(
				'type'                => 'Course',
				'description'         => 'An educational course.',
				'use_case'            => 'Online course landing pages. Eligible for Google Courses rich results.',
				'required_fields'     => array( '@context', '@type', 'name', 'description', 'provider' ),
				'recommended_fields'  => array( 'url', 'hasCourseInstance', 'offers', 'educationalLevel', 'teaches' ),
				'example'             => array(
					'@context'    => 'https://schema.org',
					'@type'       => 'Course',
					'name'        => 'WordPress SEO Mastery',
					'description' => 'Learn how to rank #1 on Google.',
					'provider'    => array( '@type' => 'Organization', 'name' => 'My Academy', 'sameAs' => 'https://example.com' ),
				),
			),
		);

		return array(
			'count' => count( $types ),
			'types' => $types,
		);
	}
}
