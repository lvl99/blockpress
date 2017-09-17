<?php
/**
 * Image Block
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

$block_classes = $configure['element_class'];
?>

<div <?php if ( ! empty( $configure['element_id'] ) ) : ?> id="<?php echo $configure['element_id']; ?>" <?php endif; ?> class="layout-block-image <?php echo $block_classes; ?>">
  <figure>
    <img src="<?php echo $content['image']['url']; ?>" border="0" alt="<?php echo esc_attr( $content['image']['title'] ); ?>">
    <?php if ( ! empty( $content['image']['caption'] ) ) : ?>
    <figcaption>
      <?php echo $content['image']['caption']; ?>
    </figcaption>
    <?php endif; ?>
  </figure>
</div>
