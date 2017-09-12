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
   * The fields to enter content that the block displays
   *
   * @var array
   */
  public $content = [

  ];

  /**
   * The fields to further customise the block, or block's content
   *
   * @var array
   */
  public $customise = [

  ];

  /**
   * The fields to configure the block, or its content's, behaviour
   *
   * @var array
   */
  public $configure = [

  ];

  /**
   * The core configuration fields for the block (behaviour or the block's HTML element itself)
   *
   * @var array
   * @protected
   */
  protected $_configure = [
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
   * The generated ACF config for the block's `sub_fields` (essentially a collection of all the fields listed above)
   *
   * @var array
   * @protected
   */
  protected $acf_sub_fields = [];

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
    $this->initialise_fields( [ 'content', 'customise', 'configure', '_configure' ] );
  }

  /**
   * Generate the code to use within ACF
   *
   * @param string $key
   * @param array $options
   * @protected
   * @returns array
   */
  public function generate_acf ( $key = '', $options = [] )
  {
    $_key = $this->get_key();

    // If a namespace key was given, ensure it's at the start
    if ( ! empty( $key ) )
    {
      $_key = $this->get_namespaced_key( $key );
    }

    $_options = wp_parse_args( $options, [
      'generate_key' => $_key,
      'layout' => '',
      'block' => $this->get_prop( 'name' ),
      // @NOTE Dunno if I should put `blocks` here
      // 'blocks' => $this->get_blocks(),
    ] );

    // Generate all the fields for this block
    $_sub_fields = [];

    // Process all the basic fields for this block
    if ( ! empty( $this->get_prop( 'content' ) ) )
    {
      foreach ( $this->get_prop( 'content' ) as $field )
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
        'name' => 'acfpb_block_customise',
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
        'name' => 'acfpb_block_configure',
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
    $_acf = generate_acf_page_builder_block( [
      'key' => $_key,
      'name' => $this->get_prop( 'name' ),
      'label' => $this->get_prop( 'label' ),
      'sub_fields' => $_sub_fields,
    ], $_options );

    return $_acf;
  }

  /**
   * Register the block within the global builder's map
   *
   * @param array $acf
   */
  public function register_in_map ( $acf, $options = [] )
  {
    lvl99_acf_page_builder()->register_block_in_map( $this, $acf, $options );
  }

  /**
   * Render the block for the post
   *
   * @param int|string|\WP_Post $post
   * @param array $options
   */
  public function render ( $post = NULL, $options = [] )
  {
    lvl99_acf_page_builder()->render_block( $post, array_merge( $options, [
      'block' => $this->get_prop( 'name' ),
    ] ) );
  }
}
