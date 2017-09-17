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
   * The flatmap of the generated blocks/fields ACF config
   *
   * This is for easy access when generating the render data
   *
   * @var array
   * @protected
   */
  protected $_flatmap = [];

  /**
   * An index of all the generated blocks
   *
   * @NOTE Since there's the flatmap, not sure if I really need this?
   *
   * @var array
   */
  protected $_index = [];

  /**
   * A cache for storing the generated layout render data for each post
   *
   * @var array
   */
  protected $_render_data = [
    /*
    // Key format is: `${post_type}_${post_id}`
    'post_333' => [
      // Each layout is stored within
      'acfpb_builder_page' => [ ... ]
    ],
     */
  ];

  /**
   * Twig environment for loading twig templates
   */
  protected $_twig_env = NULL;

  /**
   * Twig renderer
   */
  protected $_twig = NULL;

  /**
   * Collection of validated view directories (used by both Twig and PHP rendering)
   */
  protected $_view_dirs = [];

  /**
   * Cached array of located view files
   *
   * @var array
   * @private
   */
  private $_views = [
    /*
    // Key format is: `${layout_name}_${block_name}`
    'page_text' => '/path/to/view/file',
    // @NOTE both layout_name/block_name is optional, e.g.:
    'page' => '/path/to/view/file',
    'text' => '/path/to/view/file',
     */
  ];

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
      /**
       * The supported post types that can show the Page Builder in the backend
       *
       * @hook LVL99\ACFPageBuilder\Builder\default_settings\post_types
       * @param array
       * @returns array
       */
      'post_types' => apply_filters( 'LVL99\ACFPageBuilder\Builder\default_settings\post_types', [
        'post',
        'page',
      ] ),

      // Enable Twig rendering
      /**
       * Enable Twig rendering
       *
       * @hook LVL99\ACFPageBuilder\Builder\default_settings\twig
       * @param bool
       * @returns bool
       */
      'twig' => apply_filters( 'LVL99\ACFPageBuilder\Builder\default_settings\twig', TRUE ),

      /**
       * The paths to where Twig views could be located ordered by highest priority first
       *
       * @hook LVL99\ACFPageBuilder\Builder\default_settings\twig_views
       * @param array
       * @returns array
       */
      'twig_views' => apply_filters( 'LVL99\ACFPageBuilder\Builder\default_settings\twig_views', [
        LVL99_ACF_PAGE_BUILDER_PATH . '/views/blocks',
        LVL99_ACF_PAGE_BUILDER_PATH . '/views/layouts',
      ] ),

      /**
       * Extra options to pass to the Twig environment
       *
       * @hook LVL99\ACFPageBuilder\Builder\default_settings\twig_options
       * @param array
       * @param Builder $this
       * @returns array
       */
      'twig_options' => apply_filters( 'LVL99\ACFPageBuilder\Builder\default_settings\twig_options', [
        'debug' => TRUE,
        'cache' => get_temp_dir() . '/cache/lvl99-acfpb',
      ] ),

      /**
       * Enable `the_content` and `the_excerpt` filters to affect the display of the layouts
       *
       * IMPORTANT: lots of plugins use `the_content` filter other than display the post's content. Use with caution...
       *
       * @hook LVL99\ACFPageBuilder\Builder\default_settings\use_render_hooks
       * @param bool
       * @returns bool
       */
      'use_render_hooks' => apply_filters( 'LVL99\ACFPageBuilder\Builder\default_settings\use_render_hooks', TRUE ),

      /**
       * Use cache for rendering layouts, only if not in development mode
       *
       * @hook LVL99\ACFPageBuilder\Builder\default_settings\use_cache
       * @param bool
       * @returns bool
       */
      'use_cache' => apply_filters( 'LVL99\ACFPageBuilder\Builder\default_settings\use_cache', ( WP_ENV === 'development' ? FALSE : WP_CACHE ) ),
    ] );

    /**
     * Change the settings after loading defaults
     *
     * @hook LVL99\ACFPageBuilder\Builder\settings
     * @param array
     * @returns array
     */
    $this->settings = apply_filters( 'LVL99\ACFPageBuilder\Builder\settings', $this->settings );

    // Set status of Builder loading and initialisation process
    $this->settings['_file'] = __FILE__;
    $this->settings['_loading'] = TRUE;
    $this->settings['_loaded'] = FALSE;
    $this->settings['_initialising'] = FALSE;
    $this->settings['_initialised'] = FALSE;

    // Load all the blocks, layouts and templates
    $this->load_blocks();
    $this->load_layouts();
    $this->load_templates();
    $this->load_view_folders();
    $this->settings['_loading'] = FALSE;
    $this->settings['_loaded'] = TRUE;

    // Initialise the loaded blocks/layouts
    $this->settings['_initialising'] = TRUE;
    $this->initialise_blocks();
    $this->initialise_layouts();
    $this->initialise_twig();

    // Generate the ACF config to set up the backend with
    $this->generate_acf();
    if ( ! empty( $this->_acf ) )
    {
      acf_add_local_field_group( $this->_acf );
    }

    // Enable the render hooks
    if ( $this->settings['use_render_hooks'] )
    {
      $this->setup_filters();
    }

    // Et voila
    $this->settings['_initialising'] = FALSE;
    $this->settings['_initialised'] = TRUE;
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
     * @hook LVL99\ACFPageBuilder\Builder\load_blocks
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

          // Create the single block instance which is reused
          $_loaded_block = $block_data;
          $_loaded_block_class = $block_data['class'];
          $_loaded_block['instance'] = new $_loaded_block_class( $this->get_appended_key( $block_name ) );

          // Save loaded block into Builder
          $this->loaded_blocks[ $block_name ] = $_loaded_block;
          $this->blocks[] = $block_name;
          $this->_blocks[ $block_name ] = $_loaded_block;
        }
        catch ( \Exception $e )
        {
          error_log( 'Failed to load Page Builder block: "' . $block_name . '" with path: "' . $block_data['path'] . '"' );
        }
      }
    }
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

        // Create a single layout instance which is reused
        $_loaded_layout = $layout_data;
        $_loaded_layout_class = $layout_data['class'];
        $_loaded_layout['instance'] = new $_loaded_layout_class( $this->get_appended_key( $layout_name ) );

        // Save loaded layout into Builder
        $this->loaded_layouts[ $layout_name ] = $_loaded_layout;
        $this->layouts[] = $layout_name;
        $this->_layouts[ $layout_name ] = $_loaded_layout;
      }
      catch ( \Exception $e )
      {
        error_log( 'Failed to load Page Builder layout: "' . $layout_name . '" with path: "' . $layout_data['path'] . '"' );
      }
    }
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
   * Test the view folders to see which ones are valid to enable using in the Twig loader
   *
   * @protected
   */
  protected function load_view_folders ()
  {
    // Generate potential locations that views could exist in
    $view_dirs = [
      // @TODO might need to support child themes?
      get_template_directory() . '/views/layouts',
      get_template_directory() . '/views/blocks',
      get_template_directory() . '/views',
      get_template_directory(),
      LVL99_ACF_PAGE_BUILDER_PATH . '/views/layouts',
      LVL99_ACF_PAGE_BUILDER_PATH . '/views/blocks',
      LVL99_ACF_PAGE_BUILDER_PATH . '/views',
    ];

    $valid_view_dirs = [];
    foreach ( $view_dirs as $view_dir )
    {
      if ( file_exists( $view_dir ) )
      {
        $valid_view_dirs[] = $view_dir;
      }
    }

    // Save the validated view folders into the instance
    $this->_view_dirs = $valid_view_dirs;
  }

  /**
   * Initialise Twig renderer and its loaders
   */
  protected function initialise_twig ()
  {
    // Load and initialise Twig (if not already initialised
    if ( $this->settings['twig'] && empty( $this->_twig ) && empty( $this->_twig_env ) )
    {
      // Load in Composer dependencies with Twig only if Twig isn't already loaded (WPML already loads in Twig)
      if ( ! class_exists( '\\Twig_Environment' ) )
      {
        $autoloader = LVL99_ACF_PAGE_BUILDER_PATH . '/vendor/autoload.php';
        if ( version_compare( PHP_VERSION, '5.3.0' ) < 0 ) {
          $autoloader = LVL99_ACF_PAGE_BUILDER_PATH . '/vendor/autoload_52.php';
        }
        require_once $autoloader;
      }

      // Use the custom loader to be able to load from absolute paths
      require_once LVL99_ACF_PAGE_BUILDER_PATH . '/classes/twig/class.twig-loader-abspath.php';

      // Instantiate Twig loader and renderer
      // -- Here's the regular Twig loader
      $twig_filesystem_loader = new \Twig_Loader_Filesystem( $this->_view_dirs );
      // -- Here's a custom Twig loader to refer to views using absolute paths
      $twig_abspath_loader = new Twig_Loader_Abspath();
      // -- And this loader chain means we use relative filesystem loader first, then absolute path loader last
      $this->_twig_env = new \Twig_Loader_Chain([ $twig_filesystem_loader, $twig_abspath_loader ]);
      $this->_twig = new \Twig_Environment( $this->_twig_env, $this->get_setting( 'twig_options' ) );

      // Add in the debug stuff
      if ( array_key_exists( 'debug', $this->get_setting( 'twig_options' ) ) && $this->get_setting( 'twig_options' )['debug'] )
      {
        $this->_twig->addExtension( new \Twig_Extension_Debug() );
      }
    }
  }

  /**
   * Generate a new ACF config for a layout
   *
   * @param string $layout_name
   * @param array $options
   * @returns array
   */
  public function generate_layout ( $layout_name, $options = [] )
  {
    $_options = wp_parse_args( $options, [
      'nested_key' => $layout_name,
    ] );

    $generation_key = $this->get_appended_key( $_options['nested_key'] );
    $layout_instance = $this->get_layout_instance( $layout_name );
    $layout_slug = 'acfpb_' . $this->get_key() . '_' . $layout_instance->get_prop( 'name' );
    $acf_layout = $layout_instance->generate_acf( $generation_key, [
      'builder' => $this,
      // Make the layout slug incorporate the builder name
      'layout_slug' => $layout_slug,
      'layout' => $layout_instance,
    ] );

    // Add this layout to the builder index
    $this->_index[ $acf_layout['key'] ] = [
      'generation_key' => $generation_key,
      'layout_slug' => $layout_slug,
      'layout' => $layout_instance,
      'builder' => $this,
      'key' => $acf_layout['key'],
      'acf' => $acf_layout,
    ];

    return $acf_layout;
  }

  /**
   * Generate a new ACF config for a block
   *
   * @param $block_name
   * @param string $layout_name
   * @param array $options
   * @returns array
   */
  public function generate_block ( $block_name, $layout_name, $options = [] )
  {
    $_options = wp_parse_args( $options, [
      'nested_key' => $layout_name . ':' . $block_name,
    ] );

    $generation_key = $this->get_appended_key( $_options['nested_key'] );
    $layout_instance = $this->get_layout_instance( $layout_name );
    $layout_slug = 'acfpb_' . $this->get_key() . '_' . $layout_instance->get_prop( 'name' );
    $block_instance = $this->get_block_instance( $block_name );

    // Generate the ACF code
    $generate_acf_options = array_merge( $_options, [
      'generation_key' => $generation_key,
      'builder' => $this,
      'layout_slug' => $layout_slug,
      'layout' => $layout_instance,
      'block' => $block_instance,
    ] );

    $acf_block = $block_instance->generate_acf( $generation_key, $generate_acf_options );

    // Create registration object for the generated block
    $register_block = [
      'generation_key' => $generation_key,
      'builder' => $this,
      'layout_slug' => $layout_slug,
      'layout' => $layout_instance,
      'block' => $block_instance,
      'key' => $acf_block['key'],
      'acf' => $acf_block,
      'map' => $this->map_block_data_acf( $block_instance, $acf_block, [
        'builder' => $this,
        'layout_slug' => $layout_slug,
        'layout' => $layout_instance,
        'block' => $block_instance,
      ] ),
    ];

    // Add reference to the parent which uses this generated block
    if ( array_key_exists( 'parent' , $_options ) )
    {
      $register_block['parent'] = $_options['parent'];
    }

    // Add this block to the builder index
    $this->_index[ $acf_block['key'] ] = $register_block;

    return $acf_block;
  }

  /**
   * Generate the code for ACF to recognise the custom fields
   *
   * @protected
   * @returns array
   */
  public function generate_acf ()
  {
    $key = $this->get_key();
    $layouts = $this->get_layouts();
    $acfpb_builder_fields = [];
    $acfpb_builder_layouts = [];

    // Allow the Page Builder to only be visible for these post types
    $acfpb_supported_post_types = empty( $this->get_setting( 'post_types' ) );
    if ( empty( $acfpb_supported_post_types ) )
    {
      $acfpb_supported_post_types = [ 'post', 'page' ];
      $this->settings['post_types'] = $acfpb_supported_post_types;
    }

    // Generate the ACF config for the supported post type locations
    $acfpb_builder_location = [];
    foreach ( $acfpb_supported_post_types as $post_type )
    {
      // To ensure the location match is an "OR", we create an array within an array for ACF
      $acfpb_builder_location[] = [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => $post_type,
        ],
      ];
    }

    // Create a true_false field to mark whether to use the Page Builder or not
    $acfpb_builder_enabled = generate_acf_field_true_false( [
      'key' => $key . ':enabled',
      'name' => 'acfpb_' . $key . '_enabled',
      'label' => 'Use ' . $this->get_prop( 'label' ),
      'ui' => 1,
    ] );
    $acfpb_builder_fields[] = $acfpb_builder_enabled;

    // For each layout generate the ACF flexible content field for the layout
    foreach( $layouts as $layout_name => $layout_instance )
    {
      $acfpb_builder_layouts[] = $this->generate_layout( $layout_name );
    }

    // Create a select element to choose which layout to use
    $acfpb_builder_select_choices = [];
    foreach ( $acfpb_builder_layouts as $index => $acfpb_layout )
    {
      $acfpb_builder_select_choices[ $acfpb_layout['name'] ] = $acfpb_layout['label'];
    }
    $acfpb_builder_select_layout = generate_acf_field_select( [
      'key' => $key . ':layout',
      'name' => 'acfpb_' . $key . '_layout',
      'label' => 'Select layout',
      'choices' => $acfpb_builder_select_choices,
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
    $acfpb_builder_fields[] = $acfpb_builder_select_layout;

    // Attach conditional logic based on the select's value to each generated layout's config
    foreach ( $acfpb_builder_layouts as $index => $acfpb_layout )
    {
      $acfpb_layout['conditional_logic'] = [
        [
          [
            'field' => $acfpb_builder_enabled['key'],
            'operator' => '==',
            'value' => '1',
          ],
          [
            'field' => $acfpb_builder_select_layout['key'],
            'operator' => '==',
            'value' => $acfpb_layout['name'],
          ],
        ],
      ];
      $acfpb_builder_fields[] = $acfpb_layout;
    }

    // Generate the full ACF group config for the Page Builder
    $acf = generate_acf_group( [
      'key' => $key,
      'title' => $this->get_prop( 'label' ),
      'description' => $this->get_prop( 'description' ),
      'fields' => $acfpb_builder_fields,
      'style' => 'seamless',
      'location' => $acfpb_builder_location,
      'position' => 'acf_after_title',
    ] );

    $this->_acf = $acf;
    return $acf;
  }

  /**
   * Get a loaded layout instance
   *
   * @param string $layout_name
   * @return Layout
   * @throws \Exception
   */
  public function get_layout_instance ( $layout_name )
  {
    if ( array_key_exists( $layout_name, $this->loaded_layouts ) )
    {
      if ( array_key_exists( 'instance', $this->loaded_layouts[ $layout_name ] ) )
      {
        return $this->loaded_layouts[ $layout_name ]['instance'];
      } else {
        throw new \Exception( 'No instance was created for layout "' . $layout_name . '"' );
      }
    }

    throw new \Exception( 'Layout "' . $layout_name . '" does not exist' );
  }

  /**
   * Get a loaded block instance
   *
   * @param string $block_name
   * @return Block
   * @throws \Exception
   */
  public function get_block_instance ( $block_name )
  {
    if ( empty( $block_name ) || ! is_string( $block_name ) )
    {
      throw new \Exception( 'Invalid block name given' );
    }

    if ( array_key_exists( $block_name, $this->loaded_blocks ) )
    {
      if ( array_key_exists( 'instance', $this->loaded_blocks[ $block_name ] ) )
      {
        return $this->loaded_blocks[ $block_name ]['instance'];
      } else {
        throw new \Exception( 'No instance was created for block "' . $block_name . '"' );
      }
    }

    throw new \Exception( 'Block "' . $block_name . '" does not exist' );
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
      $block_names = array_keys( $this->get_blocks() );
    }

    // Get each named block instance
    foreach ( $block_names as $block_name )
    {
      $_blocks[ $block_name ] = $this->get_block_instance( $block_name );
    }

    return $_blocks;
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

  /**
   * Get the value for a named setting
   *
   * @param string $setting_name
   * @param mixed $default_value The default value if a setting isn't defined/used
   * @returns mixed
   */
  public function get_setting ( $setting_name, $default_value = NULL )
  {
    if ( array_key_exists( $setting_name, $this->settings ) )
    {
      return $this->settings[ $setting_name ];
    }

    return $default_value;
  }

  /**
   * Check if the Page Builder is enabled for a post
   *
   * @param int|string|\WP_Post $post
   * @returns bool
   */
  public function is_enabled ( $post )
  {
    $key = $this->get_key();
    $is_enabled = get_field( 'acfpb_' . $key . '_enabled', $post );
    return $is_enabled;
  }

  /**
   * Get the Page Builder's active layout for a post
   *
   * @param int|string|\WP_Post $post
   * @returns string
   */
  public function get_active_layout ( $post = NULL )
  {
    $post = get_post( $post );
    $key = $this->get_key();
    $active_layout = get_field( 'acfpb_' . $key . '_layout', $post );
    return $active_layout;
  }

  /**
   * Locate a layout's view file
   *
   * The plugin will look in the active theme's views folder first, then in its own views folder.
   *
   * The plugin is setup to use Twig, but you could reference a PHP file if you really want.
   *
   * @param string $layout_name
   * @returns null|string
   */
  public function locate_layout_view( $layout_name )
  {
    $view_slug = $layout_name;

    // Check if location is already cached
    if ( array_key_exists( $view_slug, $this->_views ) )
    {
      return $this->_views[ $view_slug ];
    }

    // Generate potential locations that the view could exist in
    $view_dirs = $this->_view_dirs;
    $view_filenames = [];

    // Reference twig filenames
    if ( $this->get_setting( 'twig' ) )
    {
      $view_filenames[] = $layout_name . '.twig';
    }
    $view_filenames[] = $layout_name . '.php';

    // Check all potential locations for the view file to be located
    foreach ( $view_dirs as $view_dir )
    {
      foreach ( $view_filenames as $view_filename )
      {
        $view_file_path = $view_dir . '/' . $view_filename;
        if ( file_exists( $view_file_path ) )
        {
          $this->_views[ $view_slug ] = $view_file_path;
          return $view_file_path;
        }
      }
    }

    // Since layouts aren't so important, we don't need to throw an exception, but we will throw a null to show that
    // it wasn't found
    return NULL;
  }

  /**
   * Locate a block's view
   *
   * You can also specify which layout the block is for in case you have different views for blocks used in specific
   * layouts.
   *
   * The plugin will look in the active theme's views folder first, then in its own views folder.
   *
   * The plugin is setup to use Twig, but you could reference a PHP file if you really want.
   *
   * @param string $block_name
   * @param string $layout_name
   * @returns string
   * @throws \Exception
   */
  public function locate_block_view( $block_name, $layout_name = '' )
  {
    $view_slug = ( ! empty( $layout_name ) ? $layout_name . '_' . $block_name : $block_name );

    // Check if location is already cached
    if ( array_key_exists( $view_slug, $this->_views ) )
    {
      return $this->_views[ $view_slug ];
    }

    // Generate potential locations that the view could exist in
    $view_dirs = $this->_view_dirs;
    $view_filenames = [];

    // Reference twig filenames
    if ( $this->get_setting( 'twig' ) )
    {
      $view_filenames[] = $block_name . '.twig';
    }
    $view_filenames[] = $block_name . '.php';

    // Add extra filenames to check if the layout name was specified as well
    // Supports if you want to have a different view for a block within a specific layout
    if ( ! empty( $layout_name ) )
    {
      // Reference twig filenames
      if ( $this->get_setting( 'twig' ) )
      {
        $view_filenames[] = $layout_name . '-' . $block_name . '.twig';
        $view_filenames[] = $layout_name . '_' . $block_name . '.twig';
        $view_filenames[] = $layout_name . '.' . $block_name . '.twig';
      }
      $view_filenames[] = $layout_name . '-' . $block_name . '.php';
      $view_filenames[] = $layout_name . '_' . $block_name . '.php';
      $view_filenames[] = $layout_name . '.' . $block_name . '.php';
    }

    // Check all potential locations for the view file to be located
    foreach ( $view_dirs as $view_dir )
    {
      foreach ( $view_filenames as $view_filename )
      {
        $view_file_path = $view_dir . '/' . $view_filename;
        if ( file_exists( $view_file_path ) )
        {
          $this->_views[ $view_slug ] = $view_file_path;
          return $view_file_path;
        }
      }
    }

    throw new \Exception( 'View file does not exist for block "' . $block_name . '"' . ( empty( $layout_name ) ?: ' and layout "' . $layout_name . '"' ) );
  }

  /**
   * Map a Builder block's data structure from the generated ACF fields
   *
   * The map is a way for the builder to map a block's data/schema to the generate ACF fields. This is used when
   * getting the ACF meta data from a WP_Post object and then mapping it to the Page Builder's data structure for
   * rendering.
   *
   * Mapped fields are stored in the Builder's flatmap.
   *
   * @param Block $block_instance
   * @param array $acf
   * @param array $options
   * @return array
   */
  public function map_block_data_acf ( $block_instance, $acf, $options = [] )
  {
    $_options = wp_parse_args( $options, [
      'builder' => $this,
      'layout' => '',
      'layout_slug' => '',
      'block' => $block_instance,
    ] );

    $field_groups = [ 'acfpb_block_content', 'acfpb_block_customise', 'acfpb_block_configure' ];
    $fields = [
      'content' => [],
      'customise' => [],
      'configure' => [],
    ];
    $current_field_group = 'content';

    foreach ( $acf['sub_fields'] as $index => $_field )
    {
      // Change to the next builder field group
      if ( $_field['type'] === 'tab' && in_array( $_field['name'], $field_groups ) )
      {
        $current_field_group = str_replace( 'acfpb_block_', '', $_field['name'] );
        continue;
      }

      // Only map fields that have a key
      if ( array_key_exists( 'key', $_field ) )
      {
        $field_options = array_merge( $options, [
          'field_group' => $current_field_group,
          'parent' => $acf['key'],
        ] );

        $fields[ $current_field_group ][ $_field['key'] ] = $this->map_block_data_acf_field( $block_instance, $_field, $field_options );
      }
    }

    // The mapped block
    $map_block = [
      'is_block' => TRUE,
      'key' => $acf['key'],
      'block' => $_options['block'],
      'fields' => $fields,
      'acf' => $acf,
      'builder' => $_options['builder'],
      'layout' => $_options['layout'],
      'layout_slug' => $_options['layout_slug'],
    ];

    // Ensure parent is loaded into mapped field data
    if ( array_key_exists( 'parent', $_options ) )
    {
      $map_block['parent'] = $_options['parent'];
    }

    // Add to the flatmap
    $this->_flatmap[ $acf['key'] ] = $map_block;

    return $map_block;
  }

  /**
   * Map an entity's ACF fields. If it finds any sub_fields/layouts, it will map those recursively.
   *
   * @param Entity|Block|Layout $entity
   * @param array $acf_field
   * @param array $options
   * @return array
   */
  protected function map_block_data_acf_field ( $block_instance, $acf_field, $options = [] )
  {
    // Only map fields with keys
    if ( array_key_exists( 'key', $acf_field ) )
    {
      $map_field = [
        'is_field' => TRUE,
        'key' => $acf_field['key'],
        'name' => $acf_field['name'],
        'acf' => $acf_field,
      ];

      // Check if this is actually a block reference and if so ensure the block instance is linked
      if ( preg_match( '/^block_/', $acf_field['key'] ) ) {
        $map_field['is_block'] = TRUE;
        $map_field['block'] = $this->get_block_instance( $acf_field['name'] );
      }
      else
      {
        // @NOTE not sure if I need this?
        $map_field['block'] = $block_instance;
      }

      // Ensure parent is loaded into mapped field data
      if ( array_key_exists( 'parent', $options ) )
      {
        $map_field['parent'] = $options['parent'];
      }

      // Ensure field_group is loaded into mapped field data
      if ( array_key_exists( 'field_group', $options ) )
      {
        $map_field['field_group'] = $options['field_group'];
      }
      else
      {
        $map_field['field_group'] = $map_field['block']->get_field_group( $map_field['key'] );
      }

      // Add the type
      if ( array_key_exists( 'type', $acf_field ) )
      {
        $map_field['type'] = $acf_field['type'];
      }

      // Add the layout slug
      if ( array_key_exists( 'layout_slug', $options ) )
      {
        $map_field['layout_slug'] = $options['layout_slug'];
      }

      // Has layouts defined within
      if ( array_key_exists( 'layouts', $acf_field ) )
      {
        $map_field['layouts'] = [];
        $layout_options = array_merge( $options, [
          'parent' => $acf_field['key'],
          'layout_slug' => $options['layout_slug'],
        ] );
        foreach( $acf_field['layouts'] as $acf_layout_key => $acf_layout )
        {
          $map_field['layouts'][ $acf_layout_key ] = $this->map_block_data_acf_field( $map_field['block'], $acf_layout, $layout_options );
        }
      }
      // Has sub fields defined within
      else if ( array_key_exists( 'sub_fields', $acf_field ) )
      {
        $map_field['sub_fields'] = [];
        $sub_field_options = [
          'parent' => $map_field['key'],
          'layout_slug' => $options['layout_slug'],
        ];

        // Let's add a type if none set but has sub-fields. We can assume (rightfully?) that this is then a flexible
        // content layout
        if ( ! array_key_exists( 'type', $acf_field ) )
        {
          $map_field['type'] = 'flexible_content_layout';
        }

        foreach( $acf_field['sub_fields'] as $index => $acf_sub_field )
        {
          // Only map sub fields if they have a type and a key
          if ( array_key_exists( 'type', $acf_sub_field ) && array_key_exists( 'key', $acf_sub_field ) )
          {
            $map_field['sub_fields'][ $acf_sub_field['key'] ] = $this->map_block_data_acf_field( $map_field['block'], $acf_sub_field, $sub_field_options );
          }
        }
      }

      // Add field to the flatmap
      $this->_flatmap[ $map_field['key'] ] = $map_field;

      // Return the mapped field
      return $map_field;
    }
  }

  /**
   * Cache render data for a post.
   *
   * @param int|string|\WP_Post $post
   * @param string $key
   * @param array $data
   */
  public function cache_render_data( $post = NULL, $key, $data )
  {
    $post = get_post( $post );
    $post_cache_key = $post->post_type . '_' . $post->ID;

    // @TODO maybe do some persistent WP Object cache stuff here rather than just in memory?
    $this->_render_data[ $post_cache_key ][ $key ] = $data;
  }

  /**
   * Check if render data was cached and use that instead of having to re-map it all again
   *
   * @param int|string|\WP_Post $post
   * @param string $key
   * @returns null|array
   */
  public function get_cached_render_data( $post = NULL, $key = '' )
  {
    $post = get_post( $post );

    // Cache key
    $post_cache_key = $post->post_type . '_' . $post->ID;

    // Check if it is in the cache and return it if so
    if ( array_key_exists( $post_cache_key, $this->_render_data ) )
    {
      // Extra key was specified, so get the specific entry for it
      if ( ! empty( $key ) && array_key_exists( $key, $this->_render_data[ $post_cache_key ] ) )
      {
        return $this->_render_data[ $post_cache_key ][ $key ];
      }
      else
      {
        return $this->_render_data[ $post_cache_key ];
      }
    }

    return NULL;
  }

  /**
   * Get the post's builder render data for rendering a layout
   *
   * @param int|string|\WP_Post $post
   * @param string $key
   * @returns array
   * @throws \Exception
   */
  public function get_render_data ( $post = NULL, $key = '' )
  {
    $post = get_post( $post );
    $render_data = NULL;
    $layout_name = $this->get_active_layout( $post );

    // Default to getting the full active layout's render data
    if ( empty( $key ) )
    {
      $key = $layout_name;
    }

    // Check if there was any cached data first
    $render_data = $this->get_cached_render_data( $post, $key );

    // Already cached (i.e. non-null value), so return it
    if ( ! is_null( $render_data ) )
    {
      return $render_data;
    }

    // If no cached render data is available, we need to build it from the ACF data
    $render_data = [];
    if ( have_rows( $layout_name, $post ) )
    {
      while ( have_rows( $layout_name, $post ) )
      {
        $acf_layout_row_data = the_row();
        $block_name = get_row_layout();
        $block_data = $this->parse_block_acf_layout_row_data( $acf_layout_row_data, [
          'post' => $post,
          'builder' => $this->get_prop( 'name' ),
          'layout' => $layout_name,
          'block' => $block_name,
        ] );

        // Add the block data to the layout's render data
        $render_data[] = $block_data;
      }
    }

    // Save the layout's render data to the cache
    $this->cache_render_data( $post, $layout_name, $render_data );

    return $render_data;
  }

  /**
   * Take the block's ACF row data (retrieved via `the_row()`) and parse it to be structured in a way that we can feed it to
   * the views.
   *
   * @param $acf_layout_row_data
   * @return mixed
   */
  protected function parse_block_acf_layout_row_data ( $acf_layout_row_data, $options = [] )
  {
    $data = [];

    $_options = wp_parse_args( $options, [
      'post' => '',
      'builder' => $this->get_prop( 'name' ),
      'layout' => '',
      'block' => '',
    ] );

    // Test if its a registered Builder block
    $is_block = array_key_exists( 'acf_fc_layout', $acf_layout_row_data ) && array_key_exists( $acf_layout_row_data['acf_fc_layout'], $this->get_blocks() );

    // Process block layout
    if ( $is_block )
    {
      $_options['block'] = $acf_layout_row_data['acf_fc_layout'];
      foreach ( $acf_layout_row_data as $acf_field_key => $acf_field_value )
      {
        // Get the block's mapped field data
        if ( preg_match( '/^field_/i', $acf_field_key ) && array_key_exists( $acf_field_key, $this->_flatmap ) )
        {
          $field_data = $this->_flatmap[ $acf_field_key ];
          $field_value = $this->parse_block_acf_field_data( $acf_field_key, $acf_field_value, $_options );

          // Ensure field is organised into the block's field groups
          if ( array_key_exists( 'field_group', $field_data ) && ! empty( $field_data['field_group'] ) )
          {
            // Create the field group for the field to be organised into
            if ( ! array_key_exists( $field_data['field_group'], $data ) )
            {
              $data[ $field_data['field_group'] ] = [];
            }

            $data[ $field_data['field_group'] ][ $field_data['name'] ] = $field_value;

          // Support for generating other non-block layouts
          } else {
            $data[ $field_data['name'] ] = $field_value;
          }
        }
      }
    }

    // Output the special data object
    if ( ! empty( $data ) )
    {
      // Add builder meta
      $data['_builder'] = $_options;

      return $data;
    }

    // Otherwise just output as raw ACF data
    return $acf_layout_row_data;
  }

  /**
   * Parse a block's ACF field data to get its data mapped in a structure which can be referenced in rendering.
   *
   * This is a recursive function to cater for crawling through sub_fields, values and numbered arrays which may contain
   * further fields.
   *
   * @param string $acf_field_key
   * @param mixed $acf_field_value
   * @param array $options
   * @return mixed
   */
  public function parse_block_acf_field_data ( $acf_field_key, $acf_field_value, $options = [] )
  {
    $mode = 'field';
    $output = [];
    $return_output = $acf_field_value;

    $_options = wp_parse_args( $options, [
      'builder' => $this->get_prop( 'name' ),
      'layout' => '',
      'block' => '',
    ] );

    // Not a registered Builder block/field, so carry on...
    if ( ! array_key_exists( $acf_field_key, $this->_flatmap ) )
    {
      return $return_output;
    }

    // Get the registered block's field information
    $field_data = $this->_flatmap[ $acf_field_key ];

    // Check if this is a Builder block
    if ( is_array( $acf_field_value ) && array_key_exists( 'acf_fc_layout', $acf_field_value ) && array_key_exists( $acf_field_value['acf_fc_layout'], $this->loaded_blocks() ) )
    {
      $mode = 'block';
      $output = $this->parse_block_acf_layout_row_data( $acf_field_value, [
        'builder' => $_options['builder'],
        'layout' => $_options['layout'],
        'block' => $acf_field_value['acf_fc_layout'],
      ] );

    }
    // Otherwise process it like a regular field
    else
    {
      // Check if it is a special field which has an array value
      if ( is_array( $acf_field_value ) )
      {
        // Is a collection of fields (e.g. repeater, group)
        if ( $field_data['type'] === 'repeater' || $field_data['type'] === 'group' )
        {
          $mode = 'collection';
          $data_collection = [];
          foreach ( $acf_field_value as $collection_index => $collection_value )
          {
            foreach ( $collection_value as $collection_field_key => $collection_field_value )
            {
              $data_collection_item = $this->parse_block_acf_field_data( $collection_field_key, $collection_field_value, $_options );

              // Might be null
              if ( ! is_null( $data_collection_item ) )
              {
                $data_collection[] = $data_collection_item;
              }
            }
          }

          $output = $data_collection;
        }
        // Field has layouts
        else if ( $field_data['type'] === 'flexible_content' )
        {
          $mode = 'collection';
          foreach ( $acf_field_value as $layout_row_key => $layout_row_value )
          {
            $output[] = $this->parse_block_acf_layout_row_data( $layout_row_value, $_options );
          }

        }
        // Field has sub-fields
        else if ( $field_data['type'] === 'flexible_content_layout' || array_key_exists( 'sub_fields', $field_data ) )
        {
          $mode = 'flexible_content_layout';
          foreach ( $acf_field_value as $acf_sub_field_key => $acf_sub_field_value )
          {
            $sub_field_data = $this->_flatmap[ $acf_sub_field_key ];
            $data_sub_field_value = $this->parse_block_acf_field_data( $acf_sub_field_key, $acf_sub_field_value, $_options );
            $output[ $sub_field_data['name'] ] = $data_sub_field_value;
          }
        }
      }

      // Some fields need some extra work done to have proper typed value, like fields with an array/object return_format
      if ( $mode === 'field' && array_key_exists( 'post', $_options ) )
      {
        $return_output = check_acf_field_to_format_value( $acf_field_key, $acf_field_value, $field_data['acf'], $_options['post'] );
      }
    }

    // Output was generated, so ensure it is the returned output
    if ( $mode !== 'field' )
    {
      // Return as a collection
      if ( $mode === 'collection' )
      {
        $return_output = $output;
      }
      // Return as an array with a named key for block fields
      else
      {
        $return_output = [
          $field_data['name'] => $output,
        ];
      }
    }

    return $return_output;
  }

  /**
   * Render a post's layout
   *
   * @param int|string|\WP_Post $post
   * @param array $options
   * @returns string
   */
  public function render_layout( $post = NULL, $options = [] )
  {
    $post = get_post( $post );
    $layout_view_file = '';
    $rendered_layout = [];

    // Don't show anything if it is disabled
    if ( ! $this->is_enabled( $post ) )
    {
      return;
    }

    // This is the name that the builder uses to refer to the cached data
    $builder_layout_name = $this->get_active_layout( $post );

    // This is the name that we use to check for the layout's view
    $layout_name = str_replace( 'acfpb_' . $this->get_key() . '_', '', $builder_layout_name );

    // We get the render data based on the builder's layout name
    $render_data = $this->get_render_data( $post, $builder_layout_name );

    // Check if a layout view exists, if so use that to render the layout
    $layout_view_file = $this->locate_layout_view( $layout_name );
    if ( ! is_null( $layout_view_file ) )
    {
      $layout_data = [
        '_builder' => [
          'builder' => $this->get_prop( 'name' ),
          'version' => LVL99_ACF_PAGE_BUILDER,
          'path' => LVL99_ACF_PAGE_BUILDER_PATH,
          'post' => $post,
          'layout' => $layout_name,
          'layout_slug' => $builder_layout_name,
          'view' => $layout_view_file,
          'options' => $options,
        ],
        'blocks' => $render_data,
      ];

      // Render the twig view
      if ( preg_match( '/\.twig$/i', $layout_view_file ) && $this->get_setting( 'twig' ) )
      {
        $rendered_layout[] = $this->render_twig_view( $layout_view_file, $layout_data );
      }
      // Render the php view
      else if ( preg_match( '/\.php$/i', $layout_view_file ) )
      {
        $rendered_layout[] = $this->render_php_view( $layout_view_file, $layout_data );
      }
    }

    // No view file for the layout found/specified or rendered, so we do basic rendering
    if ( empty( $rendered_layout ) )
    {
      // Yeehaw, there's data
      if ( ! empty( $render_data ) )
      {
        foreach( $render_data as $block_index => $block_data )
        {
          // Meta data for builder is stored in `_builder` property in data array
          $rendered_block = $this->render_block( $post, [
            'post' => $post,
            'layout' => $layout_name,
            'block' => $block_data['_builder']['block'],
            'data' => $block_data,
          ] );

          // Only render this block if it is not empty
          if ( ! empty( $rendered_block ) )
          {
            // Extra stuff to render before each block
            if ( array_key_exists( 'before_block', $options ) )
            {
              $rendered_layout[] = $options['before_block'];
            }

            // Add the rendered block to the layout's output
            $rendered_layout[] = $rendered_block;

            // Extra stuff to render after each block
            if ( array_key_exists( 'after_block', $options ) )
            {
              $rendered_layout[] = $options['after_block'];
            }
          }
        }
      }
    }

    // Put something before the rendered layout
    if ( array_key_exists( 'before_layout', $options ) )
    {
      array_unshift( $rendered_layout,  $options['after_layout'] );
    }

    // Put something after the rendered layout
    if ( array_key_exists( 'after_layout', $options ) )
    {
      $rendered_layout[] = $options['after_layout'];
    }

    // Join all the pieces together
    $rendered_layout = join( "\n", $rendered_layout );

    // Echo the rendered template
    if ( array_key_exists( 'output', $options ) && $options['output'] === 'echo' )
    {
      echo $rendered_layout;
    }

    return $rendered_layout;
  }

  /**
   * Render a post's block
   *
   * @TODO revise this
   *
   * @param int|string|\WP_Post $post
   * @param array $options
   * @returns string
   * @throws \Exception
   */
  public function render_block ( $post = NULL, $options = [] )
  {
    $post = get_post( $post );
    $block_view_file = '';
    $rendered_block = '';

    // Get the layout/block name
    $key = ( array_key_exists( 'key', $options ) ? $options['key'] : '' );
    $layout_name = ( array_key_exists( 'layout', $options ) ? $options['layout'] : $this->get_active_layout( $post ) );
    $block_name = ( array_key_exists( 'block', $options ) ? $options['block'] : '' );

    // Get the entry in the flatmap to populate layout/block info if key given
    if ( ! empty( $key ) && array_key_exists( $key, $this->_flatmap ) )
    {
      $layout_name = $this->_flatmap[ $key ][ 'layout' ]->get_prop( 'name' );
      $block_name = $this->_flatmap[ $key ][ 'block' ]->get_prop( 'name' );
    }

    // Update options to pass through
    $options['post'] = $post;
    $options['layout'] = $layout_name;
    $options['block'] = $block_name;

    try {
      $block_view_file = $this->locate_block_view( $block_name, $layout_name );
    }
    catch (\Exception $e)
    {
      error_log( $e->getMessage() );
      return '<!-- LVL99 ACF Page Builder - Missing view for block "' . $block_name . '" -->';
    }

    return $this->render_view( $block_view_file, $options );
  }

  /**
   * Render the view
   *
   * @param string $view_file
   * @param array $options
   * @returns string
   */
  public function render_view ( $view_file, $options = [] )
  {
    $rendered_view = '';
    $post = get_post( array_key_exists( 'post', $options ) ? $options['post'] : NULL );
    $key = ( array_key_exists( 'key', $options ) ? $options['key'] : '' );
    $layout_name = ( array_key_exists( 'layout', $options ) ? $options['layout'] : $this->get_active_layout( $post ) );
    $block_name = ( array_key_exists( 'block', $options ) ? $options['block'] : '' );
    $data = ( array_key_exists( 'data', $options ) ? $options['data'] : [] );

    // Get the entry in the flatmap to populate layout/block info
    if ( ! empty( $key ) && array_key_exists( $key, $this->_flatmap ) )
    {
      $layout_name = $this->_flatmap[ $key ][ 'layout' ]->get_prop( 'name' );
      $block_name = $this->_flatmap[ $key ][ 'block' ]->get_prop( 'name' );
    }

    // Attach builder information to the render data
    if ( ! array_key_exists( '_builder', $data ) )
    {
      $data['_builder'] = [];
    }

    // Overwrite _builder stuff with things related to this render pass
    $data['_builder'] = array_merge( $data['_builder'], [
      'builder' => $this->get_prop( 'name' ),
      'version' => LVL99_ACF_PAGE_BUILDER,
      'path' => LVL99_ACF_PAGE_BUILDER_PATH,
      'post' => $post,
      'key' => $key,
      'layout' => $layout_name,
      'block' => $block_name,
      'data' => $data,
      'view' => $view_file,
      'options' => $options,
    ] );

    // Render Twig template
    if ( preg_match( '/\.twig$/i', $view_file ) && $this->get_setting( 'twig' ) )
    {
      $rendered_view = $this->render_twig_view( $view_file, $data );
    }
    // Render PHP template
    else if ( preg_match( '/\.php$/i', $view_file ) )
    {
      $rendered_view = $this->render_php_view( $view_file, $data );
    }
    // Just in case file can't be found...
    else
    {
      $error_message = 'LVL99 ACF Page Builder - No view found ';
      error_log( $error_message );
      $rendered_view = '<!-- ' . $error_message . ' -->';
    }

    // Echo the rendered template
    if ( array_key_exists( 'output', $options ) && $options['output'] === 'echo' )
    {
      echo $rendered_view;
    }

    return $rendered_view;
  }

  /**
   * Render a Twig view
   *
   * @param $file
   * @param $data
   * @returns string
   * @protected
   */
  protected function render_twig_view ( $file, $data = [] )
  {
    $rendered_view = '';

    // Twig is initialised and the file exists
    if ( $this->get_setting( 'twig' ) && ! is_null( $this->_twig ) && file_exists( $file ) )
    {
      $rendered_view = $this->_twig->render( $file, $data );
    }
    // Attempt to render with the PHP version (if it exists)
    else
    {
      // If twig renderer not available, attempt to render via PHP
      $file = preg_replace( '/\.twig$/i', '.php', $file );

      // Only render if it exists
      if ( file_exists( $file ) )
      {
        $rendered_view = $this->render_php_view( $file, $data );
      }
      // Otherwise output an error via HTML comment message
      else
      {
        $safe_file = str_replace( ABSPATH, '', $file );
        $error_message = 'LVL99 ACF Page Builder - Missing view file:';
        error_log( $error_message . ' "' . $file . '"' );
        $rendered_view = '<!-- ' . $error_message . ' "' . $safe_file . '" -->';
      }
    }

    return $rendered_view;
  }

  /**
   * Render a PHP view
   *
   * @param $file
   * @param $data
   * @returns string
   * @protected
   */
  protected function render_php_view ( $file, $data = [] )
  {
    $rendered_view = '';

    if ( file_exists( $file ) )
    {
      // This is so user doesn't overwrite the filename to include when we extract the variables
      extract( $data, EXTR_SKIP );

      ob_start();
      include $file;
      $rendered_view = ob_get_contents();
      ob_end_clean();
    }
    else
    {
      $safe_file = str_replace( ABSPATH, '', $file );
      $error_message = 'LVL99 ACF Page Builder - Missing view file:';
      error_log( $error_message . ' "' . $file . '"' );
      $rendered_view = '<!-- ' . $error_message . ' "' . $safe_file . '" -->';
    }

    return $rendered_view;
  }

  /**
   * Setup the filters that the Page Builder can apply to
   */
  protected function setup_filters ()
  {
    add_filter( 'the_content', [ $this, 'filter_the_content' ], 10, 1 );
    add_filter( 'the_content_feed', [ $this, 'filter_the_content_feed' ], 10, 2 );
    add_filter( 'get_the_excerpt', [ $this, 'filter_get_the_excerpt' ], 10, 2 );
    add_filter( 'the_excerpt_rss', [ $this, 'filter_the_excerpt_rss' ], 10, 1 );
  }

  /**
   * Fetch the global post's content. If a post has Page Builder enabled, then this will bypass WordPress's
   * `the_content` filter.
   *
   * There's a lot of issues with this approach. #1 is that because this filter doesn't specify the post of which to
   * fetch the content (the `get_the_content` doesn't have a hookable filter either) it's possible someone could
   * pass other information to apply the filter with that could be mistakenly overwritten by this one.
   *
   * Poor form, WordPress...
   *
   * @hook the_content
   * @param $content
   * @return string
   */
  public function filter_the_content ( $content = '' )
  {
    $post = get_post();

    // Return the rendered layout content if Page Builder is enabled
    if ( $this->is_enabled( $post ) )
    {
      // If post password required and it doesn't match the cookie.
      if ( post_password_required( $post ) )
      {
        return get_the_password_form( $post );
      }

      $content = $this->render_layout( $post );

      // What is this strange line that they have in `the_content` ?
      $content = str_replace( ']]>', ']]&gt;', $content );

      return $content;
    }
    // Otherwise just return the regular content
    else
    {
      return $content;
    }
  }

  /**
   * Get the content for displaying within a feed.
   *
   * Ideally this should get only the textual content of the rendered layout.
   *
   * @hook the_content_feed
   * @param string $content
   * @param string $feed_type
   * @param int|string|\WP_Post
   * @return string
   */
  public function filter_the_content_feed ( $content = '', $feed_type = '', $post = NULL )
  {
    $post = get_post( $post );

    // Return the rendered layout content if Page Builder is enabled
    if ( is_a( $post, 'WP_Post' ) && $this->is_enabled( $post ) )
    {
      $content = $this->render_layout( $post );
      // $content = str_replace( ']]>', ']]&gt;', $content );
      return clean_excess_whitespace( strip_tags( $content, '' ) );
    }
    // WordPress
    else
    {
      return $content;
    }
  }

  /**
   * Fetch a (specified) post's excerpt. If a post has Page Builder enabled, then this will bypass WordPress's
   * `get_the_excerpt` filter.
   *
   * Thankfully this one specifies a post from which to get the excerpt from...
   *
   * @hook the_excerpt
   * @param string $excerpt
   * @param int|\WP_Post $post
   * @returns string
   */
  public function filter_get_the_excerpt ( $excerpt = '', $post = NULL )
  {
    $post = get_post( $post );

    // If post password required and it doesn't match the cookie.
    if ( post_password_required( $post ) )
    {
      return __( 'There is no excerpt because this is a protected post.' );
    }

    // Return the rendered layout content if Page Builder is enabled
    if ( is_a( $post, 'WP_Post' ) && $this->is_enabled( $post ) )
    {
      $excerpt = $this->render_layout( $post );
      // $excerpt = str_replace( ']]>', ']]&gt;', $excerpt );
      return clean_excess_whitespace( strip_tags( $excerpt, '' ) );
    }
    // Return the WordPress default excerpt
    else
    {
      return $excerpt;
    }
  }

  /**
   * Fetch the post's excerpt. If a post has Page Builder enabled, then this will bypass WordPress's
   * `the_excerpt_rss` filter.
   *
   * @hook the_excerpt_rss
   * @param string $excerpt
   * @returns string
   */
  public function filter_the_excerpt_rss ( $excerpt = '', $post = NULL )
  {
    $post = get_post( $post );

    // Return the rendered layout excerpt if Page Builder is enabled
    if ( is_a( $post, 'WP_Post' ) && $this->is_enabled( $post ) )
    {
      $excerpt = $this->filter_get_the_excerpt( $excerpt, $post );
      // $excerpt = str_replace( ']]>', ']]&gt;', $excerpt );
      return $excerpt;
    }
    // Return the WordPress excerpt
    else
    {
      return $excerpt;
    }
  }
}
