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
   * The unique key for the layout field group
   *
   * @var string
   */
  public $key = '';

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
   * The extra ACF options for this layout
   * @var array
   */
  public $acf_options = [];

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
    // @TODO figure out how to process the extra options to apply $pecial $auce
    // $this->initialise_fields( [ 'acf_options' ] );
  }

  /**
   * Generate the ACF config for the layout
   */
  public function generate_acf ()
  {
    $_key = $this->get_key();

    // Build the ACF fields for the layout blocks
    $acf_layout_blocks = generate_acf_field_flexible_content( [
      'key' => $_key,
      'name' => $this->get_prop( 'name' ),
      'label' => 'Content',
      'instructions' => $this->get_prop( 'instructions' ),
      'layouts' => [],
      'button_label' => 'Add Layout Block',
    ], [
      'blocks' => $this->get_blocks(),
    ] );

    // Generate the main layout ACF config
    $acf_group = array_merge( $this->get_prop( 'acf_options' ), [
      'key' => $_key,
      'title' => $this->get_prop( 'label' ),
      'description' => $this->get_prop( 'description' ),
      'fields' => [
        $acf_layout_blocks,
      ],
    ] );

    // Generate the full ACF group config
    $_acf = generate_acf_group( $acf_group );

    return $_acf;
  }
}
