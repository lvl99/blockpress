<?php
/**
 * BlockPress - Entity
 *
 * Represents a generic entity in BlockPress
 */

namespace LVL99\BlockPress;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class Entity {
  /**
   * A unique identifier for the entity
   *
   * @var array
   */
  public $key = [];

  /**
   * A name for the entity
   *
   * @var string
   */
  public $name = '';

  /**
   * A list of all the blocks this entity requires
   *
   * @var array
   */
  public $blocks = [];

  /**
   * An internal collection of the registered blocks for this entity
   *
   * @var array
   * @protected
   */
  protected $_blocks = [];

  /**
   * A list of all the layouts this entity requires
   *
   * @var array
   */
  public $layouts = [];

  /**
   * An internal collection of the registered layouts for this entity
   *
   * @var array
   * @protected
   */
  protected $_layouts = [];

  /**
   * A list of the fields this entity displays
   *
   * @var array
   */
  public $fields = [];

  /**
   * An internal collection of the registered fields for this entity
   *
   * @var array
   * @protected
   */
  protected $_fields = [];

  /**
   * Rules on where this entity can be used.
   *
   * When blocks are nested within layouts and other blocks, these rules can further allow or restrict what gets nested
   * where.
   *
   * @var array
   */
  public $rules = [
    /**
     * Specify which block names this entity is compatible with, or can be nested within.
     *
     * You can set '*' to let this entity be compatible with all blocks.
     *
     * Otherwise you can set the specific name of each block this one is compatible with.
     *
     * @type string|array
     */
    'block' => '*',

    /**
     * Specify which layout names this entity is compatible with, or can be nested within.
     *
     * You can set '*' to let this entity be compatible with all layouts.
     *
     * Otherwise you can set the specific name of each layout this one is compatible with.
     *
     * @type string|array
     */
    'layout' => '*',
  ];

  /**
   * Get a property on the entity
   *
   * @param string $prop_name
   * @param mixed $default_value
   * @returns mixed
   */
  public function get_prop ( $prop_name, $default_value = NULL )
  {
    if ( property_exists( $this, $prop_name ) )
    {
      return $this->$prop_name;
    }

    return $default_value;
  }

  /**
   * Get the entity's key to use
   *
   * @return string
   */
  public function get_key ()
  {
    $_key = $this->key;

    if ( ! empty( $_key ) && is_array( $_key ) )
    {
      $_key = join( ':', $_key );
    }

    return sanitise_key( $_key );
  }

  /**
   * Get a version of the entity's key with the given key prepended
   *
   * @param string $key
   * @return string
   */
  public function get_prepended_key ( $key = '' )
  {
    $_key = $this->get_key();

    // If a key was given, ensure it's prepended
    if ( ! empty( $key ) )
    {
      $_key = sanitise_key( $key ) . ':' . $_key;
    }

    return $_key;
  }

  /**
   * Get a version of the entity's key with the given key appended
   *
   * @param string $key
   * @return string
   */
  public function get_appended_key ( $key = '' )
  {
    $_key = $this->get_key();

    // If a key was given, ensure it's appended
    if ( ! empty( $key ) )
    {
      $_key .= ':' . sanitise_key( $key );
    }

    return $_key;
  }

  /**
   * Add a key to the entity's key
   *
   * @param string $key
   * @return string
   */
  public function add_key ( $key = '' )
  {
    if ( ! empty( $key ) )
    {
      array_push( $this->key, $key );
    }

    return $this->get_key();
  }

  /**
   * Set the start key for the entity
   *
   * @param string $key
   * @return string
   */
  public function set_key ( $key = '' )
  {
    if ( ! empty( $key ) )
    {
      if ( is_array( $key ) )
      {
        $this->key = $key;
      } else {
        // String has special delimiter
        if ( strpos( $key, ':' ) > -1 )
        {
          $this->key = explode( ':', $key );
        }
        else
        {
          $this->key = [ $key ];
        }
      }

    // Default to the entity's name
    } else {
      $this->key = [ $this->name ];
    }

    return $this->get_key();
  }

  /**
   * Register a block to use within the entity
   *
   * @param $block_name
   * @param array $options
   * @return Block $block_instance
   */
  public function register_block ( $block_name, $options = [] )
  {
    $_options = wp_parse_args( $options, [
      'builder' => blockpress(),
    ]);

    $this->_blocks[ $block_name ] = $_options['builder']->get_block_instance( $block_name );
  }

  /**
   * Get all of the blocks registered in the Entity
   *
   * @return array
   */
  public function get_blocks ()
  {
    return $this->_blocks;
  }

  /**
   * Get a single block attached to the entity
   *
   * @param string $block_name
   * @return array
   * @throws \Exception
   */
  public function get_block ( $block_name )
  {
    $blocks = $this->get_blocks();

    if ( isset( $blocks[$block_name] ) /* @perf array_key_exists( $block_name, $blocks ) */ )
    {
      return $blocks[ $block_name ];
    }

    throw new \Exception( 'No block exists with the name "' . $block_name . '"' );
  }

  /**
   * Initialise the blocks within the entity instance
   *
   * @param array $blocks A list of block names to initialise
   * @param array $options
   */
  public function initialise_blocks ( $blocks = [], $options = [] )
  {
    $_options = wp_parse_args( $options, [
      'builder' => blockpress(),
    ]);

    // If no blocks given, check the entity for any blocks
    if ( empty( $blocks ) )
    {
      $blocks = $this->get_prop( 'blocks' );
    }

    // If again no blocks specified, register all of the blocks within this entity
    if ( empty( $blocks ) )
    {
      $blocks = array_keys( $_options['builder']->get_prop( 'loaded_blocks' ) );
      $this->blocks = $blocks;
    }

    // Register the specified blocks within this entity
    if ( ! empty( $blocks ) )
    {
      foreach ( $blocks as $block_name )
      {
        // If $pecial $auce is found, load in the blocks which match the selector
        if ( preg_match( '/^\$\$\:?/', $block_name ) )
        {
          $filters = str_replace( '$$:', '', $block_name );
          $filtered_blocks = filter_blocks( $_options['builder']->get_blocks(), $filters );

          // Register all the filtered blocks
          foreach ( $filtered_blocks as $filtered_block_name => $filtered_block_instance )
          {
            $this->register_block( $filtered_block_name );
          }
        }
        else
        {
          $this->register_block( $block_name );
        }
      }
    }
  }

  /**
   * Register a loaded layout to use within the entity
   *
   * @param string $layout_name
   * @param array $options
   * @param Layout $layout_instance
   */
  public function register_layout ( $layout_name, $options = [] )
  {
    $_options = wp_parse_args( $options, [
      'builder' => blockpress(),
    ]);

    $this->_layouts[ $layout_name ] = $_options['builder']->get_layout_instance( $layout_name );
  }

  /**
   * Get all of the layouts loaded into the Builder
   *
   * @return array
   */
  public function get_layouts ()
  {
    return $this->_layouts;
  }

  /**
   * Get a single layout attached to the entity
   *
   * @param string $layout_name
   * @return array
   * @throws \Exception
   */
  public function get_layout ( $layout_name )
  {
    $layouts = $this->get_layouts();

    if ( isset( $layouts[$layout_name] ) /* @perf array_key_exists( $layout_name, $layouts ) */ )
    {
      return $layouts[ $layout_name ];
    }

    throw new \Exception( 'No layout exists with the name "' . $layout_name . '"' );
  }

  /**
   * Initialise the layouts within the entity instance
   *
   * @param array $layouts
   * @param array $options
   */
  public function initialise_layouts ( $layouts = [], $options = [] )
  {
    $_options = wp_parse_args( $options, [
      'builder' => blockpress(),
    ]);

    // If no layouts given, register all of the layouts to use within this entity
    if ( empty( $layouts ) )
    {
      $layouts = array_keys( $_options['builder']->get_prop( 'loaded_layouts' ) );
      $this->layouts = $layouts;
    }

    // Register the specified layouts within this entity
    if ( ! empty( $layouts ) )
    {
      foreach ( $layouts as $layout_name )
      {
        $this->register_layout( $layout_name );
      }
    }
  }

  /**
   * Initialise the fields on the entity
   *
   * @param array $fields The names of the properties that hold fields to be initialised
   */
  public function initialise_fields ( $fields = [] )
  {
    if ( ! empty( $fields ) )
    {
      foreach ( $fields as $_field_type )
      {
        // Process each of the fields
        if ( property_exists( $this, $_field_type ) )
        {
          $_fields = $this->get_prop( $_field_type );
          foreach ( $_fields as $_field_key => $_field )
          {
            if ( is_array( $_field ) && isset( $_field['$$'] ) /* @perf array_key_exists( '$$', $_field ) */ )
            {
              $this->$_field_type[ $_field_key ] = apply_special_sauce( $_field['$$'], $this );
            }
          }
        }
      }
    }
  }

  /**
   * Generate a field for use within this entity
   *
   * @param null|string $field_group
   * @param string $type
   * @param array $acf_config
   * @param array $options
   * @returns array
   */
  protected function generate_field ( $field_group = NULL, $type, $acf_config, $options = [] )
  {
    $generation_key = $acf_config['key'];
    $generated_sub_field = generate_acf_field( $type, $acf_config, $options );

    $_options = wp_parse_args( $options, [
      'overwrite_field' => '',
    ] );

    // @TODO check that a field hasn't already been defined?
    if ( $_options['overwrite_field'] === $generated_sub_field )
    {
      $debug = [
        'generation_key' => $generation_key,
        'old_key' => $_options['overwrite_field'],
        'new_key' => $generated_sub_field['key'],
      ];
    }

    // Add the generated field to the entity's fields
    if ( ! empty( $field_group ) )
    {
      // Make sure the field group exists
      if ( ! isset( $this->_fields[ $field_group ] ) )
      {
        $this->_fields[ $field_group ] = [];
      }

      $this->_fields[ $field_group ][ $generated_sub_field['key'] ] = $generation_key;
    }
    else
    {
      $this->_fields[ $generated_sub_field['key'] ] = $generation_key;
    }

    return $generated_sub_field;
  }

  /**
   * Check if the entity is a block
   *
   * @return bool
   */
  public function is_block ()
  {
    return is_a( $this, __NAMESPACE__ . '\\Block' );
  }

  /**
   * Check if the entity is a layout
   *
   * @return bool
   */
  public function is_layout ()
  {
    return is_a( $this, __NAMESPACE__ . '\\Layout' );
  }

  /**
   * Check if the rules can allow this entity to interact with another
   *
   * @param Entity $target_entity
   * @returns bool
   */
  public function is_compatible_with ( $target_entity )
  {
    $self_type = ( $this->is_block() ? 'block' : 'layout' );
    $compatible_entities = ( $self_type === 'block' ? [ 'layout', 'block' ] : [ 'block' ] );
    $rules = $this->get_prop( 'rules' );

    // If rules given...
    if ( property_exists( $this, 'rules' ) )
    {
      foreach ( $compatible_entities as $entity_type )
      {
        if ( isset( $rules[$entity_type] ) /* @perf array_key_exists( $entity_type, $rules ) */ )
        {
          // Only return true if it matches the rule
          // @TODO apply some special sauce here for more dynamic rule matching (e.g. __not, __exclude, __if, etc.)
          if ( ! empty( $rules[ $entity_type ])
            && (
               ( is_string( $rules[ $entity_type ] ) && ( $rules[ $entity_type ] === '*' || $rules[ $entity_type ] === $target_entity->get_prop( 'name' ) ) )
            || ( is_array( $rules[ $entity_type ] ) && array_key_exists( $target_entity->get_prop( 'name' ), array_flip( $rules[ $entity_type ] ) ) )
            )
          )
          {
            return true;
          }
        }
      }

      // After processing and no true returned, assume false result
      return false;
    }

    // If no rules given, then all is permissible!
    return true;
  }
}
