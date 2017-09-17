<?php
/**
 * $pecial $auce functions
 *
 * These functions can be dynamically called to generate special objects, create/modify objects with a particular
 * context, etc.
 */

namespace LVL99\ACFPageBuilder;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Apply $pecial $auce to a context
 *
 * @param array $special_sauce The array which details the $pecial $auce operation
 * @param mixed $context
 * @returns mixed
 */
function apply_special_sauce ( $special_sauce, $context = NULL )
{
  if ( array_key_exists( 'func', $special_sauce ) && function_exists( $special_sauce['func'] ) )
  {
    if ( array_key_exists( 'args', $special_sauce ) )
    {
      return call_user_func_array( $special_sauce['func'], $special_sauce['args'] );
    } else {
      return $special_sauce['func']();
    }
  }
}

/**
 * Filter an array of block instances based on the filters
 *
 * @param array $blocks
 * @param array $filters
 * @returns array
 */
function filter_blocks ( $blocks, $filters )
{
  $output = [];
  $filters = wp_parse_args( $filters );

  foreach ( $blocks as $block_name => $block_data )
  {
    $block_instance = $block_data;

    if ( array_key_exists( 'instance', $block_data ) )
    {
      $block_instance = $block_data['instance'];
    }

    foreach ( $filters as $filter_name => $filter_value )
    {
      if ( $block_instance->get_prop( $filter_name ) === $filter_value )
      {
        $output[ $block_name ] = $block_instance;
        break;
      }
    }
  }

  return $output;
}
