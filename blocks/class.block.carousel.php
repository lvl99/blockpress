<?php
/**
 * Carousel Block
 *
 * Represents a carousel of images
 */

namespace LVL99\ACFPageBuilder;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class BlockCarousel extends Block {
  public $name = 'carousel';
  public $label = 'Carousel';
  public $description = 'A carousel of many images';
  public $display = 'block';
  public $fields = [
    [
      'name' => 'images',
      'label' => 'Carousel Images',
      'type' => 'gallery',
      'insert' => 'append',
      'library' => 'all',
      'min_width' => '',
      'min_height' => '',
      'min_size' => '',
      'max_width' => '',
      'max_height' => '',
      'max_size' => '',
      'mime_types' => 'jpg,jpeg,png,gif',
    ],
  ];
  public $customise = [
    [
      'label' => 'Show navigation arrows',
      'name' => 'show_nav_arrows',
      'type' => 'true_false',
    ],
    [
      'label' => 'Show pagination',
      'name' => 'show_nav_pagination',
      'type' => 'true_false',
    ],
    [
      'label' => 'Autoplay',
      'name' => 'autoplay',
      'type' => 'true_false',
    ],
    [
      'label' => 'Autoplay Interval',
      'name' => 'autoplay_interval',
      'type' => 'number',
      'default_value' => 5000,
      'min' => 1000,
      'max' => 10000,
      'step' => 100,
    ],
    [
      'label' => 'Set image as slide background',
      'name' => 'set_bg_image',
      'type' => 'true_false',
    ],
    [
      '$$' => [
        'func' => __NAMESPACE__ . '\\field_bg_position',
      ],
    ],
    [
      '$$' => [
        'func' => __NAMESPACE__ . '\\field_bg_size',
      ],
    ],
  ];
}
