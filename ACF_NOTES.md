# ACF Notes

Here I'm collecting notes for what I wish ACF did slightly differently.

  - Store post meta layout data as a single JSON object (or would this screw with WPML translation?)
  - I wish I didn't have to duplicate block configuration to have it referenceable/usable within another
    layout/block. I wish I could define a single block and have its instance be shared between any other
    entity that wants to use it (it would make the configuration object smaller too).
  - I wish flexible content editing form in the backend would support tab layout fields.
  - I wish I could assign a value to display for a collapsed layout, much like one can with the repeater field
  - I wish I could `get_field` using the field's key, not name.
