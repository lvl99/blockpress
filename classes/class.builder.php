<?php
/**
 * BlockPress
 */

namespace LVL99\BlockPress;

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
  public $label = 'BlockPress';

  /**
   * @var string
   */
  public $description = 'Use BlockPress to create custom page/post content layouts';

  /**
   * The BlockPress settings
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
   * Collection of validated (they exist) view folders
   *
   * @type array
   */
  protected $_view_folders = [];

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
    // @TODO refactor to WP Options API?
    $this->settings = wp_parse_args( $options, [
      /**
       * The supported post types that can show BlockPress in the backend
       *
       * @hook LVL99\BlockPress\Builder\default_settings\post_types
       * @param array
       * @returns array
       */
      'post_types' => apply_filters( 'LVL99\BlockPress\Builder\default_settings\post_types', [
        'post',
        'page',
      ] ),

      /**
       * Enable `the_content` and `the_excerpt` filters to affect the display of the layouts
       *
       * IMPORTANT: lots of plugins use `the_content` filter other than display the post's content. Use with caution...
       *
       * @hook LVL99\BlockPress\Builder\default_settings\use_render_hooks
       * @param bool
       * @returns bool
       */
      'use_render_hooks' => apply_filters( 'LVL99\BlockPress\Builder\default_settings\use_render_hooks', FALSE ),

      /**
       * Use cache features when processing builder data and rendering layouts/blocks
       * You can use the cache busting query var `?acfpb_builder_reload_cache=1` to bust the cache
       *
       * @hook LVL99\BlockPress\Builder\default_settings\use_cache
       * @param bool
       * @returns bool
       */
      'use_cache' => apply_filters( 'LVL99\BlockPress\Builder\default_settings\use_cache', ( defined( 'WP_CACHE' ) ? WP_CACHE : TRUE ) ),
    ] );

    /**
     * Change the settings after loading defaults
     *
     * @hook LVL99\BlockPress\Builder\settings
     * @param array
     * @returns array
     */
    $this->settings = apply_filters( 'LVL99\BlockPress\Builder\settings', $this->settings );

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
     * @hook LVL99\BlockPress\Builder\load_blocks
     * @param array $load_blocks An associative array of all the blocks to load into the builder
     * @returns array
     */
    $_load_blocks = apply_filters( 'LVL99\BlockPress\Builder\load_blocks', [] );
    $_loaded_blocks = [];

    foreach ( $_load_blocks as $block_name => $block_data )
    {
      if ( file_exists( $block_data['path'] ) )
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
    }

    // After loaded and created all the block instances we can initialise them
    foreach ( $this->loaded_blocks as $block_name => $block_data )
    {
      $block_data['instance']->initialise();
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
     * @filter LVL99\BlockPress\Builder\load_layouts
     * @param array $load_layouts An associative array of all the layouts to load into the builder
     * @returns array
     */
    $_load_layouts = apply_filters( 'LVL99\BlockPress\Builder\load_layouts', [] );
    $_loaded_layouts = [];

    foreach ( $_load_layouts as $layout_name => $layout_data )
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

    // After loaded and created all the layout instances we can initialise them
    foreach ( $this->loaded_layouts as $layout_name => $layout_data )
    {
      $layout_data['instance']->initialise();
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
   * Test the view folders to see which ones are valid
   *
   * @protected
   */
  protected function load_view_folders ()
  {
    /**
     * The potential locations that views could exist in
     *
     * @hook LVL99\BlockPress\Builder\load_view_folders
     * @param array $view_dirs
     * @returns array
     */
    $view_folders = apply_filters( 'LVL99\BlockPress\Builder\load_view_folders', [
      // @TODO might need to support child themes?
      'layout' => [
        get_template_directory() . '/views/layouts',
        get_template_directory() . '/views',
      ],
      'block' => [
        get_template_directory() . '/views/blocks',
        get_template_directory() . '/views',
      ],
    ] );

    // Sanitise user return value
    if ( ! is_array( $view_folders ) )
    {
      $view_folders = [
      	'layout' => [],
        'block' => [],
      ];
    }

    // Always add plugin's folders to fall back on
    $view_folders['layout'][] = LVL99_BLOCKPRESS_PATH . '/views/layouts';
    $view_folders['layout'][] = LVL99_BLOCKPRESS_PATH . '/views';
    $view_folders['block'][] = LVL99_BLOCKPRESS_PATH . '/views/blocks';
    $view_folders['block'][] = LVL99_BLOCKPRESS_PATH . '/views';

    // Check that the paths are valid and exist
    $valid_view_folders = [
      'layout' => [],
      'block' => [],
    ];
    foreach ( $view_folders as $view_type => $view_dirs )
    {
      foreach ( $view_dirs as $view_dir )
      {
        if ( file_exists( $view_dir ) )
        {
          $valid_view_folders[ $view_type ][] = $view_dir;
        }
      }
    }

    // Save the validated view folders into the instance
    $this->_view_folders = $valid_view_folders;
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

    // Allow BlockPress to only be visible for these post types
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

    // Create a true_false field to mark whether to use BlockPress or not
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

    // Generate the full ACF group config for BlockPress
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
    if ( isset( $this->loaded_layouts[$layout_name] ) /* @perf array_key_exists( $layout_name, $this->loaded_layouts ) */ )
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

    if ( isset( $this->loaded_blocks[$block_name] ) /* @perf array_key_exists( $block_name, $this->loaded_blocks ) */ )
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
    if ( isset( $this->settings[$setting_name] ) /* @perf array_key_exists( $setting_name, $this->settings ) */ )
    {
      return $this->settings[ $setting_name ];
    }

    return $default_value;
  }

  /**
   * Check if BlockPress is enabled for a post
   *
   * @param int|string|\WP_Post $post
   * @returns bool
   */
  public function is_enabled ( $post = NULL )
  {
    $post = get_wp_post( $post );
    $key = $this->get_key();
    $is_enabled = get_field( 'acfpb_' . $key . '_enabled', $post );
    return $is_enabled;
  }

  /**
   * Get BlockPress's active layout for a post
   *
   * @param int|string|\WP_Post $post
   * @returns string
   */
  public function get_active_layout ( $post = NULL )
  {
    $post = get_wp_post( $post );
    $key = $this->get_key();
    $active_layout = get_field( 'acfpb_' . $key . '_layout', $post );
    return $active_layout;
  }

  /**
   * Locate a layout's view file
   *
   * The plugin will look in the active theme's views folder first, then in its own views folder.
   *
   * @param string $layout_name
   * @returns null|string
   */
  public function locate_layout_view( $layout_name )
  {
    $view_slug = $layout_name;

    // Check if location is already cached
    if ( isset( $this->_views[$view_slug] ) /* @perf array_key_exists( $view_slug, $this->_views ) */ )
    {
      return $this->_views[ $view_slug ];
    }

    // Generate potential locations that the view could exist in
    $view_dirs = $this->_view_folders['layout'];
    $view_filenames = [
      $layout_name . '.php'
    ];

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
   * @param string $block_name
   * @param string $layout_name
   * @returns string
   * @throws \Exception
   */
  public function locate_block_view( $block_name, $layout_name = '' )
  {
    $view_slug = ( ! empty( $layout_name ) ? $layout_name . '_' . $block_name : $block_name );

    // Check if location is already cached
    if ( isset( $this->_views[$view_slug] ) /* @perf array_key_exists( $view_slug, $this->_views ) */ )
    {
      return $this->_views[ $view_slug ];
    }

    // Generate potential locations that the view could exist in
    $view_dirs = $this->_view_folders['block'];
    $view_filenames = [
      $block_name . '.php'
    ];

    // Add extra filenames to check if the layout name was specified as well
    // Supports if you want to have a different view for a block within a specific layout
    if ( ! empty( $layout_name ) )
    {
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
   * getting the ACF meta data from a WP_Post object and then mapping it to BlockPress's data structure for
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
      if ( isset( $_field['key'] ) /* @perf array_key_exists( 'key', $_field ) */ )
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
    if ( isset( $_options['parent'] ) /* @perf array_key_exists( 'parent', $_options ) */ )
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
    if ( isset( $acf_field['key'] ) /* @perf array_key_exists( 'key', $acf_field ) */ )
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
      if ( isset( $options['field_group'] ) /* @perf array_key_exists( 'field_group', $options ) */ )
      {
        $map_field['field_group'] = $options['field_group'];
      }
      else
      {
        $map_field['field_group'] = $map_field['block']->get_field_group( $map_field['key'] );
      }

      // Add the type
      if ( isset( $acf_field['type'] ) /* @perf array_key_exists( 'type', $acf_field ) */ )
      {
        $map_field['type'] = $acf_field['type'];
      }

      // Add the layout slug
      if ( isset( $options['layout_slug'] ) /* @perf array_key_exists( 'layout_slug', $options ) */ )
      {
        $map_field['layout_slug'] = $options['layout_slug'];
      }

      // Has layouts defined within
      if ( isset( $acf_field['layouts'] ) /* @perf array_key_exists( 'layouts', $acf_field ) */ )
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
      else if ( isset( $acf_field['sub_fields'] ) /* @perf array_key_exists( 'sub_fields', $acf_field ) */ )
      {
        $map_field['sub_fields'] = [];
        $sub_field_options = [
          'parent' => $map_field['key'],
          'layout_slug' => $options['layout_slug'],
        ];

        // Let's add a type if none set but has sub-fields. We can assume (rightfully?) that this is then a flexible
        // content layout
        if ( ! isset( $acf_field['type'] ) /* @perf array_key_exists( 'type', $acf_field ) */ )
        {
          $map_field['type'] = 'flexible_content_layout';
        }

        foreach( $acf_field['sub_fields'] as $index => $acf_sub_field )
        {
          // Only map sub fields if they have a type and a key
          if ( isset( $acf_sub_field['type'] ) /* @perf array_key_exists( 'type', $acf_sub_field ) */ && isset( $acf_sub_field['key'] ) /* @perf array_key_exists( 'key', $acf_sub_field ) */ )
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
   * Check if a layout/block's data has been mapped
   *
   * @param $key
   * @return bool
   */
  public function check_is_mapped( $key )
  {
    return isset( $this->_flatmap[ $key ] ) /* @perf array_key_exists( $key, $this->_flatmap ) */;
  }

  /**
   * Get the layout/block's mapped data by key
   *
   * @param $key
   * @return array|null
   */
  public function get_mapped_data ( $key )
  {
    if ( $this->check_is_mapped( $key ) )
    {
      return $this->_flatmap[ $key ];
    }

    return NULL;
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
    $post = get_wp_post( $post );
    $post_cache_key = $post->post_type . '_' . $post->ID;

    $data['_cached_render_data'] = [
      'post_type' => $post->post_type,
      'post_id' => $post->ID,
      'post_cache_key' => $post_cache_key,
      'key' => $key,
    ];

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
    $post = get_wp_post( $post );

    // Cache key
    $post_cache_key = $post->post_type . '_' . $post->ID;

    // Check if it is in the cache and return it if so
    if ( isset( $this->_render_data[$post_cache_key] ) /* @perf array_key_exists( $post_cache_key, $this->_render_data ) */ )
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
    $post = get_wp_post( $post );
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
        $index = get_row_index();
        $block_name = get_row_layout();
        $block_data = $this->parse_block_acf_layout_row_data( $acf_layout_row_data, [
          'post' => $post,
          'key' => $key,
          'layout' => $layout_name,
          'block' => $block_name,
          'index' => $index,
          'builder' => $this->get_prop( 'name' ),
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
      'key' => '',
      'post' => '',
      'builder' => $this->get_prop( 'name' ),
      'layout' => '',
      'block' => '',
      'parent' => '',
      'index' => 0,
    ] );

    // Test if its a registered Builder block
    $is_block = ! empty( $acf_layout_row_data ) && isset( $acf_layout_row_data['acf_fc_layout'] ) /* @perf array_key_exists( 'acf_fc_layout', $acf_layout_row_data ) */ && isset( $this->get_blocks()[$acf_layout_row_data['acf_fc_layout']] ) /* @perf array_key_exists( $acf_layout_row_data['acf_fc_layout'], $this->get_blocks() ) */;

    // Process block layout
    if ( $is_block )
    {
      $_options['block'] = $acf_layout_row_data['acf_fc_layout'];
      foreach ( $acf_layout_row_data as $acf_field_key => $acf_field_value )
      {
        // Get the block's mapped field data
        if ( preg_match( '/^field_/i', $acf_field_key ) && $this->check_is_mapped( $acf_field_key ) )
        {
          $field_data = $this->get_mapped_data( $acf_field_key );
          $field_value = $this->parse_block_acf_field_data( $acf_field_key, $acf_field_value, $_options );

          // Save a reference to the builder's key within the fetched row data
          $data['_field_key'] = $acf_field_key;

          // Ensure field is organised into the block's field groups
          if ( isset( $field_data['field_group'] ) /* @perf array_key_exists( 'field_group', $field_data ) */ && ! empty( $field_data['field_group'] ) )
          {
            // Create the field group for the field to be organised into
            if ( ! isset( $data[$field_data['field_group']] ) /* @perf array_key_exists( $field_data['field_group'], $data ) */ )
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
      'index' => 0,
    ] );

    // Not a registered Builder block/field, so carry on...
    if ( ! isset( $this->_flatmap[$acf_field_key] ) /* @perf array_key_exists( $acf_field_key, $this->_flatmap ) */ )
    {
      return $return_output;
    }

    // Get the registered block's field information
    $field_data = $this->_flatmap[ $acf_field_key ];

    // Check if this is a Builder block first
    if ( is_array( $acf_field_value ) && isset( $acf_field_value['acf_fc_layout'] ) /* @perf array_key_exists( 'acf_fc_layout', $acf_field_value ) */ && isset( $this->loaded_blocks()[$acf_field_value['acf_fc_layout']] ) /* @perf array_key_exists( $acf_field_value['acf_fc_layout'], $this->loaded_blocks() ) */ )
    {
      $mode = 'block';
      $output = $this->parse_block_acf_layout_row_data( $acf_field_value, [
        'builder' => $_options['builder'],
        'layout' => $_options['layout'],
        'block' => $acf_field_value['acf_fc_layout'],
        'index' => $_options['index'],
      ] );

    }
    // Otherwise process it like a regular field
    else
    {
      // Check if it is a special field which has an array value
      if ( is_array( $acf_field_value ) )
      {
        // Field is a group of more fields (has sub-fields)
        if ( $field_data['type'] === 'group' )
        {
          $mode = 'group';
          $data_group = [];

          foreach ( $acf_field_value as $group_field_key => $group_field_value )
          {
            $group_field_data = $this->_flatmap[ $group_field_key ];
            $data_group[ $group_field_data['name'] ] = $this->parse_block_acf_field_data( $group_field_key, $group_field_value, $_options );
          }

          $output = $data_group;

        }
        // Field is a repeater
        else if ( $field_data['type'] === 'repeater' )
        {
          $mode = 'collection';
          $data_collection = [];

          // The field value will be an array that contains groups of fields
          foreach ( $acf_field_value as $collection_index => $collection_value )
          {
            // Process each item in the repeater
            if ( is_array( $collection_value ) )
            {
              $nested_index = 0;
              $data_collection_item = [];

              foreach ( $collection_value as $item_field_key => $item_field_value )
              {
                $item_field_data = $this->_flatmap[ $item_field_key ];
                $data_collection_item[ $item_field_data['name'] ] = $this->parse_block_acf_field_data( $item_field_key, $item_field_value, array_merge( $_options, [
                  // @TODO not sure if this is needed
                  'index' => $nested_index,
                ] ) );
                $nested_index++;
              }

              $data_collection[] = $data_collection_item;
            }
          }

          $output = $data_collection;
        }
        // Field has layouts
        else if ( $field_data['type'] === 'flexible_content' )
        {
          $mode = 'collection';
          $nested_index = 0;
          foreach ( $acf_field_value as $layout_row_key => $layout_row_value )
          {
            $output[] = $this->parse_block_acf_layout_row_data( $layout_row_value, array_merge( $_options, [
              'index' => $nested_index,
            ] ) );

            $nested_index++;
          }

        }
        // Field has sub-fields
        else if ( $field_data['type'] === 'flexible_content_layout' || isset( $field_data['sub_fields'] ) /* @perf array_key_exists( 'sub_fields', $field_data ) */ )
        {
          $mode = 'flexible_content_layout';
          $nested_index = 0;
          foreach ( $acf_field_value as $acf_sub_field_key => $acf_sub_field_value )
          {
            $sub_field_data = $this->_flatmap[ $acf_sub_field_key ];
            $data_sub_field_value = $this->parse_block_acf_field_data( $acf_sub_field_key, $acf_sub_field_value, array_merge( $_options, [
              'index' => $nested_index,
            ] ) );

            $output[ $sub_field_data['name'] ] = $data_sub_field_value;

            $nested_index++;
          }
        }
      }

      // Some fields need some extra work done to have proper typed value, like fields with an array/object return_format
      if ( $mode === 'field' && isset( $_options['post'] ) /* @perf array_key_exists( 'post', $_options ) */ )
      {
        $return_output = check_acf_field_to_format_value( $acf_field_key, $acf_field_value, $field_data['acf'], $_options['post'] );
      }
    }

    // Output was generated, so ensure it is the returned output
    if ( $mode !== 'field' )
    {
      // Return as a collection or array
      if ( $mode === 'collection' || $mode === 'group' )
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
    $post = get_wp_post( $post );
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

    // Check the cache first to see if the layout was already rendered for this post
    $pre_cache_key = $this->generate_pre_cache_key( [
      'post' => $post,
      'layout' => $layout_name,
    ] );
    $cache_key = md5( $pre_cache_key );
    $cached_layout = $this->get_cached_view( $cache_key );
    if ( ! empty( $cached_layout ) )
    {
      return $cached_layout;
    }

    // If not found in the cache then we build!

    // We get the render data based on the builder's layout name
    $render_data = $this->get_render_data( $post, $builder_layout_name );

    // Check if a layout view exists, if so use that to render the layout
    $layout_view_file = $this->locate_layout_view( $layout_name );
    if ( ! is_null( $layout_view_file ) )
    {
      $layout_data = [
        '_builder' => [
          'builder' => $this->get_prop( 'name' ),
          'version' => LVL99_BLOCKPRESS,
          'path' => LVL99_BLOCKPRESS_PATH,
          'post' => $post,
          'layout' => $layout_name,
          'layout_slug' => $builder_layout_name,
          'view' => $layout_view_file,
          'options' => $options,
          'settings' => $this->settings,
        ],
        'blocks' => $render_data,
      ];

      // Render the layout view
      $rendered_layout[] = $this->render_view( $layout_view_file, [
        'post' => $post,
        'layout' => $layout_name,
        'data' => $layout_data,
      ] );
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
            'index' => $block_index,
            'data' => $block_data,
            // @TODO allow parent cache key to be set so _builder can refer to parent
            'parent' => $builder_layout_name,
            'parent_pre_cache_key' => $pre_cache_key,
            'parent_cache_key' => $cache_key,
          ] );

          // Only render this block if it is not empty
          if ( ! empty( $rendered_block ) )
          {
            // Extra stuff to render before each block
            if ( isset( $options['before_block'] ) /* @perf array_key_exists( 'before_block', $options ) */ )
            {
              $rendered_layout[] = $options['before_block'];
            }

            // Add the rendered block to the layout's output
            $rendered_layout[] = $rendered_block;

            // Extra stuff to render after each block
            if ( isset( $options['after_block'] ) /* @perf array_key_exists( 'after_block', $options ) */ )
            {
              $rendered_layout[] = $options['after_block'];
            }
          }
        }
      }
    }

    // Put something before the rendered layout
    if ( isset( $options['before_layout'] ) /* @perf array_key_exists( 'before_layout', $options ) */ )
    {
      array_unshift( $rendered_layout,  $options['after_layout'] );
    }

    // Put something after the rendered layout
    if ( isset( $options['after_layout'] ) /* @perf array_key_exists( 'after_layout', $options ) */ )
    {
      $rendered_layout[] = $options['after_layout'];
    }

    // Join all the pieces together
    $rendered_layout = join( "\n", $rendered_layout );

    // Echo the rendered template
    if ( isset( $options['output'] ) /* @perf array_key_exists( 'output', $options ) */ && $options['output'] === 'echo' )
    {
      echo $rendered_layout;
    }

    return $rendered_layout;
  }

  /**
   * Render a post's block
   *
   * @param int|string|\WP_Post $post
   * @param array $options
   * @returns string
   * @throws \Exception
   */
  public function render_block ( $post = NULL, $options = [] )
  {
    $block_view_file = '';
    $rendered_block = '';

    // Get the layout/block name
    $post = get_wp_post( $post );
    $key = ( isset( $options['key'] ) /* @perf array_key_exists( 'key', $options ) */ ? $options['key'] : '' );
    $layout_name = ( isset( $options['layout'] ) /* @perf array_key_exists( 'layout', $options ) */ ? $options['layout'] : $this->get_active_layout( $post ) );
    $parent = ( isset( $options['parent'] ) /* @perf array_key_exists( 'parent', $options ) */ ? $options['parent'] : '' );
    $index = ( isset( $options['index'] ) /* @perf array_key_exists( 'index', $options ) */ ? $options['index'] : '' );
    $block_name = ( isset( $options['block'] ) /* @perf array_key_exists( 'block', $options ) */ ? $options['block'] : '' );

    // Get the entry in the flatmap to populate layout/block/parent info if key given
    if ( ! empty( $key ) && isset( $this->_flatmap[$key] ) /* @perf array_key_exists( $key, $this->_flatmap ) */ )
    {
      $block_data = $this->_flatmap[ $key ];
      $layout_name = $block_data[ 'layout' ]->get_prop( 'name' );
      $block_name = $block_data[ 'block' ]->get_prop( 'name' );

      // Get the block's parent (if nested its key of parent block, or name of layout)
      if ( isset( $block_data['parent'] ) /* @perf array_key_exists( 'parent', $block_data ) */ )
      {
        $parent = $block_data[ 'parent' ];
      }
    }

    // Update options to pass through
    $options['post'] = $post;
    $options['layout'] = $layout_name;
    $options['block'] = $block_name;
    $options['index'] = $index;
    $options['parent'] = $parent;

    try {
      $block_view_file = $this->locate_block_view( $block_name, $layout_name );
    }
    catch (\Exception $e)
    {
      error_log( $e->getMessage() );
      return '<!-- LVL99 BlockPress - Missing view for block "' . $block_name . '" -->';
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
    $pre_cache_key = '';
    $cache_key = '';
    $rendered_view = '';

    // Get the post
    $post = get_wp_post( isset( $options['post'] ) /* @perf array_key_exists( 'post', $options ) */ ? $options['post'] : NULL );

    // A specific key to the layout/block
    $key = ( isset( $options['key'] ) /* @perf array_key_exists( 'key', $options ) */ ? $options['key'] : '' );

    // The name of the layout being rendered
    if ( isset( $options['layout_name'] ) /* @perf array_key_exists( 'layout_name', $options ) */ )
    {
      $layout_name = $this->get_layout_name( $options['layout_name'] );
    }
    else
    {
      $layout_name = $this->get_layout_name( isset( $options['layout'] ) /* @perf array_key_exists( 'layout', $options ) */ ? $options['layout'] : $this->get_active_layout( $post ) );
    }

    // The key (or name) of the parent layout/block
    $parent = ( isset( $options['parent'] ) /* @perf array_key_exists( 'parent', $options ) */ ? $options['parent'] : $layout_name );

    // The block layout index count in the layout row
    $index = ( isset( $options['index'] ) /* @perf array_key_exists( 'index', $options ) */ ? $options['index'] : '' );

    // The name of the block being rendered
    if ( isset( $options['block_name'] ) /* @perf array_key_exists( 'block_name', $options ) */ )
    {
      $block_name = $this->get_block_name( $options['block_name'] );
    }
    else
    {
      $block_name = $this->get_block_name( isset( $options['block'] ) /* @perf array_key_exists( 'block', $options ) */ ? $options['block'] : '' );
    }

    $data = ( isset( $options['data'] ) /* @perf array_key_exists( 'data', $options ) */ ? $options['data'] : [] );

    // Convert layout_slug to layout_name
    if ( strpos( $layout_name, $this->get_prop( 'name' ) ) >= 0 )
    {
      $layout_name = str_replace( 'acfpb_' . $this->get_prop( 'name' ) . '_', '', $layout_name );
    }

    // Set specific options for render_view
    $_options = wp_parse_args( $options, [
      'overwrite_cache' => $this->check_cache_busting(),
    ] );

    // Get the entry in the flatmap to populate layout/block info
    if ( ! empty( $key ) && isset( $this->_flatmap[$key] ) /* @perf array_key_exists( $key, $this->_flatmap ) */ )
    {
      // Get the layout name being rendered
      $layout_name = $this->_flatmap[ $key ][ 'layout' ]->get_prop( 'name' );

      // Get the parent key, if rendering nested block
      if ( array_key_exists( 'parent', $this->_flatmap[ $key ] ) )
      {
        $parent = $this->_flatmap[ $key ][ 'parent' ];
      }

      // Get the block name being rendered
      if ( array_key_exists( 'block', $this->_flatmap[ $key ] ) )
      {
        $block_name = $this->_flatmap[ $key ][ 'block' ]->get_prop( 'name' );
      }
    }

    // Check if view was cached
    if ( $this->get_setting( 'use_cache' ) || ! $_options['overwrite_cache'] )
    {
      $pre_cache_key = $this->generate_pre_cache_key( [
        'post' => $post,
        'key' => $key,
        'layout_name' => $layout_name,
        'parent' => $parent,
        'block_name' => $block_name,
        'index' => $index,
      ] );

      // 4. Get the md5 hash
      $cache_key = md5( $pre_cache_key );

      // Check if rendered view is in the cache
      $cached_view = $this->get_cached_view( $cache_key );

      // Set the rendered view if there was something found
      if ( ! empty( $cached_view ) )
      {
        $rendered_view = $cached_view;
      }
    }

    // Render the view
    if ( empty( $rendered_view )  )
    {
      // Attach builder information to the render data
      if ( ! isset( $data['_builder'] ) /* @perf array_key_exists( '_builder', $data ) */ )
      {
        $data['_builder'] = [];
      }

      // Overwrite _builder stuff with things related to this render pass
      $data['_builder'] = array_merge( $data['_builder'], [
        'builder' => $this->get_prop( 'name' ),
        'version' => LVL99_BLOCKPRESS,
        'path' => LVL99_BLOCKPRESS_PATH,
        'post' => $post,
        'key' => $key,
        'layout' => $layout_name,
        'block' => $block_name,
        'index' => $index,
        'data' => $data,
        'view' => $view_file,
        'options' => $options,
        'pre_cache_key' => $pre_cache_key,
        'cache_key' => $cache_key,
        'overwrite_cache' => $_options['overwrite_cache'],
      ] );

      // Render PHP template
      if ( preg_match( '/\.php$/i', $view_file ) )
      {
        $rendered_view = $this->render_php_view( $view_file, $data );

        // Cache the view
        $this->cache_view( $cache_key, $rendered_view );
      }
      // Just in case file can't be found...
      else
      {
        $error_message = 'LVL99 BlockPress - No view found ';
        error_log( $error_message );
        $rendered_view = '<!-- ' . $error_message . ' -->';
      }
    }

    // Echo the rendered template
    if ( isset( $options['output'] ) /* @perf array_key_exists( 'output', $options ) */ && $options['output'] === 'echo' )
    {
      echo $rendered_view;
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
      // @debug
      // var_dump( get_defined_vars() );
      include $file;
      $rendered_view = ob_get_contents();
      ob_end_clean();
    }
    else
    {
      $safe_file = str_replace( ABSPATH, '', $file );
      $error_message = 'LVL99 BlockPress - Missing view file:';
      error_log( $error_message . ' "' . $file . '"' );
      $rendered_view = '<!-- ' . $error_message . ' "' . $safe_file . '" -->';
    }

    return $rendered_view;
  }

  /**
   * Generates a human-readable unique cache key for the layout/block
   *
   * @param array $options
   * @return string
   */
  protected function generate_pre_cache_key ( $options = [] )
  {
    // Get the post
    $post = get_wp_post( isset( $options['post'] ) /* @perf array_key_exists( 'post', $options ) */ ? $options['post'] : NULL );

    // A specific key to the layout/block
    $key = ( isset( $options['key'] ) /* @perf array_key_exists( 'key', $options ) */ ? $options['key'] : '' );

    // The name of the layout being rendered
    if ( isset( $options['layout_name'] ) /* @perf array_key_exists( 'layout_name', $options ) */ )
    {
      $layout_name = $this->get_layout_name( $options['layout_name'] );
    }
    else
    {
      $layout_name = $this->get_layout_name( isset( $options['layout'] ) /* @perf array_key_exists( 'layout', $options ) */ ? $options['layout'] : $this->get_active_layout( $post ) );
    }

    // The key (or name) of the parent layout/block
    $parent = ( isset( $options['parent'] ) /* @perf array_key_exists( 'parent', $options ) */ ? $options['parent'] : $layout_name );

    // The block layout index count in the layout row
    $index = ( isset( $options['index'] ) /* @perf array_key_exists( 'index', $options ) */ ? $options['index'] : '' );

    // The name of the block being rendered
    if ( isset( $options['block_name'] ) /* @perf array_key_exists( 'block_name', $options ) */ )
    {
      $block_name = $this->get_block_name( $options['block_name'] );
    }
    else
    {
      $block_name = $this->get_block_name( isset( $options['block'] ) /* @perf array_key_exists( 'block', $options ) */ ? $options['block'] : '' );
    }

    // Get the entry in the flatmap to populate layout/block info
    if ( ! empty( $key ) && isset( $this->_flatmap[$key] ) /* @perf array_key_exists( $key, $this->_flatmap ) */ )
    {
      $entity_data = $this->_flatmap[ $key ];

      // Get the layout name being rendered
      $layout_name = $this->get_layout_name( $entity_data[ 'layout' ] );

      // Get the parent key, if rendering nested block
      if ( isset( $entity_data['parent'] ) /* @perf array_key_exists( 'parent', $entity_data ) */ )
      {
        $parent = $entity_data[ 'parent' ];
      }

      // Get the block name being rendered
      if ( isset( $entity_data['block'] ) /* @perf array_key_exists( 'block', $entity_data ) */ )
      {
        $block_name = $this->get_block_name( $entity_data[ 'block' ] );
      }
    }

    //
    // Build the pre-cache key
    //

    // 1. Set the post ID, builder name and layout name
    $pre_cache_key = $post->ID . '_' . $this->get_key() . '_' . $layout_name;

    // 2. Set the parent
    if ( ! empty( $parent ) && $parent !== $layout_name )
    {
      $pre_cache_key .= '_' . $parent;
    }

    // 3. Set the index and block
    if ( ! empty( $block_name ) )
    {
      // The index of the block within the layout/parent block
      if ( ! is_null( $index ) && $index !== '' )
      {
        $pre_cache_key .= '_' . $index;
      }

      $pre_cache_key .=  '_' . $block_name;
    }

    return $pre_cache_key;
  }

  /**
   * Cache a rendered view
   *
   * @param string $cache_key
   */
  protected function cache_view ( $cache_key, $rendered_view )
  {
    // @TODO support WP Object cache, or maybe some kind of HTML cache
    if ( ! empty( $cache_key ) && $this->get_setting( 'use_cache' ) )
    {
      $this->_views[ $cache_key ] = $rendered_view;
    }
  }

  /**
   * Get a cached rendered view
   *
   * @param string $cache_key
   * @return null|string
   */
  protected function get_cached_view ( $cache_key )
  {
    // @TODO support WP Object cache, or maybe some kind of HTML cache
    if ( ! empty( $cache_key ) && isset( $this->_views[$cache_key] ) /* @perf array_key_exists( $cache_key, $this->_views ) */ )
    {
      $output = '<!-- BEGIN LVL99 BlockPress - Cached View: ' . $cache_key . ' -->' . "\n";
      $output .= $this->_views[ $cache_key ] . "\n";
      $output .= '<!-- END LVL99 BlockPress - Cached View: ' . $cache_key . ' -->' . "\n";
      return $output;
    }

    return NULL;
  }

  /**
   * Setup the filters that BlockPress can apply to
   */
  protected function setup_filters ()
  {
    add_filter( 'the_content', [ $this, 'filter_the_content' ], 10, 1 );
    add_filter( 'the_content_feed', [ $this, 'filter_the_content_feed' ], 10, 2 );
    add_filter( 'get_the_excerpt', [ $this, 'filter_get_the_excerpt' ], 10, 2 );
    add_filter( 'the_excerpt_rss', [ $this, 'filter_the_excerpt_rss' ], 10, 1 );
  }

  /**
   * Fetch the global post's content. If a post has BlockPress enabled, then this will bypass WordPress's
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
    $post = get_wp_post();

    // Return the rendered layout content if BlockPress is enabled
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
    $post = get_wp_post( $post );

    // Return the rendered layout content if BlockPress is enabled
    if ( is_a( $post, 'WP_Post' ) && $this->is_enabled( $post ) )
    {
      $content = $this->render_layout( $post );

      // Strip shortcodes
      $content = strip_shortcodes( $content );

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
   * Fetch a (specified) post's excerpt. If a post has BlockPress enabled, then this will bypass WordPress's
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
    $post = get_wp_post( $post );

    // If post password required and it doesn't match the cookie.
    if ( post_password_required( $post ) )
    {
      return __( 'There is no excerpt because this is a protected post.' );
    }

    // Return the rendered layout content if BlockPress is enabled
    if ( empty( $excerpt ) && is_a( $post, 'WP_Post' ) && $this->is_enabled( $post ) )
    {
      // Check if already has an excerpt, if so use that
      if ( ! empty( $post->post_excerpt ) )
      {
        $excerpt = wp_trim_excerpt( $post->post_excerpt );
      }
      // Check if post content is set, if so use that
      else if ( ! empty( $post->post_content ) )
      {
        $excerpt = wp_trim_excerpt( $post->post_content );
      }
      // Otherwise fallback on the rendered layout
      // @note this can have issues if no excerpt or no content found and it gets stuck in a recursive loop...
      else
      {
        $excerpt = $this->render_layout( $post );
      }

      // Strip shortcodes
      $excerpt = strip_shortcodes( $excerpt );

      // $excerpt = str_replace( ']]>', ']]&gt;', $excerpt );
      return wp_trim_excerpt( clean_excess_whitespace( strip_tags( $excerpt, '' ) ) );
    }
    // Return the WordPress default excerpt
    else
    {
      return $excerpt;
    }
  }

  /**
   * Fetch the post's excerpt. If a post has BlockPress enabled, then this will bypass WordPress's
   * `the_excerpt_rss` filter.
   *
   * @hook the_excerpt_rss
   * @param string $excerpt
   * @returns string
   */
  public function filter_the_excerpt_rss ( $excerpt = '', $post = NULL )
  {
    $post = get_wp_post( $post );

    // Return the rendered layout excerpt if BlockPress is enabled
    if ( is_a( $post, 'WP_Post' ) && $this->is_enabled( $post ) )
    {
      $excerpt = $this->filter_get_the_excerpt( $excerpt, $post );
      return $excerpt;
    }
    // Return the WordPress excerpt
    else
    {
      return $excerpt;
    }
  }

  /**
   * Check if the user is attempting to bust the cache, i.e. re-render view
   *
   * You can bust the cache for the current page's layouts/blocks by setting a query var with the name of the builder,
   * e.g. `?acfpb_builder_reload_cache=1`
   *
   * @return bool
   */
  public function check_cache_busting ()
  {
    // Allow only users who can manage options to bust the cache
    if ( ! current_user_can( 'manage_options' ) )
    {
      return FALSE;
    }

    $cache_buster = 'acfpb_' . $this->get_prop( 'name' ) . '_reload_cache';
    return ( isset( $_GET[ $cache_buster ] ) ? TRUE : FALSE );
  }

  /**
   * Get the layout's name
   *
   * @param string|Layout $layout
   * @return string
   * @throws \Error
   */
  public function get_layout_name ( $layout )
  {
    // Get the name from the layout instance
    if ( is_a( $layout, 'Layout' ) )
    {
      return $layout->get_prop( 'name' );

    }
    // Otherwise get the name from a string
    else if ( is_string( $layout ) )
    {
      if ( strpos( $layout, 'actpb_' . $this->get_prop( 'name' ) . '_' ) >= 0 )
      {
        $layout = str_replace( 'actpb_' . $this->get_prop( 'name' ) . '_', '', $layout );
      }

      return $layout;
    }

    throw new \Error( 'No name detected for the given layout' );
  }

  /**
   * Get the block's name
   *
   * @param string|Block $block
   * @return string
   * @throws \Error
   */
  public function get_block_name ( $block )
  {
    // Get the name from the block instance
    if ( is_a( $block, 'Block' ) )
    {
      return $block->get_prop( 'name' );

    }
    // Otherwise get the name from a string
    else if ( is_string( $block ) )
    {
      return $block;
    }

    throw new \Error( 'No name detected for the given block' );
  }
}
