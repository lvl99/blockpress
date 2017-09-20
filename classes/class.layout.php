<?php
/**
 * ACF Page Builder - Layout
 *
 * Represents a field group that can affect a post type's `post_content`
 */

namespace LVL99\ACFPageBuilder;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class Layout extends Entity {
  /**
   * The name of the layout
   *
   * @var string
   */
  public $name = '';

  /**
   * The label (or display name) of the layout
   *
   * @var string
   */
  public $label = '';

  /**
   * The description of the layout
   *
   * @var string
   */
  public $description = '';

  /**
   * Instructions to the user about this layout
   *
   * @var string
   */
  public $instructions = '';

  /**
   * Extra fields for this layout
   *
   * @var array
   */
  public $fields = [];

  /**
   * An index containing the keys for all the generated layouts using this class
   *
   * @var array
   */
  protected $_index = [];

  /**
   * Class Layout
   *
   * @constructor
   * @param string $key
   */
  public function __construct ( $key = '' )
  {
    $this->set_key( $key );
  }

  /**
   * Initialise the layout with any nested blocks
   */
  public function initialise ()
  {
    $this->initialise_blocks();
    // $this->initialise_fields( [ 'fields' ] );
  }

  /**
   * Generate the ACF config for the layout
   *
   * @param string $key
   * @param array $options
   * @returns array
   */
  public function generate_acf ( $key = '', $options = [] )
  {
    $_key = $this->get_key();

    // Pass it along...
    $_options = wp_parse_args( $options, [
      'generation_key' => $_key,
      'layout_slug' => $this->get_prop( 'name' ),
    ] );

    // If a key was given, use it
    if ( ! empty( $key ) )
    {
      $_key = $key;
      $_options['generation_key'] = $_key;
    }

    // Ensure the layout instance is loaded into the options
    $_options['layout'] = $this;

    // Ensure the blocks are loaded into the options
    $_options['blocks'] = $this->get_blocks();

    // Build the ACF fields for the layout blocks
    $acf_layout = generate_acf_page_builder_layout( [
      'key' => $_key,
      'name' => $_options['layout_slug'],
      'label' => $this->get_prop( 'label' ),
      'instructions' => $this->get_prop( 'instructions' ),
      'layouts' => [],
      'button_label' => 'Add Block',
    ], $_options );

    // @TODO support extra fields for layouts
    // Generate the extra fields for this layout
    // $acf_fields = [];
    // foreach ( $this->fields as $field )
    // {
    //   $acf_fields[] = $this->generate_field( NULL, $field['type'], array_merge( $field, [
    //     'key' => $_key . ':' . $field['name'],
    //   ] ), [
    //     'layout_name' => $_options['layout_slug'],
    //     'layout' => $this,
    //   ] );
    // }

    // Save a reference to the generated layout's ACF key in this instance's index
    $this->_index[] = $acf_layout['key'];

    return $acf_layout;
  }
}
