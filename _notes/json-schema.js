/**
 * This is a draft JSON schema (presented in JS to support comments) for editor apps to use the layout and block
 * data provided by the ACF BlockPress
 *
 * @author Matt Scheurich <matt@lvl99.com>
 * @date 2017-09-10
 */

var exampleLayout = {
  // The unique ACF key for the layout
  "key": "field_asdfasdfasdfasdfasdf",

  // The human-machine-readable label for the layout (used when saving information to DB and for rendering templates)
  "name": "page",

  // The human-readable label for the layout
  "label": "Page",

  // A description of the layout
  "description": "",

  // Further instructions to inform the user on how this layout is used
  "instructions": "",

  // The blocks that this layout has access to
  "blocks": [
    "text",
    "image",
    "carousel",

    // Could also be object containing the configs of each block
  ],

  // @TODO potentially more... and finish this...
}

var exampleBlock = {
  // The key for the block
  "key": "block_asdfasdfasdfasdfasdf",

  // The key for the parent layout (if it is part of one)
  "layout": "field_asdfasdfasdfasdfasdf",

  // The human-machine-readable label for the block (used when saving information to DB and for rendering templates)
  "name": "text",

  // The human-readable label for the block
  "label": "Text",

  // @TODO finish this...
}
