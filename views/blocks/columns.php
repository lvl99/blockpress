<?php
/**
 * Columns Block
 *
 * Holds column blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

$block_classes = $configure['element_class'];
?>

<div <?php if ( ! empty( $configure['element_id'] ) ) : ?> id="<?php echo $configure['element_id']; ?>" <?php endif; ?> class="layout-block-columns row <?php echo $block_classes; ?>">
  <?php foreach ( $content['columns'] as $block_index => $block ) :
    $block_view_file = lvl99_acf_page_builder()->locate_block_view( $block['_builder']['block'], $block['_builder']['layout'] );
    echo lvl99_acf_page_builder()->render_view( $block_view_file, [
      'data' => $block,
    ] );
  endforeach; ?>
</div>
