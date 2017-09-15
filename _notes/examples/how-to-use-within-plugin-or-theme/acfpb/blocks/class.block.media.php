<?php

/**
 * Custom Blocks extend the `LVL99\ACFPageBuilder\Block` class (located at `core/class.block.php`)
 *
 * A Block describes a single flexible content layout field. A block could represent a single image, a collection of
 * paragraphs, a carousel of multiple images, etc.
 *
 * Blocks are re-usable. The other cool thing is that blocks can be nested within other blocks. You could effectively
 * have a block which can contain other blocks.
 *
 * Each Block can have many sub-fields configured. These fields fall under 3 different categories:
 *   - `content`: fields that represent the block's content
 *   - `customise`: fields that customise the block's display
 *   - `configure`: fields that configure the block's behaviours
 *
 * A block can have as many fields as you want assigned to it. The sky's the limit! Actually, PHP and your server's
 * capacity might be the actual limit...
 */

//
// Namespaces are good for organisation!
//
namespace My_Cool_Theme;

//
// Since the LVL99 ACF Page Builder has been nicely namespaced, we can use and set a shorthand alias here.
//
use LVL99\ACFPageBuilder as ACFPageBuilder;

//
// WordPress security best practices
//
if ( ! defined( 'ABSPATH' ) ) exit;

//
// When creating a custom block, you'll need to extend the Page Builder's core Block class.
//
// You can always refer to the `core/class.block.php` file to see what specific information blocks support.
//
class BlockMedia extends ACFPageBuilder\Block {
  //
  // Just like in custom layouts, we need to specify a human/machine-readable name. This name will be used in array
  // keys and within templates. Think of it as a `slug`, like setting a post's URL slug, where characters are
  // lower-case and no special characters except for underscores and hyphens are allowed.
  //
  // This block `slug` should be exactly the same as the block's array key when loading it into Page Builder.
  //
  public $name = 'mct_media';

  //
  // A human-readable label can be defined
  //
  public $label = 'Media';

  //
  // You can set a human-readable label for the block.
  //
  public $description = 'An example custom block that allows you to present a piece of media';

  //
  // In ACF you have three different methods to display the block's sub-fields:
  //   - `block`
  //   - `table`
  //   - `row`
  //
  // By default it is block, but if you want to change it here you can.
  //
  public $display = 'block';

  //
  // Here we can specify the sub fields of the block.
  //
  // As described above the fields represent a block's content and its customisation or configuration.
  //
  // For this example, we're going to specify a few fields that require some extra work in the `generate_acf` function.
  //
  public $content = [
    //
    // You can specify the fields based on the ACF schema
    //
    // For all fields there are basic defaults set (based on ACF defaults) so you can just omit the field properties
    // for it to use the defaults
    //
    [
      'type' => 'select',
      'name' => 'media_type',
      'label' => 'Media Type',
      'instructions' => 'Select the media type you wish to display',
      'choices' => [
        'image' => 'Image',
        'audio' => 'Audio',
        'video' => 'Video',
        'oembed' => 'oEmbed',
      ],
      'default_value' => [
        'image',
      ],
    ],
    //
    // With the following fields I'm going to make them only visible if the above select field is set to one of their
    // name values.
    //
    [
      'type' => 'image',
      'name' => 'image',
      'label' => 'Image',
      'instructions' => 'Select or upload an image file (supported formats: jpg, gif, png)',
      'return_format' => 'array',
      'preview_size' => 'thumbnail',
      'library' => 'all',
      'mime_types' => 'jpg,jpeg,gif,png',
    ],
    [
      'type' => 'file',
      'name' => 'audio',
      'label' => 'Audio',
      'instructions' => 'Select or upload an audio file (supported formats: mp3, ogg, m4a, aac, wma, wav, aiff, flac)',
      'return_format' => 'array',
      'preview_size' => 'thumbnail',
      'library' => 'all',
      'mime_types' => 'mp3,ogg,m4a,aac,wma,wav,aiff,flac',
    ],
    [
      'type' => 'file',
      'name' => 'video',
      'label' => 'Video',
      'instructions' => 'Select or upload a video file (supported formats: mov, avi, mpg, mp4)',
      'return_format' => 'array',
      'preview_size' => 'thumbnail',
      'library' => 'all',
      'mime_types' => 'mov,avi,mpg,mp4',
    ],
    [
      'type' => 'oembed',
      'name' => 'oembed',
      'label' => 'oEmbed',
      'instructions' => 'Enter the URL to the oEmbed media',
    ],
  ];

  //
  // The customise fields are a place to put fields which affect the block's display.
  //
  public $customise = [
    //
    // At some times we need to apply $pecial $auce.
    //
    // It's a silly name I've given to referring to a dynamic kind of invocation.
    //
    // It's currently very experimental and perhaps not even necessary (you could just load in the fields via the
    // `__construct` call if you really wanted to).
    //
    [
      //
      // A $pecial $auce invocation is performed by setting an associative array with a single key of `$$`. It will
      // then contain another associative array detailing the $pecial $auce operation.
      //
      '$$' => [
        //
        // We specify the name of the function we want special sauce to invoke.
        //
        // This is essentially where we get "funky".
        //
        // The ACF Page Builder contains a bunch of helper functions that we can use to generate preset fields.
        //
        // Because I'm so well organised with namespaces, we'll need to put the namespace as well as the function
        // name to call.
        //
        // The function `field_bg_color` is located in the `helpers/presets.php` file. It basically creates an array
        // detailing a color picker that will configure the background color for this block.
        //
        'func' => 'LVL99\\ACFPageBuilder\\field_bg_color',

        //
        // If we had any arguments to pass to this function, we would put them in a property named `args`.
        //
        // If the $pecial $auce function requires arguments, the function above is invoked using `call_user_func_array`.
        //
        // 'args' => [ 'cool', 'beans' ],
      ],
    ],
  ];

  //
  // Because we want some fields to reference other fields, we have to do that after the ACF configuration has been
  // generated.
  //
  // Each block (depending on its parent layout/block) will be generated with a special key.
  //
  // We need to reference this key every time we want to generate the ACF config.
  //
  public function generate_acf ( $key = '', $options = [] )
  {
    //
    // We also don't want to overload the class's default `generate_acf` method, so ensure to call it first.
    //
    // Since we want to manipulate and transform the ACF configuration, it makes sense to save it to a variable to
    // then do some extra work on.
    //
    $acf = parent::generate_acf( $key, $options );

    //
    // Now we've generated the ACF configuration for this block, we can then fetch the field keys to affect the
    // conditional logic for the fields that rely on the value of the select field.
    //
    // Content, Customise and Configure fields are all combined into the `sub_fields` which is a zero-indexed array.
    // Therefore we need to reference the fields we want to manipulate by number.
    //
    $select_field_key = $acf['sub_fields'][0]['key'];

    //
    // For each of the media fields (image, audio, video and oembed) we will set the `conditional_logic` value to one
    // that references the select field's key, matching only if the select field's value is that of the field's name.
    //
    $acf['sub_fields'][1]['conditional_logic'] = [
      [
        [
          'field' => $select_field_key,
          'operator' => '==',
          'value' => $acf['sub_fields'][1]['name'],
        ],
      ],
    ];
    $acf['sub_fields'][2]['conditional_logic'] = [
      [
        [
          'field' => $select_field_key,
          'operator' => '==',
          'value' => $acf['sub_fields'][2]['name'],
        ],
      ],
    ];
    $acf['sub_fields'][3]['conditional_logic'] = [
      [
        [
          'field' => $select_field_key,
          'operator' => '==',
          'value' => $acf['sub_fields'][3]['name'],
        ],
      ],
    ];
    $acf['sub_fields'][4]['conditional_logic'] = [
      [
        [
          'field' => $select_field_key,
          'operator' => '==',
          'value' => $acf['sub_fields'][4]['name'],
        ],
      ],
    ];

    //
    // When we're finished with all our extra transforms on the ACF configuration for this block, we need to return the
    // array so it will be used in the parent layout/block.
    //
    return $acf;
  }
}

//
// That's it!
//
