<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }
?>

<div class="wrap">
<h1>Dojo Rank Types <a href="?page=<?php echo esc_attr( $_REQUEST['page'] ) ?>&action=add-new" class="page-title-action">Add New</a></h1>
<?php
$this->rank_types_table->prepare_items();
$this->rank_types_table->display();
?>
</div>