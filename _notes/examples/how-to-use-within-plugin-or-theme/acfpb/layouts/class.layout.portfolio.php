<?php

/**
 * Layouts extend the `LVL99\ACFPageBuilder\Layout` class (located at `core/class.layout.php`)
 *
 * Layouts describe a single flexible content ACF field.
 *
 * Layouts can have one, some, or all blocks assigned to them. This means that you, the developer, can control what
 * blocks are visible in the custom layouts. This is good for when you're defining single purpose layouts that only
 * rely on a few necessary blocks.
 */

//
// Namespaces are good for organisation!
//
namespace My_Cool_Theme;

//
// Since the LVL99 ACF Page Builder has been nicely namespaced, we can use and set a shorthand alias here.
//
use LVL99\ACFPageBuilder as ACFPageBuilder;

//
// WordPress security best practices
//
if ( ! defined( 'ABSPATH' ) ) exit;

//
// When creating a custom layout, you'll need to extend the Page Builder's core Layout class.
//
// You can always reference the `core/class.layout.php` to see what specific information layouts support.
//
class LayoutPortfolio extends ACFPageBuilder\Layout {
  //
  // For the most part Layout classes are very basic.
  //
  // For starters, define a human-machine-readable name. This name will be used in array keys and within templates.
  // Think of it as a `slug`, like setting a post's URL slug, where characters are lower-case and no special characters
  // except for underscores and hyphens are allowed.
  //
  // This layout `slug` should be exactly the same as the layout's array key when loading it into Page Builder.
  //
  public $name = 'mct_portfolio';

  //
  // The following properties are optional.
  //
  // You can set a human-readable label for the layout.
  //
  public $label = 'Portfolio';

  //
  // A description to describe what the layout is for.
  //
  public $description = 'This is a layout for showcasing portfolio projects';

  //
  // You can also give extra instructions to the user for configuring this particular layout.
  //
  // I won't here, but hopefully you get the idea of how it can be utilised.
  //
  public $instructions = '';

  //
  // We can also set the names of the blocks that this layout can support.
  //
  // We can control what types of blocks users can create in this layout. This allows some kind of finite control
  // for pages/posts that may only need or require a few block types.
  //
  // If this is empty, then it will support all blocks that have been loaded into the Page Builder. Since the Page
  // Builder generates the ACF configuration on the fly, it is sensible to limit what blocks are supported for
  // speed and optimisation's sake.
  //
  // In this example we're only going to allow our custom media block and a basic text block.
  //
  public $blocks = [ 'mct_media', 'text' ];
}

//
// That's it!
//
