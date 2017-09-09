<?php
/**
 * Page Layout
 *
 * Represents the layout that a page can have
 *
 * This is technically an ACF Field Group that supports a single flexible_content field that then loads in the
 * necessary blocks defined in ACF Page Builder.
 */

namespace LVL99\ACFPageBuilder;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class LayoutPage extends Layout {
  public $name = 'page';
  public $label = 'Page';
}
