<?php
/**
 * Columns Block
 *
 * Holds column blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

if ( ! empty( $content['columns'] ) ) :
  $block_classes = $configure['element_class']; ?>

  <div <?php if ( ! empty( $configure['element_id'] ) ) : ?> id="<?php echo $configure['element_id']; ?>" <?php endif; ?> class="layout-block-columns <?php echo $block_classes; ?>">
    <?php foreach ( $content['columns'] as $block_index => $block ) :
      $block_view_file = lvl99_acf_page_builder()->locate_block_view( $block['_builder']['block'], $block['_builder']['layout'] );
      echo lvl99_acf_page_builder()->render_view( $block_view_file, [
        'layout' => $block['_builder']['layout'],
        'parent' => $_builder['cache_key'] . '_' . $block_index, // $_field_key
        'parent_cache_key' => $_builder['cache_key'],
        'index' => $block_index,
        'block' => $block['_builder']['block'],
        'data' => $block,
      ] );
    endforeach; ?>
  </div>

<?php endif;
