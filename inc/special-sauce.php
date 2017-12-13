<?php
/**
 * $pecial $auce functions
 *
 * These functions can be dynamically called to generate special objects, create/modify objects with a particular
 * context, etc.
 */

namespace LVL99\BlockPress;

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
  if ( isset( $special_sauce['func'] ) /* @perf array_key_exists( 'func', $special_sauce ) */ && function_exists( $special_sauce['func'] ) )
  {
    if ( isset( $special_sauce['args'] ) /* @perf array_key_exists( 'args', $special_sauce ) */ )
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

    if ( isset( $block_data['instance'] ) /* @perf array_key_exists( 'instance', $block_data ) */ )
    {
      $block_instance = $block_data['instance'];
    }

    foreach ( $filters as $filter_name => $filter_value )
    {
      // Filter by a block property value
      if ( property_exists( $block_instance, $filter_name ) )
      {
        if ( $block_instance->get_prop( $filter_name ) === $filter_value )
        {
          $output[ $block_name ] = $block_instance;
          break;
        }
      }
      else
      {
        $include_block = TRUE;

        // Special rules to filter by
        switch ( strtolower( $filter_name ) )
        {
          case '__exclude':
          case '__not':
            // Exclude by name
            if ( is_string( $filter_value ) )
            {
              if ( strpos( $filter_value, ',' ) >= 0 )
              {
                $filter_value = explode( ',', $filter_value );
              }
              else
              {
                $filter_value = [ $filter_value ];
              }
            }

            // Exclude the block
            if ( in_array( $block_name, $filter_value ) )
            {
              $include_block = FALSE;
            }
            break;
        }

        if ( $include_block )
        {
          $output[ $block_name ] = $block_instance;
        }
      }
    }
  }

  return $output;
}
