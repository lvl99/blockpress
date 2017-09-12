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
  public $content = [
    // This is actually a dummy field that will be replaced by the real field in the `generate_acf` method below
    [
      'name' => 'columns',
      'label' => 'Columns',
      'type' => 'repeater',
      'layout' => 'block',
      'button_label' => 'Add Column',
      'sub_fields' => [],
    ],
  ];

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

  // Custom implementation of the generate_acf since this block has fields which need extra massaging
  public function generate_acf ( $key = '', $options = [] )
  {
    $settings = array_merge( $options, [
      'key' => $key,
      'layout' => '',
    ] );
    $acf = parent::generate_acf( $key, $options );

    // Create the real repeater field
    // We do it here because the flexible content field that we want to repeat relies this field's key
    $acf_repeater = generate_acf_field_repeater( [
      'key' => $this->get_namespaced_key( $key ),
      'name' => $this->get_prop( 'name' ),
      'label' => $this->get_prop( 'label' ),
      'type' => 'repeater',
      'layout' => $this->get_prop( 'display' ),
      'button_label' => 'Add Column',
      'sub_fields' => [],
    ]);

    // Generate the sub_fields that represent the repeatable content for the field above
    $acf_column = generate_acf_field_flexible_content( [
      'key' => $this->get_namespaced_key( $key ) . '_column',
      'name' => 'content',
      'label' => 'Content',
      'layout' => 'block',
      'button_label' => 'Add Layout Block',
      'layouts' => [],
    ], [
      'layout' => $settings['layout'],
      'blocks' => $this->get_blocks(), // Attaching the blocks will load the layout blocks within this flexible_content
    ] );

    // Attach the layout blocks to the repeater's sub_fields
    $acf_repeater['sub_fields'][] = $acf_column;

    // Ensure this field is the first in the block's sub_fields
    $acf['sub_fields'][0] = $acf_repeater;

    return $acf;
  }
}
