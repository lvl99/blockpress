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
