<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$sources = $this->sources;
$customer = $this->current_customer;

if ( isset( $this->supress_source_select_title ) && $this->supress_source_select_title ) {
    $show_title = false;
} else {
    $show_title = true;
}

if ( isset( $this->supress_source_select_add ) && $this->supress_source_select_add ) {
    $show_add = false;
} else {
    $show_add = true;
}
?>
<div class="dojo-source-select">
    <?php if ( $show_title ) : ?>
    <strong class="dojo-source-select-title">Select Payment Method</strong>
    <?php endif; ?>
    <div class="dojo-clear-space"></div>
    <?php foreach ( $sources as $source ) : ?>
    <input type="radio" name="source" value="<?php echo esc_attr( $source->source_id ) ?>" <?php checked( $source->source_id, $customer->default_source ) ?>>
    <?php echo esc_html( $source->brand . ' ending in **** ' . $source->last_4 ) ?>
    <a href="javascript:;" class="dojo-delete-source dojo-red-link" style="margin-left:20px;" data-source-id="<?php echo esc_attr( $source->source_id ) ?>">delete</a>
    <br />
    <?php endforeach; ?>
    <?php if ( $show_add ) : ?>
    <div class="dojo-add-payment-method-option">
        <input type="radio" name="source" value="new">
        Add Payment Method
    </div>
    <?php endif; ?>

    <div class="dojo-clear-space"></div>
</div>