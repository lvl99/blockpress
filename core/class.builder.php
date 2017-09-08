<?php
/**
 * ACF Page Builder
 */

namespace LVL99\ACFPageBuilder;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class Builder extends Entity {
  /**
   * Current version of the Page Builder
   *
   * @var string
   */
  public $VERSION = ACF_PAGE_BUILDER;

  /**
   * @var string
   */
  public $key = 'builder';

  /**
   * @var string
   */
  public $name = 'Builder';

  /**
   * The Page Builder settings
   *
   * @var array
   */
  public $settings = [];

  /**
   * Collection of available blocks
   *
   * @var array
   * @private
   */
  protected $loaded_blocks = [];

  /**
   * Collection of available layouts
   *
   * @var array
   * @private
   */
  protected $loaded_layouts = [];

  /**
   * Collection of available templates
   *
   * @var array
   * @private
   */
  protected $loaded_templates = [];

  /**
   * The generate ACF config for the page builder
   *
   * @var array
   * @protected
   */
  protected $_acf = [];

  /**
   * Class Builder constructor
   *
   * @constructor
   * @param array $options
   */
  public function __constructor ()
  {
    // Yeehaw
  }

  /**
   * Initialise the Builder
   *
   * @param array $options Extra options to pass to the builder
   */
  public function initialise ( $options = [] )
  {
    $this->settings = wp_parse_args( $options, [
      //
      // Default settings go in here...
      //
      'loading' => TRUE,
      'loaded' => FALSE,
      'initialising' => FALSE,
      'initialised' => FALSE,
      'file' => __FILE__,
      'path' => __DIR__,
    ] );

    // Load all the blocks, layouts and templates
    $this->load_blocks();
    $this->load_layouts();
    $this->load_templates();
    $this->settings['loading'] = FALSE;
    $this->settings['loaded'] = TRUE;

    // Initialise the loaded blocks/layouts
    $this->settings['initialising'] = TRUE;
    $this->initialise_blocks();
    $this->initialise_layouts();
    $this->settings['initialising'] = FALSE;
    $this->settings['initialised'] = TRUE;

    // Generate the ACF config to set up the backend with
    $this->generate_acf();

    if ( ! empty( $this->_acf ) )
    {
      foreach( $this->_acf as $acf_group )
      {
        acf_add_local_field_group( $acf_group );
      }
    }
  }

  /**
   * Load blocks into the builder
   *
   * @protected
   */
  protected function load_blocks ()
  {
    /**
     * @filter LVL99\ACFPageBuilder\Builder\load_blocks
     * @param array $load_blocks An associative array of all the blocks to load into the builder
     * @returns array
     */
    $_load_blocks = apply_filters( 'LVL99\ACFPageBuilder\Builder\load_blocks', [] );
    $_loaded_blocks = [];

    foreach ( $_load_blocks as $block_name => $block_data )
    {
      if ( file_exists( $block_data['path'] ) )
      {
        try
        {
          require_once( $block_data['path'] );
          $_loaded_block_class = $block_data['class'];
          $_loaded_blocks[ $block_name ] = $block_data;
          $_loaded_blocks[ $block_name ]['instance'] = new $_loaded_block_class();
          $this->blocks[] = $block_name;
        }
        catch ( \Exception $e )
        {
          error_log( 'Failed to load Page Builder Block: "' . $block_name . '" with path: "' . $block_data['path'] . '"' );
        }
      }
    }

    // Save the list of loaded blocks into the instance for the layouts/templates to refer to
    $this->loaded_blocks = $_loaded_blocks;
  }

  /**
   * Load layouts into the builder
   *
   * @protected
   */
  protected function load_layouts ()
  {
    /**
     * @filter LVL99\ACFPageBuilder\Builder\load_layouts
     * @param array $load_layouts An associative array of all the layouts to load into the builder
     * @returns array
     */
    $_load_layouts = apply_filters( 'LVL99\ACFPageBuilder\Builder\load_layouts', [] );
    $_loaded_layouts = [];

    foreach ( $_load_layouts as $layout_name => $layout_data )
    {
      try
      {
        require_once( $layout_data['path'] );
        $_loaded_layout_class = $layout_data['class'];
        $_loaded_layouts[ $layout_name ] = $layout_data;
        $_loaded_layouts[ $layout_name ]['instance'] = new $_loaded_layout_class();
        $this->layouts[] = $layout_name;
      }
      catch ( \Exception $e )
      {
        error_log( 'Failed to load Page Builder Layout: "' . $layout_name . '" with path: "' . $layout_data['path'] . '"' );
      }
    }

    $this->loaded_layouts = $_loaded_layouts;
  }

  /**
   * Load templates into the builder
   *
   * @protected
   */
  protected function load_templates ()
  {

  }

  /**
   * Generate the code for ACF to recognise the custom fields
   *
   * @protected
   * @returns array
   */
  protected function generate_acf ()
  {
    $_acf = [];
    $_layouts = $this->get_layouts();

    // For each layout generate the extra blocks
    foreach( $_layouts as $layout_name => $layout_instance )
    {
      $_acf[] = $layout_instance->generate_acf();
    }

    $this->_acf = $_acf;
    return $_acf;
  }

  /**
   * Get a loaded block instance
   *
   * @param $block_name
   * @return {Block}
   */
  public function get_block_instance ( $block_name )
  {
    if ( array_key_exists( $block_name, $this->loaded_blocks ) )
    {
      return $this->loaded_blocks[ $block_name ]['instance'];
    }
  }

  /**
   * Get an associative array of named block instances
   *
   * @param array $block_names
   * @return array
   */
  public function get_block_instances ( $block_names = [] )
  {
    $_blocks = [];

    // Default to all blocks if none specified
    if ( empty( $block_names ) )
    {
      $block_names = array_keys( lvl99_acf_page_builder()->get_blocks() );
    }

    // Get each named block instance
    foreach ( $block_names as $block_name )
    {
      $_blocks[ $block_name ] = lvl99_acf_page_builder()->get_block_instance( $block_name );
    }

    return $_blocks;
  }

  /**
   * Get a loaded layout instance
   *
   * @param $layout_name
   * @return {Layout}
   */
  public function get_layout_instance ( $layout_name )
  {
    if ( array_key_exists( $layout_name, $this->loaded_layouts ) )
    {
      return $this->loaded_layouts[ $layout_name ]['instance'];
    }
  }

  /**
   * Get the builder's ACF config
   *
   * @return array
   */
  public function get_acf ()
  {
    return $this->_acf;
  }
}
