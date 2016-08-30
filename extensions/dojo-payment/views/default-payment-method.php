<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$source = $this->default_source;
?>

<?php if ( $source ) : ?>
<div class="dojo-field">
    <strong>Default payment method:</strong>
    <br /><?php echo esc_html( $source->brand . ' ending in **** ' . $source->last_4 ) ?>
</div>
<?php else : ?>
<div class="dojo-field">No default payment method.</div>
<?php endif; ?>
