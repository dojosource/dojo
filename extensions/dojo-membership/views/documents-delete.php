<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$document = $this->selected_document;

if ( null === $document ) {
	wp_die( '<h1>Document not found!</h1>' );
}
?>

<div class="wrap">
	<form name="post" action="<?php echo esc_attr( $this->ajax( 'delete_document' ) ) ?>" method="post" id="post" autocomplete="off">
		<input type="hidden" id="document_id" name="document_id" value="<?php echo esc_attr( $document->ID ) ?>">
		<h1>Are you sure you want to delete the document: <?php echo esc_html( $document->title ) ?>?</h1>
		<p class="submit">
			<button type="submit" class="button button-primary button-large">Yes Delete</button>
			<a href="<?php echo esc_attr( admin_url( '/admin.php?page=dojo-documents' ) ) ?>" style="margin-left: 20px;">Cancel</a>
		</p>
	</form>
</div>

