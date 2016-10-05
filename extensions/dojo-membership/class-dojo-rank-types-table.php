<?php
/**
 * Dojo membership rank types table
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Dojo_Rank_Types_Table extends WP_List_Table {
	private $model;

	public function __construct( $model ) {
		$this->model = $model;

		parent::__construct( array(
			'plural' => 'rank types',
			'singular' => 'rank type',
		) );
	}

	public function get_columns() {
		return array(
			'title' => 'Rank Type',
			'description' => 'Description',
		);
	}

	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $this->model->get_rank_types( ARRAY_A );
	}

	protected function column_default( $item, $column_name ) {
		if ( isset( $item[ $column_name ] ) ) {
			return $item[ $column_name ];
		}
	}

	protected function column_title( $item ) {
		$href_edit = sprintf( '?page=%s&action=edit&rank_type=%s', $_REQUEST['page'], $item['ID'] );
		$href_delete = sprintf( '?page=%s&action=delete&rank_type=%s', $_REQUEST['page'], $item['ID'] );

		$actions = array(
			'edit'      => '<a href="' . $href_edit . '">Edit</a>',
			'delete'    => '<a href="' . $href_delete . '">Delete</a>',
		);

		return sprintf('<a class="row-title" href="%1$s">%2$s</a> %3$s', $href_edit, $item['title'], $this->row_actions( $actions ) );
	}
}
