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
    $this->initialise_blocks();
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

    // If a key was given, use it
    if ( ! empty( $key ) )
    {
      $_key = $key;
    }

    // Pass it along...
    $_options = array_merge( $options, [
      'generation_key' => $_key,
      'layout' => $this->get_prop( 'name' ),
      'blocks' => $this->get_blocks(),
    ] );

    // Build the ACF fields for the layout blocks
    $_acf = generate_acf_page_builder_layout( [
      'key' => $_key,
      'name' => $this->get_prop( 'name' ),
      'label' => $this->get_prop( 'label' ),
      'instructions' => $this->get_prop( 'instructions' ),
      'layouts' => [],
      'button_label' => 'Add Block',
    ], $_options );

    // Save a reference to the generated layout's ACF key in this instance's index
    $this->_index[] = $_acf['key'];

    return $_acf;
  }
}
