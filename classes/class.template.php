<?php
/**
 * BlockPress - Template
 */

namespace LVL99\BlockPress;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class Template extends Entity {
  public $layout = '';
  public $blocks = [];
  public $data = [];

  public function __constructor ( $key = '', $options = [] )
  {
    // @TODO
  }
}
