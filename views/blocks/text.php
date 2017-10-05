<?php
/**
 * Text Block
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

$block_classes = $configure['element_class'];
?>

<div <?php if ( ! empty( $configure['element_id'] ) ) : ?> id="<?php echo $configure['element_id']; ?>" <?php endif; ?> class="layout-block-text <?php echo $block_classes; ?>">
  <?php echo do_shortcode( wpautop( $content['text'] ) ); ?>
</div>
