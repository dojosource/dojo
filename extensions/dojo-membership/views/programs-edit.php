<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$program = $this->selected_program;
$program_contracts = $this->program_contracts;

if ( null === $program ) {
    wp_die( '<h1>Program not found!</h1>' );
}

?>

<div class="wrap">
    <h1>Edit Program <a href="?page=<?php echo esc_attr( $_REQUEST['page'] ) ?>&action=add-new" class="page-title-action">Add New</a></h1>

    <form name="post" action="<?php echo esc_attr( $this->ajax( 'save_program' ) ) ?>" method="post" id="post" autocomplete="off">
        <input type="hidden" id="program_id" name="program_id" value="<?php echo esc_attr( $program->ID ) ?>">
        <div id="titlediv">
            <input type="text" name="title" size="30" value="<?php echo esc_attr( $program->title ) ?>" id="title" spellcheck="true" autocomplete="off" placeholder="Enter title here">
        </div>

        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row">Description</th>
                    <td>
                        <textarea id="description" name="description" rows="8" class="large-text"><?php echo esc_html( $program->description ) ?></textarea>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Minimum Age Limit (optional)</th>
                    <td>
                        <input type="text" id="min_age" name="min_age" class="small-text" value="<?php echo '0' == $program->min_age ? '' : esc_attr( $program->min_age ) ?>"> Years
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Maximum Age Limit (optional)</th>
                    <td>
                        <input type="text" id="max_age" name="max_age" class="small-text" value="<?php echo '0' == $program->max_age ? '' : esc_attr( $program->max_age ) ?>"> Years
                    </td>
                </tr>
            </tbody>
        </table>

        <?php if ( 0 == count( $program_contracts ) ) : ?>
        <div class="dojo-warn">
            This program is not in a contract.<br />
            You will need to include this program in a contract for it to become available to members.
        </div>
        <?php endif; ?>

        <p class="submit">
            <button class="button button-primary button-large">Save Program</button>
        </p>

    </form>
</div>


