<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

if ( ! $this->selected_rank_type ) {
	$is_new = true;
} else {
	$rank_type = $this->selected_rank_type;
	$ranks = $this->ranks;
	$is_new = false;
}

wp_enqueue_script( 'jquery-ui-sortable' );

$this->enqueue_ajax( 'delete_rank' );
?>

<div class="wrap dojo-rank-types-edit">
	<h1><?php echo $is_new ? 'New' : 'Edit' ?> Rank Type <a href="?page=<?php echo esc_attr( $_REQUEST['page'] ) ?>&action=add-new" class="page-title-action">Add New</a></h1>

	<form name="post" action="<?php echo esc_attr( $this->ajax( 'save_rank_type' ) ) ?>" method="post" id="post" autocomplete="off">
		<?php if ( ! $is_new ) : ?>
		<input type="hidden" id="rank_type_id" name="rank_type_id" value="<?php echo esc_attr( $rank_type->ID ) ?>">
		<?php endif; ?>
		<div id="titlediv">
			<input type="text" name="title" size="30" value="<?php echo $is_new ? '' : esc_attr( $rank_type->title ) ?>" id="title" spellcheck="true" autocomplete="off" placeholder="Enter title here">
		</div>

		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">Description</th>
					<td>
						<textarea id="description" name="description" rows="8" class="large-text"><?php echo $is_new ? '' : esc_html( $rank_type->description ) ?></textarea>
					</td>
				</tr>
				<tr valign="top">
					<th class="dojo-ranks-label" scope="row"><?php echo $is_new ? '' : esc_html( $rank_type->title ) ?> Ranks</th>
					<td>
						<p class="dojo-drag-notice" style="display:none;">Drag ranks to change order.</p>
						<ul class="dojo-rank-list dojo-sortable" style="overflow:auto;">
							<?php if ( ! $is_new ) : ?>
								<?php foreach ( $ranks as $rank ) : ?>
								<li data-rank-id="<?php echo esc_attr( $rank->ID ) ?>">
									<span class="dojo-rank"><?php echo esc_html( $rank->title ) ?></span>
									<span class="dojo-delete dashicons dashicons-dismiss dojo-right dojo-red" style="cursor:pointer;"></span>
									<a href="javascript:;" class="dojo-right" style="margin-right:10px;">edit</a>
								</li>
								<?php endforeach; ?>
							<?php endif; ?>
						</ul>
						<input type="text" name="new_rank" class="regular-text" placeholder="Enter new rank">
						<button class="button dojo-add-rank">Add Rank</button>
					</td>
				</tr>
			</tbody>
		</table>


		<p class="submit">
			<button class="button button-primary button-large">Save Rank Type</button>
		</p>

	</form>
</div>

