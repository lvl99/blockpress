<?php
/**
 * General helper functions
 */

namespace LVL99\ACFPageBuilder;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Get a WP post/page
 *
 * @param int
 * @returns \WP_Post
 */
function get_post ( $post_id = NULL )
{
  global $post;

  if ( ! empty( $post_id ) )
  {
    // Already WP_Post
    if ( is_a( $post_id, 'WP_Post' ) )
    {
      return $post_id;
    }
    // Get post by slug
    else if ( is_string( $post_id ) ) {
      return \get_page_by_path( $post_id, OBJECT, lvl99_acf_page_builder()->get_setting( 'post_types' ) );
    }
    // Get post by ID
    else
    {
      return \get_post( $post_id );
    }
  }
  else
  {
    return $post;
  }
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
 * Load the blocks as sub_fields/layouts
 *
 * @param string $type
 * @param array $acf_config
 * @param array $options
 * @return mixed
 */
function load_blocks_into_acf_field ( $type, $acf_config, $options = [] )
{
  $allowed_types = [ 'flexible_content', 'group', 'repeater' ];

  // Special stuff
  if ( ! empty( $options ) && in_array( $type, $allowed_types ) )
  {
    // Get the layout instance
    if ( array_key_exists( 'layout', $options ) && is_string( $options['layout'] ) && ! empty( $options['layout'] ) )
    {
      $options['layout'] = lvl99_acf_page_builder()->get_layout_instance( $options['layout'] );
    }

    // Load in the layout blocks and generate ACF config for the layouts/sub_fields values
    if ( array_key_exists( 'blocks', $options ) )
    {
      $_blocks = $options['blocks'];
      $_layout_blocks = [];

      foreach ( $_blocks as $block_name => $block_data )
      {
        $_layout_block_key = ( ! empty( $acf_config['key'] ) ? $acf_config['key'] : ! empty( $acf_config['name'] ) ? $acf_config['name'] : $block_name );
        $block_instance = NULL;
        $generated_block = [];

        // Block instances already passed
        if ( is_array( $block_data ) && array_key_exists( 'instance', $block_data ) )
        {
          $block_instance = $block_data['instance'];

        // Fetch the block instance from the global builder
        } else {
          $block_instance = lvl99_acf_page_builder()->get_block_instance( $block_name );
        }

        // Generate the block config and register into the builder's map
        if ( $block_instance )
        {
          $generated_block = $block_instance->generate_acf( $_layout_block_key );
          $register_options = array_merge( $options, [
            // Ensure each nested block has a parent listed
            'parent' => $acf_config['key'],
          ] );
          lvl99_acf_page_builder()->register_block_in_map( $block_instance, $generated_block, $register_options );
          $_layout_blocks[] = $generated_block;
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
