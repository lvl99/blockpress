<?php
/**
 * ACF BlockPress - WPML
 *
 * Manage the WPML configuration for blocks and layouts
 */

namespace LVL99\BlockPress;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class WPML {
  /**
   * The compiled config object
   *
   * @var array
   */
  private $config = [];

  /**
   * The WPML formatted config object
   *
   * @var array
   */
  private $wpml_config = [];

  /**
   * Track if has loaded and parsed the config
   *
   * @var bool
   */
  private $has_loaded_config = FALSE;

  /**
   * WPML constructor
   *
   * @param Builder $builder
   */
  public function __construct ( $builder = FALSE )
  {
    if ( $builder )
    {
      $this->parse_builder( $builder );
    }
  }

  /**
   * Parse the builder to generate the WPML config
   *
   * @param Builder $builder
   * @throws \Error
   */
  public function parse_builder ( $builder )
  {
    // Verify the build is a Builder instance
    if ( ! is_a( $builder, '\LVL99\BlockPress\Builder' ) )
    {
      throw new \Error( 'Invalid builder given' );
    }

    // Initialise the config object
    $this->config = [
      'custom_fields' => [],
    ];
    $this->wpml_config = [];

    // @TODO parse the builder
  }

  /**
   * Add a WPML config value for a custom field
   *
   * @param string $field_slug
   * @param string $namespace
   * @param string|array $options
   * @param array $options Either string or options to set; default value is 'copy'; accepted values: 'translate', 'copy', 'copy-once', 'ignore'
   */
  public function add_custom_field ( $field_path, $options = 'copy' )
  {
    // Set up options
    if ( is_string( $options ) )
    {
      $options = [ 'action' => $options ];
    }
    else if ( is_array( $options ) )
    {
      $options = wp_parse_args( $options, [
        'action' => 'copy',
      ] );
    }

    // Check the field's path doesn't already exist
    if ( ! array_key_exists( $field_path, array_flip( $this->config['custom_fields'] ) ) )
    {
      // Add the field path to the config object
      $this->config['custom_fields'][ $field_path ] = $options;
    }
  }

  /**
   * Get the generated WPML config
   *
   * @return array
   */
  public function get_config ( $format = 'raw' )
  {
    if ( ! $this->has_loaded_config() )
    {
      throw new \Error( 'No ACF configuration was given to process the WPML config object' );
    }

    // Get an unformatted version
    if ( $format === 'raw' )
    {
      return $this->config;
    }
    // Get a formatted version for WPML to read
    elseif ( $format === 'wpml' )
    {
      if ( empty( $this->wpml_config ) )
      {
        $wpml_config = [];

        foreach ( $this->config as $config_item_index => $config_item )
        {
          $wpml_config_item = $this->get_formatted_config_item( $config_item_index, $config_item );

          $wpml_config[] = $wpml_config_item;
        }

        $this->wpml_config = $wpml_config;

        return $this->wpml_config;
      }

      return $this->wpml_config;
    }
  }

  /**
   * Get the formatted version of an item within the config
   */
  private function get_formatted_config_item ( $config_item_index, $config_item, $options = [] )
  {
    $_options = wp_parse_args( $options, [
      'parent' => '',
    ] );

    // Set the name of the config node
    if ( is_string( $config_item_index ) )
    {
      // @TODO
    }

    // Array of items which need to be individually formatted
    if ( is_array( $config_item ) )
    {
      $formatted_items = [];

      foreach ( $config_item as $child_item_index => $child_item )
      {
        // @TODO
        $formatted_item = $this->get_formatted_config_item( $child_item_index, $child_item, [
          // @TODO
        ] );

        $formatted_items[] = $formatted_item;
      }

      return $formatted_items;
    }
    // Return the formatted item
    else
    {
      $formatted_item = [
        // @TODO
      ];
    }
  }

  /**
   * Check if the ACF config has been loaded and the WPML config generated
   *
   * @return bool
   */
  public function has_loaded_config ()
  {
    return $this->has_loaded_config;
  }
}
