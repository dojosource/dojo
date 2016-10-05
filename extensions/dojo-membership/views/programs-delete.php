<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$program = $this->selected_program;

if ( null === $program ) {
	wp_die( '<h1>Program not found!</h1>' );
}
?>

<div class="wrap">
	<form name="post" action="<?php echo esc_attr( $this->ajax( 'delete_program' ) ) ?>" method="post" id="post" autocomplete="off">
		<input type="hidden" id="program_id" name="program_id" value="<?php echo esc_attr( $program->ID ) ?>">
		<h1>Are you sure you want to delete the program: <?php echo esc_html( $program->title ) ?>?</h1>
		<p class="submit">
			<button class="button button-primary button-large">Yes Delete</button>
			<a href="<?php echo esc_attr( admin_url( '/admin.php?page=dojo-programs' ) ) ?>" style="margin-left: 20px;">Cancel</a>
		</p>
	</form>
</div>

