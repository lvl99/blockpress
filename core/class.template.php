<?php
/**
 * ACF Page Builder - Template
 */

namespace LVL99\ACFPageBuilder;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class Template extends Entity {
  public $blocks = [];

  public $layouts = [];

  public function __constructor ( $options = [] )
  {

  }
}
