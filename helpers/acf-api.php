<?php
/**
 * ACF helpers
 */

namespace LVL99\ACFPageBuilder;

/**
 * Generate an ACF group to hold ACF fields
 *
 * @param $acf_config
 * @return array
 */
function generate_acf_group ( $acf_config, $options = [] )
{
  $_group = array_merge( [
    'key' => uniqid(),
    'title' => '',
    'description' => '',
    'fields' => [],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'post',
        ],
      ],
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'page',
        ],
      ],
    ],
    'menu_order' => 0,
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'hide_on_screen' => '',
    'active' => 1,
  ], $acf_config );

  // Ensure keys are sanitised
  $_group['key'] = 'group_' . encode_key( $_group['key'] );

  return $_group;
}

/**
 * Generate an ACF Field
 *
 * @param $type
 * @param $acf_config
 * @return array
 */
function generate_acf_field ( $type, $acf_config, $options = [] )
{
  // Generic field stuff
  $_field = [
    'key' => uniqid(),
    'label' => '',
    'name' => '',
    'instructions' => '',
    'required' => 0,
    'conditional_logic' => 0,
    'wrapper' => [
      'width' => '',
      'class' => '',
      'id' => '',
    ],
  ];

  switch ( $type )
  {
    case 'text':
      $_field = array_merge( $_field, [
        'type' => 'text',
        'default_value' => '',
        'placeholder' => '',
        'prepend' => '',
        'append' => '',
        'maxlength' => '',
      ] );
      break;

    case 'textarea':
      $_field = array_merge( $_field, [
        'type' => 'textarea',
        'default_value' => '',
        'placeholder' => '',
        'maxlength' => '',
        'rows' => '',
        'new_lines' => '',
      ] );
      break;

    case 'number':
      $_field = array_merge( $_field, [
        'type' => 'number',
        'default_value' => '',
        'placeholder' => '',
        'maxlength' => '',
        'rows' => '',
        'new_lines' => '',
      ] );
      break;

    case 'range':
      $_field = array_merge( $_field, [
        'default_value' => '',
        'min' => '',
        'max' => '',
        'step' => '',
        'prepend' => '',
        'append' => '',
      ] );
      break;

    case 'email':
      $_field = array_merge( $_field, [
        'type' => 'email',
        'default_value' => '',
        'placeholder' => '',
        'prepend' => '',
        'append' => '',
      ] );
      break;

    case 'url':
      $_field = array_merge( $_field, [
        'type' => 'url',
        'default_value' => '',
        'placeholder' => '',
      ] );
      break;

    case 'password':
      $_field = array_merge( $_field, [
        'type' => 'password',
        'placeholder' => '',
        'prepend' => '',
        'append' => '',
      ] );
      break;

    case 'wysiwyg':
      $_field = array_merge( $_field, [
        'type' => 'wysiwyg',
        'default_value' => '',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 1,
        'delay' => 0,
      ] );
      break;

    case 'oembed':
      $_field = array_merge( $_field, [
        'type' => 'oembed',
        'width' => '',
        'height' => '',
      ] );
      break;

    case 'image':
      $_field = array_merge( $_field, [
        'type' => 'image',
        'return_format' => 'array',
        'preview_size' => 'thumbnail',
        'library' => 'all',
        'min_width' => '',
        'min_height' => '',
        'min_size' => '',
        'max_width' => '',
        'max_height' => '',
        'max_size' => '',
        'mime_types' => '',
      ] );
      break;

    case 'file':
      $_field = array_merge( $_field, [
        'type' => 'file',
        'return_format' => 'array',
        'library' => 'all',
        'min_size' => '',
        'max_size' => '',
        'mime_types' => '',
      ] );
      break;

    case 'gallery':
      $_field = array_merge( $_field, [
        'type' => 'gallery',
        'min' => '',
        'max' => '',
        'insert' => 'append',
        'library' => 'all',
        'min_width' => '',
        'min_height' => '',
        'min_size' => '',
        'max_width' => '',
        'max_height' => '',
        'max_size' => '',
        'mime_types' => '',
      ] );
      break;

    case 'link':
      $_field = array_merge( $_field, [
        'type' => 'link',
        'return_format' => 'array',
      ] );
      break;

    case 'post_object':
      $_field = array_merge( $_field, [
        'type' => 'post_object',
        'post_type' => [],
        'taxonomy' => [],
        'allow_null' => 0,
        'multiple' => 0,
        'return_format' => 'object',
        'ui' => 1,
      ] );
      break;

    case 'page_link':
      $_field = array_merge( $_field, [
        'type' => 'page_link',
        'post_type' => [],
        'taxonomy' => [],
        'allow_null' => 0,
        'allow_archives' => 1,
        'multiple' => 0,
      ] );
      break;

    case 'relationship':
      $_field = array_merge( $_field, [
        'type' => 'relationship',
        'post_type' => [],
        'taxonomy' => [],
        'filters' => [
          'search',
          'post_type',
          'taxonomy',
        ],
        'elements' => '',
        'min' => '',
        'max' => '',
        'return_format' => 'object',
      ] );
      break;

    case 'taxonomy':
      $_field = array_merge( $_field, [
        'type' => 'taxonomy',
        'taxonomy' => 'category',
        'field_type' => 'checkbox',
        'allow_null' => 0,
        'add_term' => 1,
        'save_terms' => 0,
        'load_terms' => 0,
        'return_format' => 'id',
        'multiple' => 0,
      ] );
      break;

    case 'user':
      $_field = array_merge( $_field, [
        'type' => 'user',
        'role' => '',
        'allow_null' => 0,
        'multiple' => 0,
      ] );
      break;

    case 'select':
      $_field = array_merge( $_field, [
        'type' => 'select',
        'choices' => [
          /*
          'red' => 'Red',
          'green' => 'Green',
          'blue' => 'Blue',
          */
        ],
        'default_value' => [
          /*
          0 => 'red',
          */
        ],
        'allow_null' => 0,
        'multiple' => 0,
        'ui' => 0,
        'ajax' => 0,
        'return_format' => 'value',
        'placeholder' => '',
      ] );
      break;

    case 'checkbox':
      $_field = array_merge( $_field, [
        'type' => 'checkbox',
        'choices' => [
          /*
          'red' => 'Red',
          'green' => 'Green',
          'blue' => 'Blue',
          */
        ],
        'default_value' => [
          /*
          0 => 'red',
          */
        ],
        'allow_custom' => 0,
        'save_custom' => 0,
        'layout' => 'vertical',
        'toggle' => 0,
        'return_format' => 'value',
      ] );
      break;

    case 'radio_button':
      $_field = array_merge( $_field, [
        'type' => 'radio_button',
        'choices' => [
          /*
          'red' => 'Red',
          'green' => 'Green',
          'blue' => 'Blue',
          */
        ],
        'allow_null' => 0,
        'other_choice' => 0,
        'save_other_choice' => 0,
        'default_value' => '',
        'layout' => 'vertical',
        'return_format' => 'value',
      ] );
      break;

    case 'true_false':
      $_field = array_merge( $_field, [
        'type' => 'true_false',
        'message' => '',
        'default_value' => 0,
        'ui' => 0,
        'ui_on_text' => '',
        'ui_off_text' => '',
      ] );
      break;

    case 'google_map':
      $_field = array_merge( $_field, [
        'type' => 'google_map',
        'center_lat' => '',
        'center_lng' => '',
        'zoom' => '',
        'height' => '',
      ] );
      break;

    case 'date_picker':
      $_field = array_merge( $_field, [
        'type' => 'date_picker',
        'display_format' => 'd/m/Y',
        'return_format' => 'd/m/Y',
        'first_day' => 1,
      ] );
      break;

    case 'date_time_picker':
      $_field = array_merge( $_field, [
        'type' => 'date_time_picker',
        'display_format' => 'd/m/Y g:i a',
        'return_format' => 'd/m/Y g:i a',
        'first_day' => 1,
      ] );
      break;

    case 'time_picker':
      $_field = array_merge( $_field, [
        'type' => 'time_picker',
        'display_format' => 'g:i a',
        'return_format' => 'g:i a',
      ] );
      break;

    case 'color_picker':
      $_field = array_merge( $_field, [
        'type' => 'color_picker',
        'default_value' => '',
      ] );
      break;

    case 'message':
      $_field = array_merge( $_field, [
        'type' => 'message',
        'message' => '',
        'new_lines' => 'wpautop',
        'esc_html' => 0,
      ] );

      // Remove unnecessary properties
      unset( $_field['key'] );
      break;

    case 'tab':
      $_field = array_merge( $_field, [
        'type' => 'tab',
        'placement' => 'top',
        'endpoint' => 0,
      ] );

      // Remove unnecessary properties
      unset( $_field['key'] );
      break;

    case 'group':
      $_field = array_merge( $_field, [
        'type' => 'group',
        'layout' => 'block',
        'sub_fields' => [],
      ] );
      break;

    case 'repeater':
      $_field = array_merge( $_field, [
        'type' => 'repeater',
        'collapsed' => '',
        'min' => 0,
        'max' => 0,
        'layout' => 'block',
        'button_label' => 'Add Item',
        'sub_fields' => [],
      ] );
      break;

    case 'flexible_content':
      $_field = array_merge( $_field, [
        'type' => 'flexible_content',
        'layouts' => [
          /*
          '59b106f894dc0' => array (
            'key' => '59b106f894dc0',
            'name' => '',
            'label' => '',
            'display' => 'block',
            'sub_fields' => [],
            'min' => '',
            'max' => '',
          ),
          */
        ],
        'button_label' => 'Add Flexible Content',
        'min' => '',
        'max' => '',
      ] );
      break;

    case 'clone':
      $_field = array_merge( $_field, [
        'type' => 'clone',
        'clone' => [
          /*
          0 => 'field_59b10542a5769',
          1 => 'field_59b1054ca576a',
          */
        ],
        'display' => 'seamless',
        'layout' => 'block',
        'prefix_label' => 0,
        'prefix_name' => 0,
      ] );
      break;
  }

  // Load in the passed details
  $_field = array_merge( $_field, $acf_config );

  // Ensure blocks (if required) are loaded and attached to the field's ACF config
  if ( ! empty( $options ) && array_key_exists( 'blocks', $options ) )
  {
    $_field = load_blocks_into_acf_field( $type, $_field, $options );
  }

  // Ensure sanitised key
  if ( array_key_exists( 'key', $_field ) )
  {
    $_field['key'] = 'field_' . encode_key( $_field['key'] );
  }

  return $_field;
}

function generate_acf_field_text ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'text', $acf_config, $options );
  return $_field;
}

function generate_acf_field_textarea ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'textarea', $acf_config, $options );
  return $_field;
}

function generate_acf_field_number ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'number', $acf_config, $options );
  return $_field;
}

function generate_acf_field_range ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'range', $acf_config, $options );
  return $_field;
}

function generate_acf_field_email ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'email', $acf_config, $options );
  return $_field;
}

function generate_acf_field_url ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'url', $acf_config, $options );
  return $_field;
}

function generate_acf_field_password ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'password', $acf_config, $options );
  return $_field;
}

function generate_acf_field_wysiwyg ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'wysiwyg', $acf_config, $options );
  return $_field;
}

function generate_acf_field_oembed ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'oembed', $acf_config, $options );
  return $_field;
}

function generate_acf_field_image ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'image', $acf_config, $options );
  return $_field;
}

function generate_acf_field_file ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'file', $acf_config, $options );
  return $_field;
}

function generate_acf_field_gallery ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'gallery', $acf_config, $options );
  return $_field;
}

function generate_acf_field_link ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'link', $acf_config, $options );
  return $_field;
}

function generate_acf_field_post_object ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'post_object', $acf_config, $options );
  return $_field;
}

function generate_acf_field_page_link ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'page_link', $acf_config, $options );
  return $_field;
}

function generate_acf_field_taxonomy ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'taxonomy', $acf_config, $options );
  return $_field;
}

function generate_acf_field_relationship ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'relationship', $acf_config, $options );
  return $_field;
}

function generate_acf_field_user ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'user', $acf_config, $options );
  return $_field;
}

function generate_acf_field_select ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'select', $acf_config, $options );
  return $_field;
}

function generate_acf_field_checkbox ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'checkbox', $acf_config, $options );
  return $_field;
}

function generate_acf_field_radio_button ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'radio_button', $acf_config, $options );
  return $_field;
}

function generate_acf_field_true_false ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'true_false', $acf_config, $options );
  return $_field;
}

function generate_acf_field_google_map ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'google_map', $acf_config, $options );
  return $_field;
}

function generate_acf_field_date_picker ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'date_picker', $acf_config, $options );
  return $_field;
}

function generate_acf_field_date_time_picker ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'date_time_picker', $acf_config, $options );
  return $_field;
}

function generate_acf_field_time_picker ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'time_picker', $acf_config, $options );
  return $_field;
}

function generate_acf_field_color_picker ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'color_picker', $acf_config, $options );
  return $_field;
}

function generate_acf_field_message ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'message', $acf_config, $options );
  return $_field;
}

function generate_acf_field_tab ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'tab', $acf_config, $options );
  return $_field;
}

function generate_acf_field_group ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'group', $acf_config, $options );
  return $_field;
}

function generate_acf_field_repeater ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'repeater', $acf_config, $options );
  return $_field;
}

function generate_acf_field_flexible_content ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'flexible_content', $acf_config, $options );
  return $_field;
}

function generate_acf_field_clone ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'clone', $acf_config, $options );
  return $_field;
}

function generate_acf_flexible_content_layout ( $acf_config, $options = [] )
{
  $_layout = array_merge( [
    'key' => uniqid(),
    'name' => '',
    'label' => '',
    'display' => 'block',
    'sub_fields' => [],
    'min' => '',
    'max' => '',
  ], $acf_config );

  // Ensure keys are sanitised
  $_layout['key'] = 'field_' . encode_key( $_layout['key'] );

  return $_layout;
}

function generate_acf_page_builder_layout ( $acf_config, $options = [] )
{
  $_field = generate_acf_field( 'flexible_content', $acf_config, $options );
  return $_field;
}

// Essentially the same as `generate_acf_flexible_content_layout` just with a different key denomination
function generate_acf_page_builder_block ( $acf_config, $options = [] )
{
  $_block = array_merge( [
    'key' => uniqid(),
    'name' => '',
    'label' => '',
    'display' => 'block',
    'sub_fields' => [],
    'min' => '',
    'max' => '',
  ], $acf_config );

  // Ensure keys are sanitised
  $_block['key'] = 'block_' . encode_key( $_block['key'] );

  return $_block;
}
