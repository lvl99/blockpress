<?php
/**
 * ACF Page Builder - Entity
 *
 * Represents a generic entity in Page Builder
 */

namespace LVL99\ACFPageBuilder;

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
      $_key = join( '_', $_key );
    }

    return sanitise_key( $_key );
  }

  /**
   * Get a namespaced version of the key (namespace will be prepended to the entity's key)
   *
   * @param string $key
   * @return string
   */
  public function get_namespaced_key ( $key = '' )
  {
    $_key = $this->get_key();

    // If a namespace key was given, ensure it's at the start
    if ( ! empty( $key ) )
    {
      $_key = sanitise_key( $key ) . '_' . $_key;
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
      $this->key = [ $key ];

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
   * @param $block_instance
   */
  public function register_block ( $block_name )
  {
    $this->_blocks[ $block_name ] = lvl99_acf_page_builder()->get_block_instance( $block_name );
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

    if ( array_key_exists( $block_name, $blocks ) )
    {
      return $blocks[ $block_name ];
    }

    throw new \Exception( 'No block exists with the name "' . $block_name . '"' );
  }

  /**
   * Initialise the blocks within the entity instance
   *
   * @param array $blocks A list of block names to initialise
   */
  public function initialise_blocks ( $blocks = [] )
  {
    // If no blocks given, check the entity for any blocks
    if ( empty( $blocks ) )
    {
      $blocks = $this->get_prop( 'blocks' );
    }

    // If again no blocks specified, register all of the blocks
    if ( empty( $blocks ) )
    {
      $blocks = array_keys( lvl99_acf_page_builder()->get_prop( 'loaded_blocks' ) );
      $this->blocks = $blocks;
    }

    // Register the specified blocks within this entity
    if ( ! empty( $blocks ) )
    {
      foreach ( $blocks as $block_name )
      {
        $this->register_block( $block_name );
      }
    }
  }

  /**
   * Register a loaded layout to use within the entity
   *
   * @param $layout_name
   * @param $layout_instance
   */
  public function register_layout ( $layout_name )
  {
    $this->_layouts[ $layout_name ] = lvl99_acf_page_builder()->get_layout_instance( $layout_name );
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

    if ( array_key_exists( $layout_name, $layouts ) )
    {
      return $layouts[ $layout_name ];
    }

    throw new \Exception( 'No layout exists with the name "' . $layout_name . '"' );
  }

  /**
   * Initialise the layouts within the entity instance
   *
   */
  public function initialise_layouts ( $layouts = [] )
  {
    // If no layouts given, register all of the layouts
    if ( empty( $layouts ) )
    {
      $layouts = array_keys( lvl99_acf_page_builder()->get_prop( 'loaded_layouts' ) );
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
            if ( is_array( $_field ) && array_key_exists( '$$', $_field ) )
            {
              $this->$_field_type[ $_field_key ] = apply_special_sauce( $_field['$$'], $this );
            }
          }
        }
      }
    }
  }
}
