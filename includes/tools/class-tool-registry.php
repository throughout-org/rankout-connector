<?php
namespace RankOut_Connector\Tools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tool_Registry {

    private $tools = array();

    






    private $lazy_classes = array();

    public function register( Base_Tool $tool ) {
        $this->tools[ $tool->get_name() ] = $tool;
    }

    public function get_tool( $name ) {
        if ( isset( $this->tools[ $name ] ) ) {
            return $this->tools[ $name ];
        }
        
        
        
        foreach ( $this->lazy_classes as $key => $class ) {
            $instance  = new $class();
            $tool_name = $instance->get_name();
            $this->tools[ $tool_name ] = $instance;
            unset( $this->lazy_classes[ $key ] );
            if ( $tool_name === $name ) {
                return $instance;
            }
        }
        return null;
    }

    public function get_all_definitions() {
        $this->materialize_all();
        $definitions = array();
        foreach ( $this->tools as $tool ) {
            $definitions[] = $tool->get_definition();
        }
        return $definitions;
    }

    public function get_all_tool_names() {
        $this->materialize_all();
        return array_keys( $this->tools );
    }

    public function get_tools_by_category() {
        $this->materialize_all();
        $categorized = array();
        foreach ( $this->tools as $tool ) {
            $category = $tool->get_category();
            if ( ! isset( $categorized[ $category ] ) ) {
                $categorized[ $category ] = array();
            }
            $categorized[ $category ][] = $tool->get_definition();
        }
        return $categorized;
    }

    private function materialize_all() {
        foreach ( $this->lazy_classes as $key => $class ) {
            $instance = new $class();
            $this->tools[ $instance->get_name() ] = $instance;
            unset( $this->lazy_classes[ $key ] );
        }
    }

    public function auto_discover() {
        $tool_classes = array(
            'RankOut_Connector\\Tools\\Posts\\List_Posts',
            'RankOut_Connector\\Tools\\Posts\\Get_Post',
            'RankOut_Connector\\Tools\\Posts\\Create_Post',
            'RankOut_Connector\\Tools\\Posts\\Update_Post',
            'RankOut_Connector\\Tools\\Posts\\Delete_Post',
            'RankOut_Connector\\Tools\\Posts\\Search_Posts',
            'RankOut_Connector\\Tools\\Pages\\List_Pages',
            'RankOut_Connector\\Tools\\Pages\\Get_Page',
            'RankOut_Connector\\Tools\\Pages\\Create_Page',
            'RankOut_Connector\\Tools\\Pages\\Update_Page',
            'RankOut_Connector\\Tools\\Pages\\Delete_Page',
            'RankOut_Connector\\Tools\\Media\\List_Media',
            'RankOut_Connector\\Tools\\Media\\Get_Media',
            'RankOut_Connector\\Tools\\Media\\Upload_Media',
            'RankOut_Connector\\Tools\\Media\\Update_Media',
            'RankOut_Connector\\Tools\\Media\\Delete_Media',
            'RankOut_Connector\\Tools\\Taxonomy\\List_Categories',
            'RankOut_Connector\\Tools\\Taxonomy\\Get_Category',
            'RankOut_Connector\\Tools\\Taxonomy\\Create_Category',
            'RankOut_Connector\\Tools\\Taxonomy\\Update_Category',
            'RankOut_Connector\\Tools\\Taxonomy\\Delete_Category',
            'RankOut_Connector\\Tools\\Taxonomy\\List_Tags',
            'RankOut_Connector\\Tools\\Taxonomy\\Get_Tag',
            'RankOut_Connector\\Tools\\Taxonomy\\Create_Tag',
            'RankOut_Connector\\Tools\\Taxonomy\\Update_Tag',
            'RankOut_Connector\\Tools\\Taxonomy\\Delete_Tag',
            'RankOut_Connector\\Tools\\Comments\\List_Comments',
            'RankOut_Connector\\Tools\\Comments\\Get_Comment',
            'RankOut_Connector\\Tools\\Comments\\Create_Comment',
            'RankOut_Connector\\Tools\\Comments\\Update_Comment',
            'RankOut_Connector\\Tools\\Comments\\Delete_Comment',
            'RankOut_Connector\\Tools\\Users\\List_Users',
            'RankOut_Connector\\Tools\\Users\\Get_User',
            'RankOut_Connector\\Tools\\Users\\Create_User',
            'RankOut_Connector\\Tools\\Users\\Update_User',
            'RankOut_Connector\\Tools\\Users\\Delete_User',
            'RankOut_Connector\\Tools\\Site\\Get_Site_Settings',
            'RankOut_Connector\\Tools\\Site\\Update_Site_Settings',
            'RankOut_Connector\\Tools\\Site\\Get_Post_Types',
            'RankOut_Connector\\Tools\\Site\\Get_Taxonomies',
            'RankOut_Connector\\Tools\\Menus\\List_Menus',
            'RankOut_Connector\\Tools\\Menus\\Get_Menu',
            'RankOut_Connector\\Tools\\Menus\\List_Menu_Items',
            'RankOut_Connector\\Tools\\Menus\\Create_Menu_Item',
            'RankOut_Connector\\Tools\\Plugins\\List_Plugins',
            'RankOut_Connector\\Tools\\Themes\\List_Themes',
            'RankOut_Connector\\Tools\\Themes\\Get_Active_Theme',
            'RankOut_Connector\\Tools\\Site_Health\\Get_Site_Health',

            'RankOut_Connector\\Tools\\Revisions\\List_Revisions',
            'RankOut_Connector\\Tools\\Revisions\\Get_Revision',
            'RankOut_Connector\\Tools\\Revisions\\Delete_Revision',
            
            'RankOut_Connector\\Tools\\Meta\\Get_Post_Meta',
            'RankOut_Connector\\Tools\\Meta\\Update_Post_Meta',
            
            'RankOut_Connector\\Tools\\Search\\Search',
            
            'RankOut_Connector\\Tools\\Blocks\\List_Blocks',
            'RankOut_Connector\\Tools\\Blocks\\Get_Block',
            'RankOut_Connector\\Tools\\Blocks\\Create_Block',
            'RankOut_Connector\\Tools\\Blocks\\Update_Block',
            'RankOut_Connector\\Tools\\Blocks\\Delete_Block',
            
            'RankOut_Connector\\Tools\\Site\\Get_Post_Statuses',
            
            'RankOut_Connector\\Tools\\CPT\\List_CPT_Items',
            'RankOut_Connector\\Tools\\CPT\\Get_CPT_Item',
            'RankOut_Connector\\Tools\\CPT\\Create_CPT_Item',
            'RankOut_Connector\\Tools\\CPT\\Update_CPT_Item',
            'RankOut_Connector\\Tools\\CPT\\Delete_CPT_Item',
            
            'RankOut_Connector\\Tools\\Menus\\Create_Menu',
            'RankOut_Connector\\Tools\\Menus\\Update_Menu',
            'RankOut_Connector\\Tools\\Menus\\Delete_Menu',
            'RankOut_Connector\\Tools\\Menus\\Update_Menu_Item',
            'RankOut_Connector\\Tools\\Menus\\Delete_Menu_Item',
            
            'RankOut_Connector\\Tools\\Templates\\List_Templates',
            'RankOut_Connector\\Tools\\Templates\\Get_Template',
            'RankOut_Connector\\Tools\\Templates\\Update_Template',
            
            'RankOut_Connector\\Tools\\Styles\\Get_Global_Styles',
            'RankOut_Connector\\Tools\\Styles\\Update_Global_Styles',
            
            'RankOut_Connector\\Tools\\WooCommerce\\List_Products',
            'RankOut_Connector\\Tools\\WooCommerce\\Get_Product',
            'RankOut_Connector\\Tools\\WooCommerce\\Create_Product',
            'RankOut_Connector\\Tools\\WooCommerce\\Update_Product',
            'RankOut_Connector\\Tools\\WooCommerce\\Delete_Product',
            'RankOut_Connector\\Tools\\WooCommerce\\List_Product_Variations',
            'RankOut_Connector\\Tools\\WooCommerce\\Get_Product_Variation',
            'RankOut_Connector\\Tools\\WooCommerce\\Create_Product_Variation',
            'RankOut_Connector\\Tools\\WooCommerce\\Update_Product_Variation',
            'RankOut_Connector\\Tools\\WooCommerce\\Delete_Product_Variation',
            'RankOut_Connector\\Tools\\WooCommerce\\List_Product_Categories',
            'RankOut_Connector\\Tools\\WooCommerce\\List_Orders',
            'RankOut_Connector\\Tools\\WooCommerce\\Get_Order',
            'RankOut_Connector\\Tools\\WooCommerce\\Create_Order',
            'RankOut_Connector\\Tools\\WooCommerce\\Update_Order',
            'RankOut_Connector\\Tools\\WooCommerce\\List_Order_Notes',
            'RankOut_Connector\\Tools\\WooCommerce\\Create_Order_Note',
            'RankOut_Connector\\Tools\\WooCommerce\\List_Order_Refunds',
            'RankOut_Connector\\Tools\\WooCommerce\\List_Customers',
            'RankOut_Connector\\Tools\\WooCommerce\\Get_Customer',
            'RankOut_Connector\\Tools\\WooCommerce\\Create_Customer',
            'RankOut_Connector\\Tools\\WooCommerce\\Update_Customer',
            'RankOut_Connector\\Tools\\WooCommerce\\Delete_Customer',
            'RankOut_Connector\\Tools\\WooCommerce\\List_Coupons',
            'RankOut_Connector\\Tools\\WooCommerce\\Create_Coupon',
            'RankOut_Connector\\Tools\\WooCommerce\\Update_Coupon',
            'RankOut_Connector\\Tools\\WooCommerce\\Delete_Coupon',
            'RankOut_Connector\\Tools\\WooCommerce\\Report_Sales',
            'RankOut_Connector\\Tools\\WooCommerce\\Report_Top_Sellers',
            'RankOut_Connector\\Tools\\WooCommerce\\Report_Orders',
            'RankOut_Connector\\Tools\\WooCommerce\\Report_Products',
            'RankOut_Connector\\Tools\\WooCommerce\\Report_Customers',
            'RankOut_Connector\\Tools\\WooCommerce\\List_Webhooks',
            'RankOut_Connector\\Tools\\WooCommerce\\Create_Webhook',
            'RankOut_Connector\\Tools\\WooCommerce\\Update_Webhook',
            'RankOut_Connector\\Tools\\WooCommerce\\Delete_Webhook',
            'RankOut_Connector\\Tools\\WooCommerce\\List_Shipping_Zones',
            'RankOut_Connector\\Tools\\WooCommerce\\List_Shipping_Methods',
            'RankOut_Connector\\Tools\\WooCommerce\\List_Tax_Rates',
            'RankOut_Connector\\Tools\\WooCommerce\\List_Payment_Gateways',
            
            'RankOut_Connector\\Tools\\ACF\\Get_Fields',
            'RankOut_Connector\\Tools\\ACF\\Update_Fields',
            'RankOut_Connector\\Tools\\ACF\\Get_User_Fields',
            'RankOut_Connector\\Tools\\ACF\\Update_User_Fields',
            'RankOut_Connector\\Tools\\ACF\\Get_Term_Fields',
            'RankOut_Connector\\Tools\\ACF\\List_Field_Groups',
            
            'RankOut_Connector\\Tools\\Events_Calendar\\List_Events',
            'RankOut_Connector\\Tools\\Events_Calendar\\Get_Event',
            'RankOut_Connector\\Tools\\Events_Calendar\\Create_Event',
            'RankOut_Connector\\Tools\\Events_Calendar\\Update_Event',
            'RankOut_Connector\\Tools\\Events_Calendar\\Delete_Event',
            'RankOut_Connector\\Tools\\Events_Calendar\\List_Venues',
            'RankOut_Connector\\Tools\\Events_Calendar\\Get_Venue',
            'RankOut_Connector\\Tools\\Events_Calendar\\List_Organizers',
            'RankOut_Connector\\Tools\\Events_Calendar\\Create_Venue',
            'RankOut_Connector\\Tools\\Events_Calendar\\Create_Organizer',
            
            'RankOut_Connector\\Tools\\BuddyPress\\List_Members',
            'RankOut_Connector\\Tools\\BuddyPress\\Get_Member',
            'RankOut_Connector\\Tools\\BuddyPress\\List_Activity',
            'RankOut_Connector\\Tools\\BuddyPress\\Create_Activity',
            'RankOut_Connector\\Tools\\BuddyPress\\Delete_Activity',
            'RankOut_Connector\\Tools\\BuddyPress\\List_Groups',
            'RankOut_Connector\\Tools\\BuddyPress\\Get_Group',
            'RankOut_Connector\\Tools\\BuddyPress\\List_Group_Members',
            'RankOut_Connector\\Tools\\BuddyPress\\List_Message_Threads',
            'RankOut_Connector\\Tools\\BuddyPress\\Get_Message_Thread',
            
            'RankOut_Connector\\Tools\\SEO\\Yoast_Get_Head',
            'RankOut_Connector\\Tools\\SEO\\Yoast_Get_Post_Seo',
            'RankOut_Connector\\Tools\\SEO\\Yoast_Update_Post_Seo',
            'RankOut_Connector\\Tools\\SEO\\Rankmath_Get_Head',
            'RankOut_Connector\\Tools\\SEO\\Rankmath_Get_Post_Seo',
            'RankOut_Connector\\Tools\\SEO\\Rankmath_Update_Post_Seo',
            'RankOut_Connector\\Tools\\SEO\\Aioseo_Get_Post_Seo',
            'RankOut_Connector\\Tools\\SEO\\Aioseo_Update_Post_Seo',
            'RankOut_Connector\\Tools\\GSC\\List_Sites',
            'RankOut_Connector\\Tools\\GSC\\Get_Site',
            'RankOut_Connector\\Tools\\GSC\\Query_Performance',
            'RankOut_Connector\\Tools\\GSC\\List_Sitemaps',
            'RankOut_Connector\\Tools\\GSC\\Get_Sitemap',
            'RankOut_Connector\\Tools\\GSC\\Inspect_Url',

            'RankOut_Connector\\Tools\\GA\\List_Account_Summaries',
            'RankOut_Connector\\Tools\\GA\\Get_Property',
            'RankOut_Connector\\Tools\\GA\\List_Data_Streams',
            'RankOut_Connector\\Tools\\GA\\List_Custom_Dimensions',
            'RankOut_Connector\\Tools\\GA\\List_Custom_Metrics',
            'RankOut_Connector\\Tools\\GA\\List_Conversion_Events',
            'RankOut_Connector\\Tools\\GA\\Get_Metadata',
            'RankOut_Connector\\Tools\\GA\\Run_Report',
            'RankOut_Connector\\Tools\\GA\\Run_Pivot_Report',
            'RankOut_Connector\\Tools\\GA\\Run_Realtime_Report',
            'RankOut_Connector\\Tools\\GA\\Check_Compatibility',

            
            'RankOut_Connector\\Tools\\DFS\\Serp_Google_Organic_Live',
            'RankOut_Connector\\Tools\\DFS\\Keywords_Search_Volume_Live',
            'RankOut_Connector\\Tools\\DFS\\Backlinks_Referring_Domains_Live',
            'RankOut_Connector\\Tools\\DFS\\On_Page_Instant_Pages',
            'RankOut_Connector\\Tools\\DFS\\Account_Balance',
            'RankOut_Connector\\Tools\\DFS\\Labs_Keywords_For_Site_Live',
            'RankOut_Connector\\Tools\\DFS\\Labs_Ranked_Keywords_Live',
            'RankOut_Connector\\Tools\\DFS\\Backlinks_Summary_Live',

            
            'RankOut_Connector\\Tools\\Posts\\Count_Posts',
            'RankOut_Connector\\Tools\\Taxonomy\\Count_Terms',
            'RankOut_Connector\\Tools\\Media\\Count_Media',
            
            'RankOut_Connector\\Tools\\Revisions\\Restore_Revision',
            
            'RankOut_Connector\\Tools\\Meta\\Delete_Post_Meta',
            
            'RankOut_Connector\\Tools\\Taxonomy\\Get_Term_Meta',
            'RankOut_Connector\\Tools\\Taxonomy\\Update_Term_Meta',
            'RankOut_Connector\\Tools\\Taxonomy\\Delete_Term_Meta',
            
            'RankOut_Connector\\Tools\\Posts\\Add_Post_Terms',
            
            'RankOut_Connector\\Tools\\Users\\Get_User_Meta',
            'RankOut_Connector\\Tools\\Users\\Update_User_Meta',
            'RankOut_Connector\\Tools\\Users\\Delete_User_Meta',
            
            'RankOut_Connector\\Tools\\WooCommerce\\Batch_Update_Products',
            'RankOut_Connector\\Tools\\WooCommerce\\Batch_Update_Orders',
            'RankOut_Connector\\Tools\\WooCommerce\\Batch_Update_Variations',
            'RankOut_Connector\\Tools\\WooCommerce\\List_Product_Attributes',
            'RankOut_Connector\\Tools\\WooCommerce\\Create_Product_Attribute',
            'RankOut_Connector\\Tools\\WooCommerce\\Set_Product_Attributes',

            
            
            'RankOut_Connector\\Tools\\Posts\\Get_Post_Full',
            'RankOut_Connector\\Tools\\Media\\Upload_Media_From_Url',
            'RankOut_Connector\\Tools\\Taxonomy\\Get_Term',
            'RankOut_Connector\\Tools\\Taxonomy\\Create_Term',
            'RankOut_Connector\\Tools\\Taxonomy\\Update_Term',
            'RankOut_Connector\\Tools\\Taxonomy\\Delete_Term',
            'RankOut_Connector\\Tools\\Posts\\Replace_In_Post',

            
            'RankOut_Connector\\Tools\\History\\History_List',
            'RankOut_Connector\\Tools\\History\\History_Get',
            'RankOut_Connector\\Tools\\History\\History_Diff',

            
            'RankOut_Connector\\Tools\\Semrush\\Domain_Overview',
            'RankOut_Connector\\Tools\\Semrush\\Domain_Organic_Keywords',
            'RankOut_Connector\\Tools\\Semrush\\Competitors_Organic',
            'RankOut_Connector\\Tools\\Semrush\\Keyword_Overview',
            'RankOut_Connector\\Tools\\Semrush\\Related_Keywords',
            'RankOut_Connector\\Tools\\Semrush\\Keyword_Difficulty',
            'RankOut_Connector\\Tools\\Semrush\\Phrase_Questions',
            'RankOut_Connector\\Tools\\Semrush\\Backlinks_Overview',
            'RankOut_Connector\\Tools\\Semrush\\Backlinks',
            'RankOut_Connector\\Tools\\Semrush\\Referring_Domains',
            'RankOut_Connector\\Tools\\Semrush\\Anchors',
            'RankOut_Connector\\Tools\\Semrush\\Url_Organic_Keywords',
            'RankOut_Connector\\Tools\\Semrush\\Api_Units_Balance',

            // GEO — Generative Engine Optimisation
            'RankOut_Connector\\Tools\\GEO\\Get_Llms_Txt',
            'RankOut_Connector\\Tools\\GEO\\Update_Llms_Txt',
            'RankOut_Connector\\Tools\\GEO\\Get_Entity_Context',
            'RankOut_Connector\\Tools\\GEO\\Audit_Geo_Readiness',

            // Schema — structured data (JSON-LD) management
            'RankOut_Connector\\Tools\\Schema\\Get_Post_Schema',
            'RankOut_Connector\\Tools\\Schema\\Update_Post_Schema',
            'RankOut_Connector\\Tools\\Schema\\Audit_Schema_Coverage',
            'RankOut_Connector\\Tools\\Schema\\List_Schema_Types',

            // Filesystem — theme, plugin, and wp-content source file access
            'RankOut_Connector\\Tools\\Filesystem\\Get_Theme_File',
            'RankOut_Connector\\Tools\\Filesystem\\List_Theme_Files',
            'RankOut_Connector\\Tools\\Filesystem\\Get_Plugin_File',
            'RankOut_Connector\\Tools\\Filesystem\\List_Plugin_Files',
            'RankOut_Connector\\Tools\\Filesystem\\List_Wp_Content',
            'RankOut_Connector\\Tools\\Filesystem\\Get_Wp_Content_File',

            // Database — read-only SQL queries
            'RankOut_Connector\\Tools\\Database\\Run_DB_Query',

            // AEO — Answer Engine Optimisation
            'RankOut_Connector\\Tools\\AEO\\Get_Faq_Blocks',
            'RankOut_Connector\\Tools\\AEO\\Create_Faq_Block',
            'RankOut_Connector\\Tools\\AEO\\Audit_Answer_Readiness',

            // E-E-A-T / HEO — Human Experience Optimisation
            'RankOut_Connector\\Tools\\EEAT\\Get_Eeat_Signals',
            'RankOut_Connector\\Tools\\EEAT\\Get_Content_Freshness',
            'RankOut_Connector\\Tools\\EEAT\\Get_Internal_Links',
            'RankOut_Connector\\Tools\\EEAT\\Suggest_Internal_Links',

            // Reporting — site-wide aggregated audits
            'RankOut_Connector\\Tools\\Reporting\\Seo_Audit_Site',
            'RankOut_Connector\\Tools\\Reporting\\Content_Gap_Report',

        );
        $plugin_namespaces = array(
            'RankOut_Connector\\Tools\\WooCommerce\\',
            'RankOut_Connector\\Tools\\ACF\\',
            'RankOut_Connector\\Tools\\Events_Calendar\\',
            'RankOut_Connector\\Tools\\BuddyPress\\',
            'RankOut_Connector\\Tools\\SEO\\',
            'RankOut_Connector\\Tools\\GSC\\',
            'RankOut_Connector\\Tools\\GA\\',
            'RankOut_Connector\\Tools\\DFS\\',
            'RankOut_Connector\\Tools\\Semrush\\',
        );

        foreach ( $tool_classes as $class ) {
            if ( ! class_exists( $class ) ) {
                $is_plugin_class = false;
                foreach ( $plugin_namespaces as $ns ) {
                    if ( strncmp( $class, $ns, strlen( $ns ) ) === 0 ) {
                        $is_plugin_class = true;
                        break;
                    }
                }
                if ( ! $is_plugin_class ) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log( 'RankOut Connector: Tool class not found, skipping: ' . $class );
                }
                continue;
            }
            $this->lazy_classes[] = $class;
        }
    }
}
