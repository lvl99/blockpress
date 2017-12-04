<?php
/**
 * BlockPress - Admin
 *
 * Manage BlockPress via the WordPress admin backend
 */

namespace LVL99\BlockPress;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class Admin {
  /**
   * The WPML configuration
   *
   * @type WPML
   * @var
   */
  private $wpml;

  /**
   * Whether it has been initialised or not
   *
   * @var bool
   */
  private $initialised = FALSE;

  /**
   * Admin constructor.
   */
  public function __construct ( $builder )
  {
    // Generate the WPML config
    if ( class_exists( '\LVL99\BlockPress\WPML' ) )
    {
      $this->wpml = new WPML( $builder );
      add_filter( 'wpml_config_array', [ $this->wpml, 'filter_wpml_config' ] );
    }

    $this->initialise();
  }

  /**
   * Initialise the class
   */
  public function initialise ()
  {
    $this->initialised = TRUE;
  }
}
