<?php
/**
 * Field presents
 *
 * A bunch of convenience functions to generate re-usable fields
 */

namespace LVL99\ACFPageBuilder;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

function field_bg_color ()
{
  return [
    'label' => 'Background Color',
    'name' => 'bg_color',
    'type' => 'color_picker',
  ];
}

function field_bg_image ()
{
  return [
    'label' => 'Background image',
    'name' => 'bg_image',
    'type' => 'image',
  ];
}

function field_bg_size ()
{
  return [
    'label' => 'Background sizing',
    'name' => 'bg_size',
    'type' => 'select',
    'choices' => [
      'none' => 'None (default)',
      'cover' => 'Cover whole slide with image',
      'contain' => 'Contain whole image within slide',
    ],
  ];
}

function field_bg_position ()
{
  return [
    'label' => 'Background positioning',
    'name' => 'bg_position',
    'type' => 'select',
    'choices' => [
      'tl' => 'Top Left',
      't' => 'Top (Horizontally centred)',
      'tr' => 'Top Right',
      'l' => 'Left (Vertically centred)',
      'center' => 'Centered',
      'bl' => 'Bottom Left',
      'b' => 'Bottom (Horizontally centred)',
      'br' => 'Bottom Right',
    ],
    'default_value' => [
      'center',
    ],
  ];
}
