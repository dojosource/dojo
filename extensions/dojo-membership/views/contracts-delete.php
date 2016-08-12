<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$contract = $this->selected_contract;

if ( null === $contract ) {
    wp_die( '<h1>Contract not found!</h1>' );
}
?>

<div class="wrap">
    <form name="post" action="<?php echo esc_attr( $this->ajax( 'delete_contract' ) ) ?>" method="post" id="post" autocomplete="off">
        <input type="hidden" id="contract_id" name="contract_id" value="<?php echo esc_attr( $contract->ID ) ?>">
        <h1>Are you sure you want to delete the contract: <?php echo esc_html( $contract->title ) ?>?</h1>
        <p class="submit">
            <button class="button button-primary button-large">Yes Delete</button>
            <a href="<?php echo esc_attr( admin_url( '/admin.php?page=dojo-contracts' ) ) ?>" style="margin-left: 20px;">Cancel</a>
        </p>
    </form>
</div>

