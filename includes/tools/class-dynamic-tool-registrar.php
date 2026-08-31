<?php
namespace RankOut_Connector\Tools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}









class Dynamic_Tool_Registrar {

    




    public function register_to( Tool_Registry $registry ) {
        $this->register_ability_tools( $registry );
    }

    
    
    

    private function register_ability_tools( Tool_Registry $registry ) {
        if ( ! \function_exists( 'wp_get_abilities' ) ) {
            return;
        }

        $enabled_slugs = (array) \get_option( 'rankout_connector_enabled_abilities', array() );
        if ( empty( $enabled_slugs ) ) {
            return;
        }

        
        
        
        $all_abilities = \wp_get_abilities();

        foreach ( $enabled_slugs as $slug ) {
            $slug = (string) $slug;
            if ( ! isset( $all_abilities[ $slug ] ) ) {
                continue; 
            }

            $ability = $all_abilities[ $slug ];

            $tool_name = 'wp_ability_' . self::normalize_identifier( $slug );

            
            $label = $ability->get_label() ?: $slug;

            
            $description = $ability->get_description();
            if ( ! empty( $description ) ) {
                $description = "{$description} (ability: {$slug})";
            } else {
                $description = "Execute WordPress Ability: {$label} ({$slug})";
            }

            
            $input_schema = method_exists( $ability, 'get_input_schema' ) ? $ability->get_input_schema() : null;
            if ( empty( $input_schema ) || ! is_array( $input_schema ) ) {
                $input_schema = array( 'type' => 'object', 'properties' => new \stdClass() );
            }

            
            $annotations_meta = array();
            if ( method_exists( $ability, 'get_meta_item' ) ) {
                $annotations_meta = (array) $ability->get_meta_item( 'annotations' );
            } elseif ( method_exists( $ability, 'get_annotations' ) ) {
                $annotations_meta = (array) $ability->get_annotations();
            }
            $readonly    = isset( $annotations_meta['readonly'] )    ? (bool) $annotations_meta['readonly']    : false;
            $destructive = isset( $annotations_meta['destructive'] ) ? (bool) $annotations_meta['destructive'] : false;
            $idempotent  = isset( $annotations_meta['idempotent'] )  ? (bool) $annotations_meta['idempotent']  : false;

            $captured_ability = $ability; 
            $captured_slug    = $slug;

            $tool = new Dynamic_Tool( array(
                'name'        => $tool_name,
                'description' => $description,
                'category'    => 'abilities',
                
                
                
                
                'capability'  => 'read',
                'input_schema' => $input_schema,
                'annotations' => array(
                    'title'           => $label,
                    'readOnlyHint'    => $readonly,
                    'destructiveHint' => $destructive,
                    'idempotentHint'  => $idempotent,
                    'openWorldHint'   => true,
                ),
                'executor' => function ( array $arguments ) use ( $captured_ability, $captured_slug ) {
                    
                    $ability = \function_exists( 'wp_get_ability' ) ? \wp_get_ability( $captured_slug ) : $captured_ability;
                    if ( ! $ability ) {
                        throw new \RuntimeException( "Ability '{$captured_slug}' is no longer registered." ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                    }

                    
                    
                    
                    
                    
                    $has_input_schema = method_exists( $ability, 'get_input_schema' )
                        && ! empty( $ability->get_input_schema() );
                    $exec_args = $has_input_schema ? $arguments : null;

                    $perm = $ability->check_permissions( $exec_args );
                    if ( \is_wp_error( $perm ) ) {
                        throw new \RuntimeException( 'Permission denied: ' . $perm->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                    }
                    if ( false === $perm ) {
                        throw new \RuntimeException( "Permission denied for ability: {$captured_slug}" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                    }

                    $result = $ability->execute( $exec_args );
                    if ( \is_wp_error( $result ) ) {
                        throw new \RuntimeException( $result->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                    }
                    return $result;
                },
            ) );

            $registry->register( $tool );
        }
    }

    
    
    

    











    public static function normalize_identifier( $value ) {
        $normalized = strtolower( (string) $value );
        $normalized = preg_replace( '/[^a-z0-9]+/', '_', $normalized );
        return trim( $normalized, '_' );
    }
}
