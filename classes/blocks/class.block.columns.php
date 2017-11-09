<?php
/**
 * Columns Block
 *
 * Represents a number of columns which each hold other types of flexible content
 */

namespace LVL99\BlockPress;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class BlockColumns extends Block {
  public $name = 'columns';
  public $label = 'Columns';
  public $description = 'A row of column blocks';
  public $display = 'block';

  // We can specify what layout blocks can be placed within this block by putting their string names here
  // By default any `flexible_content` field will support all layouts defined in BlockPress
  // Blocks which nest other blocks should be marked as the "special" type
  public $type = 'special';
  public $blocks = [ 'column' ];

  public $content = [
    // Dummy field to be overwritten in the generate_acf function
    [
      'type' => 'flexible_content',
      'name' => 'columns',
      'label' => 'Columns',
      'layout' => 'block',
      'button_label' => 'Add...',
      'layouts' => [],
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

  // Here we overwrite the dummy fields with ones that are tied to the generated ACF config
  public function generate_acf ( $key = '', $options = [] )
  {
    $_options = array_merge( $options, [
      'key' => $key,
    ] );
    $acf = parent::generate_acf( $key, $_options );

    // Here we need to generate a new flexible content field that will have the block's nested blocks loaded in
    // as layouts. This will overwrite our dummy field specified in the `content` array in the class definition.
    $acf_column = $this->generate_field( 'content', 'flexible_content', [
      'key' => $key . ':' . $acf['sub_fields'][0]['name'],
      'name' => $acf['sub_fields'][0]['name'],
      'label' => $acf['sub_fields'][0]['label'],
      'layout' => $acf['sub_fields'][0]['layout'],
      'button_label' => $acf['sub_fields'][0]['button_label'],
      'layouts' => [],
      '_wpml' => 'copy',
    ], [
      'overwrite_field' => $acf['sub_fields'][0]['key'],
      'builder' => $_options[ 'builder' ],
      'layout' => $_options['layout'],
      'block' => $this,
      'blocks' => $this->get_blocks(),
    ] );

    // Ensure the newly generated flexible content field is overwritten in the ACF config
    $acf['sub_fields'][0] = $acf_column;

    return $acf;
  }
}
