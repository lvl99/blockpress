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
    $_key = $this->get_namespaced_key( $key );

    // Pass it along...
    $_options = wp_parse_args( $options, [
      'generate_key' => $key,
      'generated_key' => $_key,
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

    return $_acf;
  }

  /**
   * Render the layout
   *
   * @param int|string|\WP_Post $post
   * @param array $options
   * @returns mixed
   */
  public function render ( $post = NULL, $options = [] )
  {
    return lvl99_acf_page_builder()->render_layout( $post, array_merge( $options, [
      'layout' => $this->get_prop( 'name' ),
    ] ) );
  }
}
