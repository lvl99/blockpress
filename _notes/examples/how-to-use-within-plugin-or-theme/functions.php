<?php

/**
 * Let's imagine this is your functions.php.
 *
 * Let's imagine aside from the base layouts/blocks that you want to add some custom layouts and blocks for your theme
 * to support.
 *
 * There are a few filters which are supported to allow adding (or changing) the layouts/blocks which Page Builder
 * makes available in the backend.
 *
 * For the moment this is all code-based (i.e. no data saved in the database). Hopefully in the future there might be
 * some way to allow for database-driven layouts/blocks, and that should hopefully be around v1.0.0.
 */

//
// Namespaces are good for organisation!
//
namespace My_Cool_Theme;

//
// To promote security and best practices, let's ensure that the PHP doesn't execute if WordPress has not essentially
// been defined.
//
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//
// Convenience shorthand to reference the theme's path.
//
define( 'MY_COOL_THEME_PATH', __DIR__ );

//
// Here we specify a function that will add extra layouts to load into the Page Builder.
//
// Loading in layouts is important as the Page Builder will dynamically generate the ACF configuration to view the
// layout as a field group with a single flexible content field to allow the user to add content via the blocks.
//
// For the custom layouts we'll store these within our theme directory structure.
//
function load_acfpb_layouts ( $load_layouts )
{
  //
  // Each layout will need a special name. This name should be the same value specified in the layout's class too.
  // Theoretically you could overwrite any of the base layouts by giving your custom layout the same name.
  // In this example we're just adding a custom layout, no overwriting.
  //
  $load_layouts['mct_portfolio'] = [
    //
    // Each layout needs a `class` and a `path`. The `class` is what's used when creating the layout's instance within
    // the Page Builder.
    //
    'class' => 'My_Cool_Theme\\LayoutPortfolio',

    //
    // The `path` points to the PHP file that contains the custom layout's class. This is for dynamic requiring of the
    // necessary layout files.
    //
    'path' => MY_COOL_THEME_PATH . '/acfpb/layouts/class.layout.portfolio.php',
  ];

  return $load_layouts;
}

//
// Filters are essentially namespaced and describe the path of the function which fires the filter.
// By default the filter runs at priority 10 and requires the $load_layouts array to be passed.
//
add_filter( 'LVL99\ACFPageBuilder\Builder\load_layouts', __NAMESPACE__ . '\\load_acfpb_layouts', 11, 1 );

//
// Loading the custom blocks is practically the same as the custom layouts.
//
// If you have a custom block which relies on other blocks (core or custom) then you'll need to load them later. Use a
// priority of 21 or more.
//
// In this example the media block is simple, so it can be loaded after the core blocks (priority 10 or more)
//
// There will definitely be changes to this API in the near future...
//
function load_acfpb_blocks ( $load_blocks )
{
  $load_blocks['mct_media'] = [
    'class' => 'My_Cool_Theme\\BlockMedia',
    'path' => MY_COOL_THEME_PATH . '/acfpb/layouts/class.block.media.php',
  ];

  return $load_blocks;
}
add_filter( 'LVL99\ACFPageBuilder\Builder\load_blocks', __NAMESPACE__ . '\\load_acfpb_blocks', 11, 1 );

//
// That's it!
//
