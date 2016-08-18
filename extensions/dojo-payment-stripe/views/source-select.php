<?php
$sources = $this->sources;
$customer = $this->current_customer;
?>
<div class="dojo-source-select">
    <strong>Select Payment Method</strong>
    <div class="dojo-clear-space"></div>
    <?php foreach ( $sources as $source ) : ?>
    <input type="radio" name="source" value="<?php echo esc_attr( $source->source_id ) ?>" <?php checked( $source->source_id, $customer->default_source ) ?>>
    <?php echo esc_html( $source->brand . ' ending in **** ' . $source->last_4 ) ?>
    <a href="javascript:;" class="dojo-delete-source dojo-red-link" style="margin-left:20px;" data-source-id="<?php echo esc_attr( $source->source_id ) ?>">delete</a>
    <br />
    <?php endforeach; ?>
    <input type="radio" name="source" value="new">
        Add Payment Method
    </input>

    <div class="dojo-clear-space"></div>
</div>