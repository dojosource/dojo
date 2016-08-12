<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }
?>

<div class="wrap">
<h1>Dojo Documents<a href="?page=<?php echo esc_attr( $_REQUEST['page'] ) ?>&action=add-new" class="page-title-action">Add New</a></h1>
<?php
$this->documents_table->prepare_items();
$this->documents_table->display();
?>
</div>
