<?php
/**
 * Text Block
 *
 * Represents a block of text
 */

namespace LVL99\ACFPageBuilder;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class BlockText extends Block {
  public $name = 'text';
  public $label = 'Text';
  public $description = 'A text block';
  public $display = 'block';
  public $content = [
    [
      'name' => 'text',
      'label' => 'Text',
      'type' => 'wysiwyg',
      'tabs' => 'all',
      'toolbar' => 'full',
      'media_upload' => 1,
    ],
  ];
}
