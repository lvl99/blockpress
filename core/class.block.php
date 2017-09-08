<?php
/**
 * ACF Page Builder - Block
 *
 * Represents a single layout item within a "Flexible Content" field
 */

namespace LVL99\ACFPageBuilder;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class Block extends Entity {
  /**
   * The name of the block
   *
   * @var string
   */
  public $name = '';

  /**
   * The display label of the block
   *
   * @var string
   */
  public $label = '';

  /**
   * The description of the block
   *
   * @var string
   */
  public $description = '';

  /**
   * The ACF display type ('block', 'table' or 'row')
   *
   * @var string
   */
  public $display = 'block';

  /**
   * The fields that the block supports
   *
   * @var array
   * @protected
   */
  public $fields = [

  ];

  /**
   * The fields to customise the block's presentation
   *
   * @var array
   * @protected
   */
  public $customise = [

  ];

  /**
   * The fields to configure the block's behaviour
   *
   * @var array
   * @protected
   */
  public $configure = [

  ];

  /**
   * The core configuration fields for the block (behaviour or the HTML element itself)
   *
   * @var array
   * @protected
   */
  public $_configure = [
    [
      'label' => 'Element ID',
      'name' => 'element_id',
      'type' => 'text',
      'instructions' => 'Set a specific ID for this block\'s element',
    ],
    [
      'label' => 'Element Class',
      'name' => 'element_class',
      'type' => 'text',
      'instructions' => 'Set additional CSS class names to this block\'s element',
    ],
  ];

  /**
   * The ACF config for the block's sub_fields
   *
   * @var array
   */
  public $acf_sub_fields = [];

  /**
   * Class Block
   *
   * @constructor
   * @param string $key
   */
  public function __construct ( $key = '' )
  {
    $this->set_key( $key );
    $this->initialise_blocks();
    $this->initialise_fields( [ 'fields', 'customise', 'configure', '_configure' ] );
  }

  /**
   * Generate the code to use within ACF
   *
   * @param string $key
   * @protected
   * @returns array
   */
  public function generate_acf ( $key = '' )
  {
    $_key = $this->get_key();

    // If a namespace key was given, ensure it's at the start
    if ( ! empty( $key ) )
    {
      $_key = sanitise_key( $key ) . '_' . $_key;
    }

    // Generate all the fields for this block
    $_sub_fields = [];

    // Process all the basic fields for this block
    if ( ! empty( $this->get_prop( 'fields' ) ) )
    {
      foreach ( $this->get_prop( 'fields' ) as $field )
      {
        $_sub_fields[] = generate_acf_field( $field['type'], array_merge( $field, [
          'key' => $_key . '_' . $field['name'],
        ] ) );
      }
    }

    // Process all the customisation fields
    if ( ! empty( $this->get_prop( 'customise' ) ) )
    {
      $_sub_fields[] = generate_acf_field_tab( [
        'label' => 'Customise',
      ] );

      foreach ( $this->get_prop( 'customise' ) as $field )
      {
        $_sub_fields[] = generate_acf_field( $field['type'], array_merge( $field, [
          'key' => $_key . '_' . $field['name'],
        ] ) );
      }
    }

    // Process all the configuration fields
    if ( ! empty( $this->get_prop( 'configure' ) ) || ! empty( $this->get_prop( '_configure' ) ) )
    {
      // Add the configure tab
      $_sub_fields[] = generate_acf_field_tab( [
        'label' => 'Configure',
      ] );

      $_configure = array_merge( $this->get_prop( 'configure' ), $this->get_prop( '_configure' ) );

      // Process the fields
      foreach ( $_configure as $field )
      {
        $_sub_fields[] = generate_acf_field( $field['type'], array_merge( $field, [
          'key' => $_key . '_' . $field['name'],
        ] ) );
      }
    }

    // Save the sub fields to the instance
    $this->acf_sub_fields = $_sub_fields;

    // Generate the basic config for the block
    $_acf = generate_acf_flexible_content_layout( [
      'key' => $_key,
      'name' => $this->get_prop( 'name' ),
      'label' => $this->get_prop( 'label' ),
      'sub_fields' => $_sub_fields,
    ] );

    return $_acf;
  }
}
