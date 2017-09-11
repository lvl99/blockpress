<?php
/**
 * Columns Block
 *
 * Represents a number of columns which each hold other types of flexible content
 */

namespace LVL99\ACFPageBuilder;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class BlockColumns extends Block {
  public $name = 'columns';
  public $label = 'Columns';
  public $description = 'A row of columns to contain content';
  public $display = 'block';
  public $customise = [
    [
      '$$' => [
        'func' => __NAMESPACE__ . '\\field_bg_color',
      ],
    ],
    [
      '$$' => [
        'func' => __NAMESPACE__ . '\\field_bg_image',
      ],
    ],
    [
      '$$' => [
        'func' => __NAMESPACE__ . '\\field_bg_position',
      ],
    ],
    [
      '$$' => [
        'func' => __NAMESPACE__ . '\\field_bg_size',
      ],
    ],
  ];

  // We can specify what layout blocks can be placed within this block by putting their string names here
  // By default any `flexible_content` field will support all layouts defined in the Page Builder
  public $blocks = [ 'text', 'image', 'carousel' ];

  public function generate_acf ( $key = '' )
  {
    $acf = parent::generate_acf( $key );

    $acf_repeater = generate_acf_field_repeater( [
      'key' => $this->get_namespaced_key( $key ),
      'name' => $this->get_prop( 'name' ),
      'label' => $this->get_prop( 'label' ),
      'type' => 'repeater',
      'layout' => $this->get_prop( 'display' ),
      'button_label' => 'Add Column',
      'sub_fields' => [],
    ]);

    $acf_column = generate_acf_field_flexible_content( [
      'key' => $this->get_namespaced_key( $key ) . '_column',
      'name' => 'content',
      'label' => 'Content',
      'layout' => 'block',
      'button_label' => 'Add Layout Block',
      'layouts' => [],
    ], [
      'blocks' => $this->get_blocks(), // Attaching the blocks will load the layout blocks within this flexible_content
    ] );

    // Attach the layout blocks to the repeater's sub_fields
    $acf_repeater['sub_fields'][] = $acf_column;

    // Ensure this field is the first in the block's sub_fields
    array_unshift( $acf['sub_fields'], $acf_repeater );

    return $acf;
  }
}
