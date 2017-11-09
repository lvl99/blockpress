<?php
/**
 * ACF BlockPress - Admin
 *
 * Manage BlockPress via the WordPress admin backend
 */

namespace LVL99\BlockPress;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

if ( defined( 'ICL_SITEPRESS_VERSION') )
{
  require_once( LVL99_BLOCKPRESS_PATH . '/classes/admin/class.wpml.php' );
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
      add_filter( 'wpml_config_array', [ $this->wpml, 'wpml_config' ] );
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

  /**
   * Output the WPML config
   *
   * @param $wpml_config
   * @return array
   * @throws \Error
   */
  public function wpml_config ( $wpml_config = [] )
  {
    return $wpml_config;

//    if ( defined( 'ICL_SITEPRESS_VERSION') )
//    {
//      // If not already loaded, load and parse the ACF config
//      if ( ! $this->wpml->has_loaded_config() )
//      {
//        $this->wpml->parse_builder( blockpress() );
//      }
//
//      return $this->wpml->get_config( 'wpml' );
//    }
  }
}
