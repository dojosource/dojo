<?php
/**
 * Dojo membership contracts table for displaying enrollment contract lists
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Dojo_Contracts_Table extends WP_List_Table {
    private $model;
    private $program_id;

    public function __construct( $model, $program_id = null ) {
        $this->model = $model;
        $this->program_id = $program_id;

        parent::__construct( array(
            'plural' => 'contracts',
            'singular' => 'contract',
        ) );
    }

    public function get_columns() {
        return array(
            'title' => 'Contract',
            'term_months' => 'Term Months',
            'cancellation_policy' => 'Cancellation Policy',
            'restrictions' => 'Restrictions',
            'is_active' => 'Active',
        );
    }

    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array( $columns, $hidden, $sortable );

        if ( null === $this->program_id ) {
            $this->items = $this->model->get_contracts( ARRAY_A );
        } else {
            $this->items = $this->model->get_program_contracts( $this->program_id, ARRAY_A );
        }
    }

    protected function column_default( $item, $column_name ) {
        if ( isset( $item[ $column_name ] ) ) {
            return $item[ $column_name ];
        }
    }

    protected function column_title( $item ) {
        $href_edit = sprintf( '?page=dojo-contracts&action=edit&contract=%s', $item['ID'] );
        $href_delete = sprintf( '?page=dojo-contracts&action=delete&contract=%s', $item['ID'] );

        $actions = array(
            'edit'      => '<a href="' . $href_edit . '">Edit</a>',
            'delete'    => '<a href="' . $href_delete . '">Delete</a>',
        );

        return sprintf('<a class="row-title" href="%1$s">%2$s</a> %3$s', $href_edit, $item['title'], $this->row_actions( $actions ) );
    }

    protected function column_restrictions( $item ) {
        if ( '1' == $item['new_memberships_only'] ) {
            return 'New Members Only';
        }
        if ( '1' == $item['continuing_memberships_only'] ) {
            return 'Continuing Only';
        }
        return 'None';
    }

    protected function column_cancellation_policy( $item ) {
        switch ( $item['cancellation_policy'] ) {
            case 'anytime'  : return 'Any time';
            case 'none' : return 'No cancellations';
            case 'days' : return $item['cancellation_days'] . ' days notice';
        }
    }

    protected function column_is_active( $item ) {
        return '1' == $item['is_active'] ? 'yes' : 'no';
    }
}

