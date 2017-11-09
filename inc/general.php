<?php
/**
 * General helper functions
 */

namespace LVL99\BlockPress;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Get a WP post/page
 *
 * @param int
 * @returns \WP_Post
 */
function get_wp_post ( $post_id = NULL )
{
  global $post;

  if ( ! empty( $post_id ) )
  {
    // Ensure if numeric that it is correctly cast
    if ( is_numeric( $post_id ) )
    {
      $post_id = intval( $post_id );
    }

    // Already WP_Post
    if ( is_a( $post_id, 'WP_Post' ) )
    {
      return $post_id;
    }
    // Get post by slug
    else if ( is_string( $post_id ) ) {
      return get_page_by_path( $post_id, OBJECT, blockpress()->get_setting( 'post_types' ) );
    }
    // Get post by ID
    else
    {
      return get_post( $post_id );
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
      'builder' => blockpress(),
    ] );

    // A layout must be specified
    if ( ! array_key_exists( 'layout', $_options ) )
    {
      throw new \Exception( 'LVL99 BlockPress: no layout was specified' );
    }

    // Get the layout instance
    if ( is_string( $_options['layout'] ) && ! empty( $_options['layout'] ) )
    {
      $_options['layout_name'] = $_options['layout'];
      $_options['layout'] = $_options['builder']->get_layout_instance( $_options['layout'] );
    }
    // Error if empty
    else if ( empty( $_options['layout'] ) )
    {
      throw new \Exception( 'LVL99 BlockPress: no layout name was specified' );
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
        // Ensure we have the correct builder block instance and not something else
        $block_instance = $_options['builder']->get_block_instance( $block_name );

        // Check rules with this block
        if ( array_key_exists( 'block', $_options ) )
        {
          // Skip any block that is not compatible with this one
          if ( ! $block_instance->is_compatible_with( $_options['block'] ) )
          {
            continue;
          }
        }
        // Check rules with this layout
        else
        {
          // Skip any block that is not compatible with this layout
          if ( ! $block_instance->is_compatible_with( $_options['layout'] ) )
          {
            continue;
          }
        }

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

/**
 * Get the post's field name from an ACF key.
 *
 * Kinda weird that ACF doesn't support this by default (or at least from what I've found so far)
 *
 * @param int|string|\WP_Post
 * @param string $key
 * @returns string
 */
function get_post_acf_field_name_from_key ( $post = NULL, $key )
{
  global $wpdb;

  $post = get_wp_post( $post );
  $acf_field_name = $wpdb->get_var( $wpdb->prepare( "SELECT meta_key FROM `$wpdb->postmeta` WHERE post_id = %d AND meta_value = %s LIMIT 1", $post->ID, $key ) );
  if ( is_string( $acf_field_name ) )
  {
    return preg_replace( '/^_/', '', $acf_field_name );
  }

  return $acf_field_name;
}

/**
 * Checks the ACF config and determines if the field's value requires extra formatting
 *
 * @param mixed $acf_field_key
 * @param mixed $acf_field_value
 * @param array $acf
 * @param int|string|\WP_Post
 * @return mixed
 */
function check_acf_field_to_format_value ( $acf_field_key, $acf_field_value, $acf, $post = NULL )
{
  // Needs to return formatted value or has special return format that value currently isn't
  $requires_return_acf_attachments = in_array( $acf['type'], [ 'file', 'image', 'gallery' ] );
  $requires_return_post_objects = in_array( $acf['type'], [ 'post_object', 'page_link', 'relationship' ] );
  $requires_special_return_format = array_key_exists( 'return_format', $acf ) && ( $acf['return_format'] === 'array' || $acf['return_format'] === 'object' );
  if ( ! empty( $acf_field_value ) && $requires_return_acf_attachments || $requires_return_post_objects || $requires_special_return_format )
  {
    // @NOTE there's an minor bug regarding getting a flexible content layout's field value since the field key is the
    //       same for each one, so I can't reliably get the formatted value for the specific flexible content layout
    //
    //       If I can figure out how to reliably do the commented below in the context of a flexible content layout row,
    //       then I wouldn't need all the other stuff, and it would be great to piggyback on ACF get/format value
    //       filters
    //
    // $acf_field_name = get_post_acf_field_name_from_key( $post, $acf_field_key );
    // $acf_field_value = get_field( $acf_field_name, $post, TRUE );

    switch ( $acf['type'] )
    {
      case 'file':
      case 'image':
        $acf_field_value = acf_get_attachment( $acf_field_value );
        break;

      case 'gallery':
        $gallery_images = [];
        foreach( $acf_field_value as $gallery_item_id )
        {
          if ( ! empty( $gallery_item_id ) )
          {
            $gallery_images[] = acf_get_attachment( $gallery_item_id );
          }
        }
        $acf_field_value = $gallery_images;
        break;

      case 'post_object':
      case 'page_link':
      case 'relationship':
        // These fields support multiple values, i.e. return an array of objects
        if ( is_array( $acf_field_value ) )
        {
          $multiple_values = [];
          foreach ( $acf_field_value as $item_id )
          {
            if ( ! empty( $item_id ) )
            {
              // @TODO check that these fields are always referring to posts
              //       If can refer to taxonomies/terms/other WP entities, then figure it out
              $item_entity = get_wp_post( $item_id );

              // Cast to array
              if ( $requires_special_return_format && $acf['return_format'] === 'array' )
              {
                $multiple_values[] = (array) $item_entity;
              }
              // Otherwise use default object
              else
              {
                $multiple_values[] = $item_entity;
              }
            }
          }
          $acf_field_value = $multiple_values;
        }
        // Return a singular value
        else
        {
          $acf_field_value = get_wp_post( $acf_field_value );
          if ( $requires_special_return_format && $acf['return_format'] === 'array' )
          {
            $acf_field_value = (array) $acf_field_value;
          }
        }
        break;
    }
  }

  // Turn true_false values into booleans
  if ( $acf['type'] === 'true_false' )
  {
    $acf_field_value = boolval( $acf_field_value );
  }
  // Ensure number
  else if ( $acf['type'] === 'number' )
  {
    $acf_field_value = floatval( $acf_field_value );
  }

  return $acf_field_value;
}

/**
 * Clean any excess whitespace detected within. Essentially folds all whitespace down to a single space
 *
 * @param string $input
 * @returns string
 */
function clean_excess_whitespace ( $input )
{
  return trim( preg_replace( '/\s+/', ' ', $input ) );
}
