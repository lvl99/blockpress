<?php
/*
Plugin Name: LVL99 ACF Page Builder
Plugin URI: https://github.com/lvl99/acf-page-builder
Description: Define and build custom post/page layouts for your ACF-powered WordPress website
Author: Matt Scheurich
Author URI: http://lvl99.com/
Text Domain: lvl99-acfpb
Version: 0.1.0
*/

/**
 * # ACF Page Builder
 *
 * v0.1.0
 *
 * Create rich page layouts using ACF PRO. No need for Gutenberg, Divi Builder or Visual Composer! ... well, maybe...
 *
 * - Create re-usable blocks: text, image, carousel, columns, etc.
 * - Customise the blocks in your layout with minimal markup and using your own theme's classes and conventions.
 *   These blocks can refer to other blocks, e.g. columns can hold other blocks
 * - Save pre-configured layouts and blocks as templates to re-use on other pages
 * - Much more to do...
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

if ( ! function_exists( 'lvl99_acf_page_builder' ) && ! class_exists( 'LVL99\\ACFPageBuilder\\Builder' ) )
{
  define( 'LVL99_ACF_PAGE_BUILDER', '0.1.0' );
  define( 'LVL99_ACF_PAGE_BUILDER_PATH', __DIR__ );

  require_once( __DIR__ . '/helpers/general.php' );
  require_once( __DIR__ . '/helpers/acf-api.php' );
  require_once( __DIR__ . '/helpers/field-presets.php' );
  require_once( __DIR__ . '/helpers/special-sauce.php' );
  require_once( __DIR__ . '/core/class.entity.php' );
  require_once( __DIR__ . '/core/class.builder.php' );
  require_once( __DIR__ . '/core/class.block.php' );
  require_once( __DIR__ . '/core/class.layout.php' );
  require_once( __DIR__ . '/core/class.template.php' );

  /**
   * Configure the basic blocks to load
   * Basic blocks don't require any other block
   *
   * @filter LVL99\ACFPageBuilder\Builder\load_blocks
   * @param array $_load_blocks
   * @priority 10
   * @returns array
   */
  function lvl99_acf_page_builder_load_basic_blocks ( $_load_blocks )
  {
    $_load_blocks = array_merge( $_load_blocks, [
      // Basic blocks which don't rely on other blocks should be loaded first
      'text' => [
        'class' => 'LVL99\\ACFPageBuilder\\BlockText',
        'path' => LVL99_ACF_PAGE_BUILDER_PATH . '/blocks/class.block.text.php',
      ],
      'image' => [
        'class' => 'LVL99\\ACFPageBuilder\\BlockImage',
        'path' => LVL99_ACF_PAGE_BUILDER_PATH . '/blocks/class.block.image.php',
      ],
      'carousel' => [
        'class' => 'LVL99\\ACFPageBuilder\\BlockCarousel',
        'path' => LVL99_ACF_PAGE_BUILDER_PATH . '/blocks/class.block.carousel.php',
      ],
    ] );

    return $_load_blocks;
  }

  /**
   * Configure the special blocks to load
   * Special blocks do require other blocks to be loaded before they can load
   *
   * @filter LVL99\ACFPageBuilder\Builder\load_blocks
   * @param array $load_blocks
   * @priority 20
   * @returns array
   */
  function lvl99_acf_page_builder_load_special_blocks ( $_load_blocks )
  {
    $_load_blocks = array_merge( $_load_blocks, [
      // Blocks which can reference other blocks should be loaded last
      'columns' => [
        'class' => 'LVL99\\ACFPageBuilder\\BlockColumns',
        'path' => LVL99_ACF_PAGE_BUILDER_PATH . '/blocks/class.block.columns.php',
      ],
    ] );

    return $_load_blocks;
  }

  /**
   * Configure the layouts to load
   *
   * @filter LVL99\ACFPageBuilder\Builder\load_layouts
   * @param array $_load_layouts
   * @returns array
   */
  function lvl99_acf_page_builder_load_layouts ( $_load_layouts )
  {
    $_load_layouts = array_merge( $_load_layouts, [
      'page' => [
        'class' => 'LVL99\\ACFPageBuilder\\LayoutPage',
        'path' => LVL99_ACF_PAGE_BUILDER_PATH . '/layouts/class.layout.page.php',
      ],
    ] );

    return $_load_layouts;
  }

  function lvl99_acf_page_builder()
  {
    global $lvl99_acf_page_builder;

    if ( ! isset( $lvl99_acf_page_builder ) )
    {
      $lvl99_acf_page_builder = new LVL99\ACFPageBuilder\Builder();
      $lvl99_acf_page_builder->initialise();
    }

    return $lvl99_acf_page_builder;
  }

  // Let's make page layout magic!
  add_action( 'LVL99\ACFPageBuilder\Builder\load_blocks', 'lvl99_acf_page_builder_load_basic_blocks', 10, 1 );
  add_action( 'LVL99\ACFPageBuilder\Builder\load_blocks', 'lvl99_acf_page_builder_load_special_blocks', 20, 1 );
  add_action( 'LVL99\ACFPageBuilder\Builder\load_layouts', 'lvl99_acf_page_builder_load_layouts', 10, 1 );
  add_action( 'acf/init', 'lvl99_acf_page_builder' );
}
