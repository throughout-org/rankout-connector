<?php
namespace RankOut_Connector\Tools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}








class Dynamic_Tool extends Base_Tool {

    private $name;
    private $description;
    private $category;
    private $capability;
    private $input_schema;
    private $executor;
    private $annotations_override;

    










    public function __construct( array $config ) {
        $this->validate_required( $config, array( 'name', 'description', 'executor' ) );

        $this->name         = $config['name'];
        $this->description  = $config['description'];
        $this->executor     = $config['executor'];
        $this->category     = isset( $config['category'] )    ? $config['category']    : 'general';
        $this->capability   = isset( $config['capability'] )  ? $config['capability']  : 'manage_options';
        $this->input_schema = isset( $config['input_schema'] ) ? $config['input_schema'] : array(
            'type'       => 'object',
            'properties' => new \stdClass(),
        );
        $this->annotations_override = isset( $config['annotations'] ) ? $config['annotations'] : null;
    }

    public function get_name()                 { return $this->name; }
    public function get_description()          { return $this->description; }
    public function get_category()             { return $this->category; }
    public function get_required_capability()  { return $this->capability; }
    public function get_input_schema()         { return $this->input_schema; }

    public function get_annotations() {
        if ( null !== $this->annotations_override ) {
            return $this->annotations_override;
        }
        return parent::get_annotations();
    }

    public function execute( array $arguments ) {
        return call_user_func( $this->executor, $arguments );
    }
}
