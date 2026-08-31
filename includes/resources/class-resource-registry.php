<?php
namespace RankOut_Connector\Resources;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Resource_Registry {

    private $resources = array();

    public function register( Base_Resource $resource ) {
        $this->resources[ $resource->get_uri() ] = $resource;
    }

    public function get_resource( $uri ) {
        return isset( $this->resources[ $uri ] ) ? $this->resources[ $uri ] : null;
    }

    public function get_all_definitions() {
        $definitions = array();
        foreach ( $this->resources as $resource ) {
            $definitions[] = $resource->get_definition();
        }
        return $definitions;
    }

    public function auto_discover() {
        $resource_classes = array(
            
            'RankOut_Connector\\Resources\\Site_Info_Resource',
            'RankOut_Connector\\Resources\\Recent_Posts_Resource',
            'RankOut_Connector\\Resources\\Site_Stats_Resource',
            
            'RankOut_Connector\\Resources\\Reading_Settings_Resource',
            'RankOut_Connector\\Resources\\Discussion_Settings_Resource',
            'RankOut_Connector\\Resources\\Active_Plugins_Resource',
            'RankOut_Connector\\Resources\\Post_Types_Resource',
            'RankOut_Connector\\Resources\\Taxonomies_Resource',
            'RankOut_Connector\\Resources\\Authors_Resource',
            'RankOut_Connector\\Resources\\Theme_Templates_Resource',
            'RankOut_Connector\\Resources\\Menus_Resource',
            'RankOut_Connector\\Resources\\Draft_Posts_Resource',
            'RankOut_Connector\\Resources\\Scheduled_Posts_Resource',
            'RankOut_Connector\\Resources\\Recent_Media_Resource',
        );
        foreach ( $resource_classes as $class ) {
            if ( class_exists( $class ) ) {
                $this->register( new $class() );
            }
        }
    }
}
