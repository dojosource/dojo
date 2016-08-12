<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }
?>

<div class="wrap">
<h1>Dojo Students</h1>
<?php
$this->students_table->prepare_items();
$this->students_table->display();
?>
</div>