<?php
/*
Plugin Name: LVL99 BlockPress
Plugin URI: https://github.com/lvl99/blockpress
Description: Define and build custom content layouts with reusable blocks for your ACF-powered WordPress website
Author: Matt Scheurich
Author URI: http://lvl99.com/
Text Domain: lvl99-acfpb
Version: 0.2.2
*/

/**
 * # LVL99 BlockPress
 *
 * v0.2.2
 *
 * Create rich content layouts using ACF PRO. No need for Gutenberg, Divi Builder or Visual Composer! ... well, hopefully...
 *
 * - Create re-usable blocks: text, image, carousel, columns, etc.
 * - Customise the blocks in your layout with minimal markup and using your own theme's classes and conventions.
 *   These blocks can refer to other blocks, e.g. columns can hold other blocks
 * - Render blocks in templates
 * - Much more to do...
 *
 * If you have any input on how to do things better or just comments and suggestions for improvement and bug fixes,
 * please don't hesitate to contribute via the github repo: https://github.com/lvl99/blockpress
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

if ( ! function_exists( 'blockpress' ) && ! class_exists( 'LVL99\\BlockPress\\Builder' ) )
{
  define( 'LVL99_BLOCKPRESS', '0.2.1' );
  define( 'LVL99_BLOCKPRESS_PATH', __DIR__ );

  // Dependencies
  require_once( LVL99_BLOCKPRESS_PATH . '/inc/general.php' );
  require_once( LVL99_BLOCKPRESS_PATH . '/inc/acf-api.php' );
  require_once( LVL99_BLOCKPRESS_PATH . '/inc/field-presets.php' );
  require_once( LVL99_BLOCKPRESS_PATH . '/inc/special-sauce.php' );
  require_once( LVL99_BLOCKPRESS_PATH . '/classes/class.entity.php' );
  require_once( LVL99_BLOCKPRESS_PATH . '/classes/class.builder.php' );
  require_once( LVL99_BLOCKPRESS_PATH . '/classes/class.block.php' );
  require_once( LVL99_BLOCKPRESS_PATH . '/classes/class.layout.php' );
  require_once( LVL99_BLOCKPRESS_PATH . '/classes/class.template.php' );

  /**
   * Configure the basic blocks to load
   * Basic blocks don't require any other block
   *
   * @hook LVL99\BlockPress\Builder\load_blocks
   * @param array $_load_blocks
   * @priority 10
   * @returns array
   */
  if ( ! function_exists( 'blockpress_load_basic_blocks' ) )
  {
    function blockpress_load_basic_blocks ( $_load_blocks )
    {
      $_load_blocks = array_merge( $_load_blocks, [
        // Basic blocks which don't rely on other blocks should be loaded first
        'text' => [
          'class' => 'LVL99\\BlockPress\\BlockText',
          'path' => LVL99_BLOCKPRESS_PATH . '/classes/blocks/class.block.text.php',
        ],
        'image' => [
          'class' => 'LVL99\\BlockPress\\BlockImage',
          'path' => LVL99_BLOCKPRESS_PATH . '/classes/blocks/class.block.image.php',
        ],
      ] );

      return $_load_blocks;
    }
  }

  /**
   * Configure the special blocks to load
   * Special blocks do require other blocks to be loaded before they can load
   *
   * @hook LVL99\BlockPress\Builder\load_blocks
   * @param array $load_blocks
   * @priority 20
   * @returns array
   */
  if ( ! function_exists( 'blockpress_load_special_blocks' ) )
  {
    function blockpress_load_special_blocks ( $_load_blocks )
    {
      $_load_blocks = array_merge( $_load_blocks, [
        // Blocks which can reference other blocks should be loaded last
        'column' => [
          'class' => 'LVL99\\BlockPress\\BlockColumn',
          'path' => LVL99_BLOCKPRESS_PATH . '/classes/blocks/class.block.column.php',
        ],
        'columns' => [
          'class' => 'LVL99\\BlockPress\\BlockColumns',
          'path' => LVL99_BLOCKPRESS_PATH . '/classes/blocks/class.block.columns.php',
        ],
      ] );

      return $_load_blocks;
    }
  }

  /**
   * Configure the layouts to load
   *
   * @filter LVL99\BlockPress\Builder\load_layouts
   * @param array $_load_layouts
   * @returns array
   */
  if ( ! function_exists( 'blockpress_load_layouts' ) )
  {
    function blockpress_load_layouts ( $_load_layouts )
    {
      $_load_layouts = array_merge( $_load_layouts, [
        'page' => [
          'class' => 'LVL99\\BlockPress\\LayoutPage',
          'path' => LVL99_BLOCKPRESS_PATH . '/classes/layouts/class.layout.page.php',
        ],
      ] );

      return $_load_layouts;
    }
  }

  function blockpress()
  {
    global $lvl99_blockpress;

    if ( ! isset( $lvl99_blockpress ) )
    {
      $lvl99_blockpress = new LVL99\BlockPress\Builder();
      $lvl99_blockpress->initialise();
    }

    return $lvl99_blockpress;
  }

  // Let's make page layout magic!
  add_action( 'LVL99\BlockPress\Builder\load_blocks', 'blockpress_load_basic_blocks', 10, 1 );
  add_action( 'LVL99\BlockPress\Builder\load_blocks', 'blockpress_load_special_blocks', 20, 1 );
  add_action( 'LVL99\BlockPress\Builder\load_layouts', 'blockpress_load_layouts', 10, 1 );
  add_action( 'acf/init', 'blockpress' );

  /**
   * Admin
   */
  if ( ! function_exists( 'admin_blockpress' ) && ! class_exists( 'LVL99\\BlockPress\\Admin' ) )
  {
    require_once( LVL99_BLOCKPRESS_PATH . '/classes/admin/class.admin.php' );

    function admin_blockpress ()
    {
      global $admin_lvl99_blockpress;

      if ( is_admin() && current_user_can( 'manage_options' ) )
      {
        if ( ! isset( $admin_lvl99_blockpress ) )
        {
          $admin_lvl99_blockpress = new LVL99\BlockPress\Admin( blockpress() );
          $admin_lvl99_blockpress->initialise();
        }

        return $admin_lvl99_blockpress;
      }
    }

    // Initialise the admin
    add_action( 'admin_init', 'admin_blockpress' );
  }
}
