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
   * The name of the block. This will be the block's slug in arrays and used when referring to the block in the
   * views and templates.
   *
   * @var string
   */
  public $name = '';

  /**
   * The type of the block
   *
   * There are two types: a basic block and a special block.
   *
   * Special blocks can nest other blocks within them. Special blocks also need to be loaded after the blocks they
   * nest. Very important (this API will probably be revised later).
   *
   * @var string
   */
  public $type = 'basic';

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
   * The fields to enter content that the block displays
   *
   * @var array
   */
  public $content = [];

  /**
   * The fields to further customise the block, or block's content
   *
   * @var array
   */
  public $customise = [];

  /**
   * The fields to configure the block, or its content's, behaviour
   *
   * @var array
   */
  public $configure = [];

  /**
   * The core configuration fields for the block (behaviour or the block's HTML element itself)
   *
   * @var array
   * @protected
   */
  protected $_configure = [];

  /**
   * A map of all the fields assign to this block
   *
   * @var array
   */
  protected $_fields = [
    'content' => [],
    'customise' => [],
    'configure' => [],
  ];

  /**
   * The index of all the generated blocks managed by this class
   *
   * @var array
   */
  protected $_index = [];

  /**
   * Class Block
   *
   * @constructor
   * @param string $key
   */
  public function __construct ( $key = '' )
  {
    $this->set_key( $key );
  }

  /**
   * Initialise any nested blocks and the block's fields
   */
  public function initialise ()
  {
    $this->initialise_blocks();
    $this->initialise_fields( [ 'content', 'customise', 'configure', '_configure' ] );
  }

  /**
   * Initialise the block's fields
   *
   * @param array $fields
   */
  public function initialise_fields( $fields = [] )
  {
    parent::initialise_fields( $fields );

    // Core _configure fields which all blocks should have
    $this->_configure = array_merge( $this->_configure, [
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
        'instructions' => 'Set additional CSS class names for this block\'s element',
      ],
      // @TODO may need to add more regarding responsive?
    ] );
  }

  /**
   * Go through and check if the field's key is registered in any of the instance's field groups
   *
   * @param string $field_key
   * @return string|bool
   */
  public function get_field_group ( $field_key )
  {
    foreach ( $this->_fields as $field_group_name => $field_group_items )
    {
      if ( array_key_exists( $field_key, $field_group_items ) )
      {
        return $field_group_name;
      }
    }

    return FALSE;
  }

  /**
   * Generate a field for use within this block
   *
   * @param string $field_group
   * @param string $type
   * @param array $acf_config
   * @param array $options
   * @returns array
   */
  protected function generate_field ( $field_group, $type, $acf_config, $options = [] )
  {
    $generation_key = $acf_config['key'];
    $generated_sub_field = generate_acf_field( $type, $acf_config, $options );

    $_options = wp_parse_args( $options, [
      'overwrite_field' => '',
    ] );

    if ( $_options['overwrite_field'] === $generated_sub_field )
    {
      $debug = [
        'generation_key' => $generation_key,
        'old_key' => $_options['overwrite_field'],
        'new_key' => $generated_sub_field['key'],
      ];
      $this->_fields[ $field_group ][ $generated_sub_field['key'] ] = $generation_key;
    }
    else
    {
      $this->_fields[ $field_group ][ $generated_sub_field['key'] ] = $generation_key;
    }

    return $generated_sub_field;
  }

  /**
   * Generate the code to use within ACF
   *
   * @param string $key
   * @param array $options
   * @returns array
   * @throws \Exception
   */
  public function generate_acf ( $key = '', $options = [] )
  {
    $_key = $this->get_key();

    // If a key was given, use it
    if ( ! empty( $key ) )
    {
      $_key = $key;
    }

    $_options = array_merge( $options, [
      'generation_key' => $_key,
      'block' => $this,
      // @NOTE Dunno if I should put `blocks` here
      // 'blocks' => $this->get_blocks(),
    ] );

    if ( empty( $_options['layout'] ) )
    {
      throw new \Exception( 'LVL99 ACF Page Builder: options `layout` value cannot be empty' );
    }

    // Generate all the fields for this block
    $_sub_fields = [];
    $field_options = [
      'generation_key' => $_key,
      'builder' => $_options['builder'],
      'layout' => $_options['layout'],
      'block' => $_options['block']
    ];

    // Process all the basic fields for this block
    if ( ! empty( $this->get_prop( 'content' ) ) )
    {
      foreach ( $this->get_prop( 'content' ) as $field )
      {
        $_field_options = array_merge( $field_options, [
          'generation_key' => $_key . ':' . $field['name'],
        ] );

        $_sub_fields[] = $this->generate_field( 'content', $field['type'], array_merge( $field, [
          'key' => $_field_options['generation_key'],
        ] ), $_field_options );
      }
    }

    // Process all the customisation fields
    if ( ! empty( $this->get_prop( 'customise' ) ) )
    {
      $_sub_fields[] = generate_acf_field_tab( [
        'label' => 'Customise',
        'name' => 'acfpb_block_customise',
      ] );

      foreach ( $this->get_prop( 'customise' ) as $field )
      {
        $_field_options = array_merge( $field_options, [
          'generation_key' => $_key . ':' . $field['name'],
        ] );

        $_sub_fields[] = $this->generate_field( 'customise', $field['type'], array_merge( $field, [
          'key' => $_field_options['generation_key'],
        ] ), $_field_options );
      }
    }

    // Process all the configuration fields
    if ( ! empty( $this->get_prop( 'configure' ) ) || ! empty( $this->get_prop( '_configure' ) ) )
    {
      // Add the configure tab
      $_sub_fields[] = generate_acf_field_tab( [
        'label' => 'Configure',
        'name' => 'acfpb_block_configure',
      ] );

      $_configure = array_merge( $this->get_prop( 'configure' ), $this->get_prop( '_configure' ) );

      // Process the fields
      foreach ( $_configure as $field )
      {
        $_field_options = array_merge( $field_options, [
          'generation_key' => $_key . ':' . $field['name'],
        ] );

        $_sub_fields[] = $this->generate_field( 'configure', $field['type'], array_merge( $field, [
          'key' => $_field_options['generation_key'],
        ] ), $_field_options );
      }
    }

    // Generate the basic config for the block
    $_acf = generate_acf_page_builder_block( [
      'key' => $_key,
      'name' => $this->get_prop( 'name' ),
      'label' => $this->get_prop( 'label' ),
      'sub_fields' => $_sub_fields,
    ], $_options );

    // Save a reference to the generated block's ACF key in this instance's index
    $this->_index[] = $_acf['key'];

    return $_acf;
  }
}
