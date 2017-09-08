<?php
/*
Plugin Name: ACF Page Builder
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

use LVL99\ACFPageBuilder as ACFPageBuilder;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

if ( ! function_exists( 'acf_page_builder' ) && ! class_exists( 'LVL99\\ACFPageBuilder\\Builder' ) )
{
  define( 'ACF_PAGE_BUILDER', '0.1.0' );
  define( 'ACF_PAGE_BUILDER_PATH', __DIR__ );

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
   * Configure the blocks to load
   *
   * @filter LVL99\ACFPageBuilder\Builder\load_blocks
   * @param array $_load_blocks
   * @returns array
   */
  function acf_page_builder_load_blocks ( $_load_blocks )
  {
    $_load_blocks = array_merge( $_load_blocks, [
//      'content' => [
//        'class' => PageBuilder\get_namespace_class( 'BlockContent' ),
//        'path' => ACF_PAGE_BUILDER_PATH . '/blocks/class.block.content.php',
//      ],
      'columns' => [
        'class' => ACFPageBuilder\get_namespace_class( 'BlockColumns' ),
        'path' => ACF_PAGE_BUILDER_PATH . '/blocks/class.block.columns.php',
      ],
      'text' => [
        'class' => ACFPageBuilder\get_namespace_class( 'BlockText' ),
        'path' => ACF_PAGE_BUILDER_PATH . '/blocks/class.block.text.php',
      ],
      'image' => [
        'class' => ACFPageBuilder\get_namespace_class( 'BlockImage' ),
        'path' => ACF_PAGE_BUILDER_PATH . '/blocks/class.block.image.php',
      ],
      'carousel' => [
        'class' => ACFPageBuilder\get_namespace_class( 'BlockCarousel' ),
        'path' => ACF_PAGE_BUILDER_PATH . '/blocks/class.block.carousel.php',
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
  function acf_page_builder_load_layouts ( $_load_layouts )
  {
    $_load_layouts = array_merge( $_load_layouts, [
      'page' => [
        'class' => ACFPageBuilder\get_namespace_class( 'LayoutPage' ),
        'path' => ACF_PAGE_BUILDER_PATH . '/layouts/class.layout.page.php',
      ],
    ] );

    return $_load_layouts;
  }

  function acf_page_builder()
  {
    global $acf_page_builder;

    if ( ! isset( $acf_page_builder ) )
    {
      $acf_page_builder = new ACFPageBuilder\Builder();
      $acf_page_builder->initialise();
    }

    return $acf_page_builder;
  }

  // Let's make page layout magic!
  add_action( 'LVL99\ACFPageBuilder\Builder\load_blocks', 'acf_page_builder_load_blocks', 10, 1 );
  add_action( 'LVL99\ACFPageBuilder\Builder\load_layouts', 'acf_page_builder_load_layouts', 10, 1 );
  add_action( 'acf/init', 'acf_page_builder' );
}
