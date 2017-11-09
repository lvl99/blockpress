<?php
/**
 * Field presents
 *
 * A bunch of convenience functions to generate re-usable fields
 */

namespace LVL99\BlockPress;

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
    'return_format' => 'array',
  ];
}

function field_bg_repeat ()
{
  return [
    'label' => 'Background repeat',
    'name' => 'bg_repeat',
    'type' => 'select',
    'choices' => [
      'no-repeat' => 'No repeat',
      'repeat-x' => 'Repeat only X axis',
      'repeat-y' => 'Repeat only Y axis',
      'repeat' => 'Repeat X and Y axes',
    ],
    'default_value' => [
      'no-repeat'
    ],
  ];
}

function field_bg_size ()
{
  return [
    'label' => 'Background sizing',
    'name' => 'bg_size',
    'type' => 'select',
    'choices' => [
      'none' => 'None',
      'cover' => 'Cover',
      'contain' => 'Contain',
    ],
    'default_value' => [
      'none'
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
