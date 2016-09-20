<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$rank_type = $this->selected_rank_type;

if ( null === $rank_type ) {
    wp_die( '<h1>Rank type not found!</h1>' );
}
?>

<div class="wrap">
    <form name="post" action="<?php echo esc_attr( $this->ajax( 'delete_rank_type' ) ) ?>" method="post" id="post" autocomplete="off">
        <input type="hidden" id="rank_type_id" name="rank_type_id" value="<?php echo esc_attr( $rank_type->ID ) ?>">
        <h1>Are you sure you want to delete the rank type: <?php echo esc_html( $rank_type->title ) ?>?</h1>
        <div class="dojo-danger">All student ranks associated with this rank type will be deleted as well!</div>
        <p class="submit">
            <button class="button button-primary button-large">Yes Delete</button>
            <a href="<?php echo esc_attr( admin_url( '/admin.php?page=dojo-ranks' ) ) ?>" style="margin-left: 20px;">Cancel</a>
        </p>
    </form>
</div>
