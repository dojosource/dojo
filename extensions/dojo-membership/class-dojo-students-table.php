<?php
/**
 * Dojo membership students table
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Dojo_Students_Table extends WP_List_Table {
    private $model;

    public function __construct( $model ) {
        $this->model = $model;

        parent::__construct( array(
            'plural' => 'students',
            'singular' => 'student',
        ) );
    }

    public function get_columns() {
        return array(
            'student_name' => 'Student',
            'dob' => 'DOB',
            'start_date' => 'Started',
            'status' => 'Status',
        );
    }

    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array( $columns, $hidden, $sortable );

        $this->items = $this->model->get_students( ARRAY_A );
    }

    protected function column_default( $item, $column_name ) {
        if ( isset( $item[ $column_name ] ) ) {
            return $item[ $column_name ];
        }
    }

    protected function column_dob( $item ) {
        return date( 'm/d/Y', strtotime( $item['dob'] ) );
    }

    protected function column_start_date( $item ) {
        if ( null === $item['start_date'] ) {
            return 'Not Started';
        }
        return date( 'm/d/Y', strtotime( $item['start_date'] ) );
    }

    protected function column_status( $item ) {
        return Dojo_Membership::instance()->describe_status( $item['status'] );
    }

    protected function column_student_name( $item ) {
        $href_edit = sprintf( '?page=dojo-students&action=edit&student=%s', $item['ID'] );

        $actions = array(
            'edit'      => '<a href="' . $href_edit . '">Edit</a>',
        );

        $student_name = Dojo_Membership::instance()->student_name( $item );

        return sprintf('<a class="row-title" href="%1$s">%2$s</a> %3$s', $href_edit, $student_name, $this->row_actions( $actions ) );
    }
}
