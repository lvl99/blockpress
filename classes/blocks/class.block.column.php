<?php
/**
 * Column Block
 *
 * Represents a single column which each hold other types of flexible content
 */

namespace LVL99\BlockPress;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class BlockColumn extends Block {
  public $name = 'column';
  public $label = 'Column';
  public $description = 'A column for the columns block';
  public $display = 'block';

  // We can specify what layout blocks can be placed within this block by putting their string names here
  // By default any `flexible_content` field will support all layouts defined in BlockPress
  // Blocks which nest other blocks should be marked as the "special" type
  public $type = 'special';
  public $blocks = [ '$$:__not=column,columns' ]; // See `special-sauce.php:filter_blocks`

  // Restrict this block to only be visible within these layouts/blocks
  public $rules = [
    'block' => 'columns',
  ];

  public $content = [
    // This is actually a dummy field that will be replaced by the real field in the `generate_acf` method below
    [
      'name' => 'blocks',
      'label' => 'Blocks',
      'type' => 'flexible_content',
      'layout' => 'block',
      'button_label' => 'Add...',
      'sub_fields' => [],
      '_wpml' => 'copy',
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

  public function generate_acf( $key = '', $options = [] )
  {
    $_options = array_merge( $options, [
      'key' => $key,
    ] );
    $acf = parent::generate_acf( $key, $_options );

    // Generate the sub_fields that represent the repeatable content for the field above
    $acf_content = $this->generate_field( 'content', 'flexible_content', [
      'key' => $key. ':' . $acf['sub_fields'][0]['name'],
      'name' => $acf['sub_fields'][0]['name'],
      'label' => $acf['sub_fields'][0]['label'],
      'layout' => $acf['sub_fields'][0]['layout'],
      'button_label' => $acf['sub_fields'][0]['button_label'],
      'layouts' => [],
      '_wpml' => 'copy',
    ], [
      'overwrite_field' => $acf['sub_fields'][0],
      'builder' => $_options['builder'],
      'layout' => $_options['layout'],
      'block' => $this,
      'blocks' => $this->get_blocks(), // Attaching the blocks will load the layout blocks within this flexible_content
    ] );

    // Ensure the generated flexible content field is overwritten in the ACF config
    $acf['sub_fields'][0] = $acf_content;

    return $acf;
  }
}
