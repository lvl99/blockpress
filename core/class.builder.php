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
   * The map of the blocks/fields in relation to the ACF config
   *
   * @var array
   * @protected
   */
  protected $_map = [];
  protected $_flatmap = [];

  /**
   * Twig environment for rendering templates
   */
  protected $_twig_env = NULL;

  /**
   * Twig renderer
   */
  protected $_twig = NULL;

  /**
   * Cached array of located views
   *
   * @var array
   * @private
   */
  private $_views = [
    /*
    "$layout_name_$block_name" => "$view_path",
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
      // The supported post types that can show the Page Builder
      'post_types' => [
        'post',
        'page',
      ],

      // Enable Twig rendering
      'twig' => TRUE,

      // The paths to where Twig views could be located ordered by highest priority first
      'twig_views' => [
        LVL99_ACF_PAGE_BUILDER_PATH . '/views/blocks',
        LVL99_ACF_PAGE_BUILDER_PATH . '/views/layouts',
      ],

      // Extra options to pass to the Twig environment
      'twig_options' => [
        'debug' => TRUE,
        'cache' => get_temp_dir() . '/cache/lvl99-acfpb',
      ],

      // Enable the_content and the_excerpt filters to affect the display of the layouts
      'use_render_hooks' => TRUE,
    ] );

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
    $this->settings['_loading'] = FALSE;
    $this->settings['_loaded'] = TRUE;

    // Initialise the loaded blocks/layouts
    $this->settings['_initialising'] = TRUE;
    $this->initialise_blocks();
    $this->initialise_layouts();

    // Load and initialise Twig
    if ( $this->settings['twig'] )
    {
      // Load in Composer dependencies with Twig only if Twig isn't already loaded
      if ( ! class_exists( '\\Twig_Environment' ) )
      {
        $autoloader = LVL99_ACF_PAGE_BUILDER_PATH . '/vendor/autoload.php';
        if ( version_compare( PHP_VERSION, '5.3.0' ) < 0 ) {
          $autoloader = LVL99_ACF_PAGE_BUILDER_PATH . '/vendor/autoload_52.php';
        }
        require_once $autoloader;
      }

      // Use the custom loader to be able to load from absolute paths
      require_once LVL99_ACF_PAGE_BUILDER_PATH . '/core/twig/class.twig-template-loader.php';

      // Instantiate Twig
      $this->_twig_env = new TwigTemplateLoader();
      $this->_twig = new \Twig_Environment( $this->_twig_env, $this->get_setting( 'twig_options' ) );

      // Add in the debug stuff
      if ( array_key_exists( 'debug', $this->get_setting( 'twig_options' ) ) && $this->get_setting( 'twig_options' )['debug'] )
      {
        $this->_twig->addExtension( new \Twig_Extension_Debug() );
      }
    }

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
          $_loaded_block['instance'] = new $_loaded_block_class();

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
        $_loaded_layout['instance'] = new $_loaded_layout_class();

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
   * Generate the code for ACF to recognise the custom fields
   *
   * @protected
   * @returns array
   */
  protected function generate_acf ()
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
      'key' => $key . '_enabled',
      'name' => 'acfpb_' . $key . '_enabled',
      'label' => 'Use ' . $this->get_prop( 'label' ),
      'ui' => 1,
    ] );
    $acfpb_builder_fields[] = $acfpb_builder_enabled;

    // For each layout generate the ACF flexible content field for the layout
    foreach( $layouts as $layout_name => $layout_instance )
    {
      $acfpb_builder_layouts[] = $layout_instance->generate_acf( 'acfpb_' . $key . '_layout' );
    }

    // Create a select element to choose which layout to use
    $acfpb_builder_select_choices = [];
    foreach ( $acfpb_builder_layouts as $index => $acfpb_layout )
    {
      $acfpb_builder_select_choices[ 'acfpb_' . $key . '_layout_' . $acfpb_layout['name'] ] = $acfpb_layout['label'];
    }
    $acfpb_builder_select_layout = generate_acf_field_select( [
      'key' => $key . '_layout',
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
            'value' => 'acfpb_' . $key . '_layout_' . $acfpb_layout['name'],
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
   * Register an entity within the builder's map along with its generated ACF config.
   *
   * The map is a way for the builder to map a block's data/schema to the generate ACF fields. This is used when
   * getting the ACF meta data from a WP_Post object and then mapping it to the Page Builder's data structure.
   *
   * @param Block $block_instance
   * @param string $generate_acf_key
   * @param array $acf
   */
  public function register_block_in_map ( $block_instance, $acf, $options = [] )
  {
    $_options = wp_parse_args( $options, [
      'layout' => '',
    ] );

    $block_field_groups = [ 'acfpb_block_content', 'acfpb_block_customise', 'acfpb_block_configure' ];
    $block_fields = [
      'content' => [],
      'customise' => [],
      'configure' => [],
    ];
    $current_field_group = 'content';

    foreach ( $acf['sub_fields'] as $index => $_field )
    {
      // Change to the next builder field group
      if ( $_field['type'] === 'tab' && in_array( $_field['name'], $block_field_groups ) )
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

        $block_fields[ $current_field_group ][ $_field['key'] ] = $this->map_block_acf_field( $block_instance, $_field, $field_options );
      }
    }

    // The mapped block
    $map_block = [
      'acf_key' => $acf['key'],
      'layout' => $_options['layout'],
      'block' => $block_instance,
      'fields' => $block_fields,
    ];

    // Add to the map
    $this->_map[ $acf['key'] ] = $map_block;
    $this->_flatmap[ $acf['key'] ] = $map_block;
  }

  /**
   * Map the block's ACF fields. If it finds any sub_fields/layouts, it will map those recursively.
   *
   * @param Block $block_instance
   * @param array $acf_field
   * @return array
   */
  protected function map_block_acf_field ( $block_instance, $acf_field, $options = [] )
  {
    // Only map fields with keys
    if ( array_key_exists( 'key', $acf_field ) )
    {
      $map_field = [
        'key' => $acf_field['key'],
        'name' => $acf_field['name'],
      ];

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

      // Add the type
      if ( array_key_exists( 'type', $acf_field ) )
      {
        $map_field['type'] = $acf_field['type'];
      }

      // Has layouts defined within
      if ( array_key_exists( 'layouts', $acf_field ) )
      {
        $map_field['layouts'] = [];
        $layout_options = array_merge( $options, [
          'parent' => $acf_field['key'],
        ] );
        foreach( $acf_field['layouts'] as $acf_layout_key => $acf_layout )
        {
          $map_field['layouts'][ $acf_layout_key ] = $this->map_block_acf_field( $block_instance, $acf_layout, $layout_options );
        }
      }
      // Has sub fields defined within
      else if ( array_key_exists( 'sub_fields', $acf_field ) )
      {
        // Check if this is actually a block reference and if so ensure the block instance is linked
        if ( preg_match( '/^block_/', $acf_field['key'] ) ) {
          $map_field['block'] = $this->get_block_instance( $acf_field['name'] );
        }

        // Let's add a type if none set but has sub-fields. We can assume (rightfully?) that this is then a flexible
        // content layout
        if ( ! array_key_exists( 'type', $acf_field ) )
        {
          $map_field['type'] = 'flexible_content_layout';
        }

        $map_field['sub_fields'] = [];
        $sub_field_options = [
          'parent' => $map_field['key'],
        ];
        foreach( $acf_field['sub_fields'] as $index => $acf_sub_field )
        {
          // Only map sub fields if they have a type and a key
          if ( array_key_exists( 'type', $acf_sub_field ) && array_key_exists( 'key', $acf_sub_field ) )
          {
            $map_field['sub_fields'][ $acf_sub_field['key'] ] = $this->map_block_acf_field( $block_instance, $acf_sub_field, $sub_field_options );
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

      return $this->render_layout( $post );
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
      $rendered_layout = $this->render_layout( $post );
      return strip_tags( $rendered_layout, '' );
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
      $rendered_layout = $this->render_layout( $post );
      return strip_tags( $rendered_layout, '' );
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
      return $this->filter_get_the_excerpt( $excerpt, $post );
    }
    // Return the WordPress excerpt
    else
    {
      return $excerpt;
    }
  }

  /**
   * Get a loaded block instance
   *
   * @param string $block_name
   * @return array $loaded_block
   * @throws \Exception
   */
  public function get_block_instance ( $block_name )
  {
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
      $block_names = array_keys( lvl99_acf_page_builder()->get_blocks() );
    }

    // Get each named block instance
    foreach ( $block_names as $block_name )
    {
      $_blocks[ $block_name ] = $this->get_block_instance( $block_name );
    }

    return $_blocks;
  }

  /**
   * Get a loaded layout instance
   *
   * @param string $layout_name
   * @return array $loaded_layout
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
   * Locate a block's template
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
  public function locate_block_template( $block_name, $layout_name = '' )
  {
    $view_slug = ( ! empty( $layout_name ) ? $layout_name . '_' . $block_name : $block_name );

    // Check if location is already cached
    if ( array_key_exists( $view_slug, $this->_views ) )
    {
      return $this->_views[ $view_slug ];
    }

    // Generate potential locations that the view could exist in
    $view_dirs = [
      // @TODO might need to support child themes?
      get_template_directory() . '/views/blocks',
      get_template_directory() . '/views',
      get_template_directory(),
      LVL99_ACF_PAGE_BUILDER_PATH . '/views/blocks',
    ];
    $view_filenames = [];

    // If layout name not empty, add in extra folder paths to check
    if ( ! empty( $layout_name ) )
    {
      array_unshift( $view_dirs, get_template_directory() . '/views/' . $layout_name );
      array_unshift( $view_dirs, get_template_directory() . '/views/' . $layout_name . '/blocks' );
      array_unshift( $view_dirs, get_template_directory() . '/views/layouts/' . $layout_name );
      array_unshift( $view_dirs, get_template_directory() . '/views/layouts/' . $layout_name . '/blocks' );
    }

    // Reference twig filenames
    if ( $this->get_setting( 'twig' ) )
    {
      $view_filenames[] = $block_name . '.twig';
    }
    $view_filenames[] = $block_name . '.php';

    // Add extra filenames to check if the layout name was specified as well
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
   * The the post's builder render data for rendering
   *
   * This will format the data by block in a structure that mimics how the fields are arranged in the block field
   * groups/tabs (by content, customise, configure)
   *
   * @param int|string|\WP_Post $post
   * @param string $field_key
   * @returns array
   * @throws \Exception
   */
  public function get_render_data ( $post = NULL, $key = '' )
  {
    $post = get_post( $post );
    $render_data = [];

    // Get the active layout's data
    $post_active_layout = $this->get_active_layout();
    $acf_layout_data = get_field( $post_active_layout, $post );

    // No key given, so generate a full object of the block's render data
    if ( empty( $key ) )
    {

    }
    else
    {
      // Check if the block key is within the flatmap
      if ( array_key_exists( $key, $this->_flatmap ) )
      {

      }
      else
      {
        throw new \Exception( 'LVL99 ACF Page Builder: block key "' . $key . '" does not exist in the Builder\'s flatmap' );
      }
    }

    // @TODO Arrange the post meta data by how it is structured in the block class
    var_dump( $acf_layout_data );

    return $render_data;
  }

  /**
   * Get the key of an
   *
   * @param $key
   */
  protected function process_render_data ( $key, $post = NULL )
  {
    $post = get_post( $post );

    if ( array_key_exists( $key, $this->_flatmap ) )
    {

    }
  }

  /**
   * Take the block's ACF row data (retrieved via `the_row()`) and parse it to be structured in a way that we can feed it to
   * the views.
   *
   * @param $acf_layout_row_data
   * @return mixed
   */
  protected function parse_block_acf_layout_row_data ( $acf_layout_row_data )
  {
    $data = [
      'content' => [],
      'configure' => [],
      'customise' => [],
    ];

    foreach( $acf_layout_row_data as $field_key => $field_value )
    {
      if ( preg_match( '/^field_/i', $field_key ) )
      {
        $field_data = $this->_flatmap[ $field_key ];
        $data[ $field_data['field_group'] ][ $field_data['name'] ] = $field_value;
      }
    }

    return $data;
  }

  /**
   * Render a post's layout
   *
   * @param int|string|\WP_Post $post
   // * @param string $layout_name
   * @param array $options
   * @returns string
   */
  public function render_layout( $post, /* $layout_name = '', */ $options = [] )
  {
    $rendered_layout = [];

    // Don't show anything if it is disabled
    if ( ! $this->is_enabled( $post ) )
    {
      return;
    }

    // Get the layout name if not already detected
    // if ( empty( $layout_name ) )
    // {
      $layout_name = str_replace( 'acfpb_' . $this->get_key() . '_layout_', '', $this->get_active_layout( $post ) );
    // }

    $blocks = $this->get_blocks();

    // Get the layouts for this post
    if ( have_rows( $layout_name, $post ) )
    {
      while ( have_rows( $layout_name, $post ) )
      {
        $acf_layout_row_data = the_row();
        $block_name = get_row_layout();
        $block_data = $this->parse_block_acf_layout_row_data( $acf_layout_row_data );

        if ( array_key_exists( $block_name, $blocks ) )
        {
          $rendered_block = $this->render_block( $post, [
            'layout' => $layout_name,
            'block' => $block_name,
            'data' => $block_data,
          ] );

          // Only render this block if it is not empty
          if ( ! empty( $rendered_block ) )
          {
            // @TODO maybe this can be put into the Twig/template view
            if ( array_key_exists( 'before_block', $options ) )
            {
              $rendered_layout[] = $options['before_block'];
            }

            $rendered_layout[] = $rendered_block;

            // @TODO maybe this can be put into the Twig/template view
            if ( array_key_exists( 'after_block', $options ) )
            {
              $rendered_layout[] = $options['after_block'];
            }
          }
        }
      }
    }

    if ( array_key_exists( 'before_layout', $options ) )
    {
      // @TODO maybe this can be put into the Twig/template view
      array_unshift( $rendered_layout,  $options['after_layout'] );
    }

    if ( array_key_exists( 'after_layout', $options ) )
    {
      // @TODO maybe this can be put into the Twig/template view
      $rendered_layout[] = $options['after_layout'];
    }

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
   * @param int|string|\WP_Post $post
   * @param array $options
   * @returns string
   */
  public function render_block ( $post, $options = [] )
  {
    $data = [];
    $template = '';
    $rendered_block = '';
    $key = ( array_key_exists( 'key', $options ) ? $options['key'] : '' );
    $layout_name = ( array_key_exists( 'layout', $options ) ? $options['layout'] : '' );
    $block_name = ( array_key_exists( 'block', $options ) ? $options['block'] : '' );

    // Get the entry in the flatmap to populate layout/block info
    if ( ! empty( $key ) && array_key_exists( $key, $this->_flatmap ) )
    {
      $layout_name = $this->_flatmap[ $key ][ 'layout' ]->get_prop( 'name' );
      $block_name = $this->_flatmap[ $key ][ 'block' ]->get_prop( 'name' );
    }

    // Data already given via the options
    if ( array_key_exists( 'data', $options ) )
    {
      $data = $options['data'];
    }
    // Extract the block's data from the post
    else
    {
      $data = $this->get_render_data( $post, [
        'key' => $key,
        'layout' => $layout_name,
        'block' => $block_name,
      ] );
    }

    try {
      $template = lvl99_acf_page_builder()->locate_block_template( $block_name, $layout_name );
    }
    catch (\Exception $e)
    {
      error_log( $e->getMessage() );
      $rendered_block = '<!-- LVL99 ACF Page Builder - Missing view for block "' . $this->get_prop( 'name' ) . '" -->';
    }

    // Attach generic information about the builder to the render data
    $data['_builder'] = [
      'debug' => [
        'version' => LVL99_ACF_PAGE_BUILDER,
        'path' => LVL99_ACF_PAGE_BUILDER_PATH,
        'template' => $template,
        'post' => $post,
        'block_name' => $block_name,
        'layout_name' => $layout_name,
        'options' => $options,
      ],
    ];

    // A template was found
    if ( ! empty( $template ) )
    {
      // Render Twig template
      if ( preg_match( '/\.twig$/i', $template ) && $this->get_setting( 'twig' ) )
      {
        $rendered_block = $this->render_twig_template( $template, $data );
      }
      // Render PHP template
      else if ( preg_match( '/\/.php$/i', $template ) )
      {
        $rendered_block = $this->render_php_template( $template, $data );
      }
      else
      {
        $error_message = 'LVL99 ACF Page Builder - No valid renderer enabled for for block "' . $this->get_prop( 'name' ) . '"';
        error_log( $error_message );
        $rendered_block = '<!-- ' . $error_message . ' -->';
      }
    }

    // Echo the rendered template
    if ( array_key_exists( 'output', $options ) && $options['output'] === 'echo' )
    {
      echo $rendered_block;
    }

    return $rendered_block;
  }

  /**
   * Render a Twig template
   *
   * @param $file
   * @param $data
   * @returns string
   * @protected
   */
  protected function render_twig_template ( $file, $data = [] )
  {
    // Twig is initialised
    if ( ! is_null( $this->_twig ) )
    {
      return $this->_twig->render( $file, $data );
    }
    // Attempt to render with the PHP version (if it exists)
    else
    {
      // If twig renderer not available, attempt to render via PHP
      $file = preg_replace( '/\.twig$/i', '.php', $file );

      // Only render if it exists
      if ( file_exists( $file ) )
      {
        return $this->render_php_template( $file, $data );
      }
      // Otherwise output an error via HTML comment message
      else
      {
        $safe_file = str_replace( ABSPATH, '', $file );
        $error_message = 'LVL99 ACF Page Builder - Missing view file:';
        error_log( $error_message . ' "' . $file . '"' );
        return '<!-- ' . $error_message . ' "' . $safe_file . '" -->';
      }
    }
  }

  /**
   * Render a PHP template
   *
   * @param $file
   * @param $data
   * @returns string
   * @protected
   */
  protected function render_php_template ( $file, $data = [] )
  {
    ob_start();
    include $file;
    $rendered_template = ob_get_contents();
    ob_end_clean();

    return $rendered_template;
  }
}
