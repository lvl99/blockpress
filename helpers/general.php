<?php
/**
 * General helper functions
 */

namespace LVL99\ACFPageBuilder;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Sanitise a string which is used for key, name, etc.
 *
 * @param string $key
 * @return mixed
 */
function sanitise_key ( $key )
{
  return preg_replace( '/[^a-zA-Z0-9_]+/', '', strtolower( $key ) );
}

/**
 * Encode a key into a reproduceable unique short code
 *
 * @param string $key
 * @return mixed
 */
function encode_key ( $key )
{
  return substr( sanitise_key( strrev( base64_encode( $key ) ) ), 0, 32 );
}

/**
 * Return the full class with namespace
 *
 * @param $class
 * @return string
 */
function get_namespace_class ( $class )
{
  return '\\' . __NAMESPACE__ . '\\' . $class;
}

/**
 * Load the blocks as sub_fields/layouts
 *
 * @param string $type
 * @param array $acf_config
 * @param array $options
 * @return mixed
 */
function load_blocks_into_acf_field ( $type, $acf_config, $options = [] )
{
  // Special stuff
  if ( ! empty( $options ) )
  {
    // Load in the layout blocks and generate ACF config for the layouts/sub_fields values
    if ( array_key_exists( 'blocks', $options ) )
    {
      $_blocks = $options['blocks'];
      $_layout_blocks = [];
      foreach ( $_blocks as $block_name => $block_instance )
      {
        $_layout_block_key = ( ! empty( $acf_config['key'] ) ? $acf_config['key'] : ! empty( $acf_config['name'] ) ? $acf_config['name'] : $block_name );

        // Block instances already passed
        if ( is_array( $block_instance ) && array_key_exists( 'instance', $block_instance ) )
        {
          $_layout_blocks[] = $block_instance['instance']->generate_acf( $_layout_block_key );

          // Fetch the global block instances
        } else {
          $_layout_blocks[] = acf_page_builder()->get_block_instance( $block_name )->generate_acf( $_layout_block_key );
        }
      }

      switch ( $type )
      {
        case 'flexible_content':
          $_layouts = [];

          // Layouts need to be an associative array with layout block's key as the array key
          foreach ( $_layout_blocks as $layout_block )
          {
            $_layouts[ $layout_block['key'] ] = $layout_block;
          }

          $acf_config['layouts'] = $_layouts;
          break;

        case 'group':
        case 'repeater':
          $acf_config['sub_fields'] = $_layout_blocks;
          break;
      }
    }
  }

  return $acf_config;
}
