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
   * @var string
   */
  public $name = 'builder';

  /**
   * @var string
   */
  public $label = 'LVL99 ACF Page Builder';

  /**
   * @var string
   */
  public $description = 'Use LVL99 ACF Page Builder to create custom page/post content layouts';

  /**
   * The Page Builder settings
   *
   * @var array
   */
  public $settings = [];

  /**
   * Collection of loaded and available blocks
   *
   * @var array
   * @private
   */
  protected $loaded_blocks = [];

  /**
   * Collection of loaded and available layouts
   *
   * @var array
   * @private
   */
  protected $loaded_layouts = [];

  /**
   * Collection of loaded and available templates
   *
   * @var array
   * @private
   */
  protected $loaded_templates = [];

  /**
   * The generated ACF config for the page builder
   *
   * @var array
   * @protected
   */
  protected $_acf = [];

  /**
   * Class Builder
   *
   * @constructor
   * @param array $options
   */
  public function __construct ()
  {
    // Yeehaw
    $this->set_key( $this->name );
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
      acf_add_local_field_group( $this->_acf );
    }
  }

  /**
   * Load blocks into the builder
   *
   * @protected
   */
  protected function load_blocks ()
  {
    $_key = $this->get_key();

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
          $_loaded_blocks[ $block_name ]['instance'] = new $_loaded_block_class( [ $_key, $block_name ] );
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
    $_key = $this->get_key();

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
        $_loaded_layouts[ $layout_name ]['instance'] = new $_loaded_layout_class( [ $_key, $layout_name ] );
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
    // @TODO
  }

  /**
   * Generate the code for ACF to recognise the custom fields
   *
   * @protected
   * @returns array
   */
  protected function generate_acf ()
  {
    $_key = $this->get_key();
    $_layouts = $this->get_layouts();
    $acfpb_fields = [];
    $acfpb_layouts = [];

    // Create a true_false field to mark whether to use the Page Builder or not
    $acfpb_builder_enabled = generate_acf_field_true_false( [
      'key' => $_key . '_enabled',
      'name' => 'acfpb_' . $_key . '_enabled',
      'label' => 'Use ' . $this->get_prop( 'label' ),
      'ui' => 1,
    ] );
    $acfpb_fields[] = $acfpb_builder_enabled;

    // For each layout generate the ACF flexible content field for the layout
    foreach( $_layouts as $layout_name => $layout_instance )
    {
      $acfpb_layouts[] = $layout_instance->generate_acf();
    }

    // Create a select element to choose which layout to use
    $_select_layouts = [];
    foreach ( $acfpb_layouts as $index => $acfpb_layout )
    {
      $_select_layouts[ $_key . '_' . $acfpb_layout['name'] ] = $acfpb_layout['label'];
    }
    $acfpb_builder_select_layout = generate_acf_field_select( [
      'key' => $_key . '_layout',
      'name' => 'acfpb_' . $_key . '_layout',
      'label' => 'Select layout',
      'choices' => $_select_layouts,
      'conditional_logic' => [
        [
          [
            'field' => $acfpb_builder_enabled['key'],
            'operator' => '==',
            'value' => '1',
          ],
        ],
      ],
    ] );
    $acfpb_fields[] = $acfpb_builder_select_layout;

    // Attach conditional logic based on the select's value to each generated layout's config
    foreach ( $acfpb_layouts as $index => $acfpb_layout )
    {
      $acfpb_layout['conditional_logic'] = [
        [
          [
            'field' => $acfpb_builder_select_layout['key'],
            'operator' => '==',
            'value' => $_key . '_' . $acfpb_layout['name']
          ],
        ],
      ];
      $acfpb_fields[] = $acfpb_layout;
    }

    // Generate the full ACF group config for the Page Builder
    $acf = generate_acf_group( [
      'key' => $_key,
      'title' => $this->get_prop( 'label' ),
      'description' => $this->get_prop( 'description' ),
      'fields' => $acfpb_fields,
      'style' => 'seamless',
      'location' => [
        [
          [
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'post',
          ],
        ],
        [
          [
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'page',
          ],
        ],
      ],
      'position' => 'acf_after_title',
    ] );

    $this->_acf = $acf;
    return $acf;
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
