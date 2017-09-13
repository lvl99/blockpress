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
 * Encode a key into a reproduceable unique ID
 *
 * @param string $key
 * @return string
 */
function encode_key ( $key )
{
  return substr( sanitise_key( md5( $key ) ), 0, 254 );
}

/**
 * Load the blocks as sub_fields/layouts
 *
 * @param string $type
 * @param array $acf_config
 * @param array $options
 * @return mixed
 * @throws \Exception
 */
function load_blocks_into_acf_field ( $type, $acf_config, $options = [] )
{
  $allowed_types = [ 'flexible_content', 'group', 'repeater' ];

  // Special stuff
  if ( ! empty( $options ) && in_array( $type, $allowed_types ) )
  {
    $_options = wp_parse_args( $options, [
      'builder' => lvl99_acf_page_builder(),
    ] );

    // A layout must be specified
    if ( ! array_key_exists( 'layout', $_options ) )
    {
      throw new \Exception( 'LVL99 ACF Page Builder: no layout was specified' );
    }

    // Get the layout instance
    if ( is_string( $_options['layout'] ) && ! empty( $_options['layout'] ) )
    {
      $_options['layout'] = $_options['builder']->get_layout_instance( $_options['layout'] );
    }
    // Error if empty
    else if ( empty( $_options['layout'] ) )
    {
      throw new \Exception( 'LVL99 ACF Page Builder: no layout name was specified' );
    }

    // Get the block instance
    // if ( array_key_exists( 'block', $_options ) && is_string( $_options['block'] ) && ! empty( $_options['block'] ) )
    // {
    //   $_options['block'] = $_options['builder']->get_block_instance( $_options['block'] );
    // }

    // Load in the layout blocks and generate ACF config for the layouts/sub_fields values
    if ( array_key_exists( 'blocks', $_options ) )
    {
      $_blocks = $_options['blocks'];
      $_layout_blocks = [];

      foreach ( $_blocks as $block_name => $block_data )
      {
        $generate_options = [
          'nested_key' => $_options['layout']->get_prop( 'name' ) . '_' . $acf_config['key'] . '_' . $block_name,
          'parent' => $acf_config['key'],
          'builder' => $_options['builder'],
          'layout' => $_options['layout'],
        ];

        if ( array_key_exists( 'block', $_options ) )
        {
          $generate_options['block'] = $_options['block'];
        }

        $generated_block = $_options['builder']->generate_block( $block_name, $_options['layout']->get_prop( 'name' ), $generate_options );
        $_layout_blocks[] = $generated_block;
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
