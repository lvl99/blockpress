<?php
/**
 * Content Block
 *
 * Represents a block of flexible content
 */

namespace LVL99\ACFPageBuilder;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class BlockContent extends Block {
  public $name = 'content';
  public $label = 'Content';
  public $description = 'A block of content';
  public $display = 'block';

  // We can specify what layout blocks can be placed within this block by putting their string names here
  // By default any `flexible_content` field will support all blocks defined in the Page Builder
  public $blocks = [ 'text', 'image', 'carousel' ];

  public function __construct ( $key = '' )
  {
    parent::__construct( $key );

    // Set blocks as sub_fields
    $this->initialise_blocks( $this->blocks );
  }

  public function generate_acf ( $key = '' )
  {
    $acf = parent::generate_acf( $key );

    $acf_content = generate_acf_field_flexible_content( [
      'key' => $this->get_namespaced_key( $key ),
      'name' => $this->get_prop( 'name' ),
      'label' => $this->get_prop( 'label' ),
      'type' => 'flexible_content',
      'button_label' => 'Add Layout Block',
      'layout' => $this->get_prop( 'display' ),
      'layouts' => [],
    ], [
      'blocks' => $this->get_blocks(), // Attaching the blocks will load the layout blocks within this flexible_content
    ] );

    // Add the flexible_content field to the start
    array_unshift( $acf['sub_fields'], $acf_content );

    return $acf;
  }
}
