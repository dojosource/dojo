<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }
?>

<div class="wrap">
    <h1>Add New Program</h1>

    <form name="post" action="<?php echo esc_attr( $this->ajax( 'new_program' ) ) ?>" method="post" id="post" autocomplete="off">
        <div id="titlediv">
            <input type="text" name="title" size="30" value="" id="title" spellcheck="true" autocomplete="off" placeholder="Enter title here" required>
        </div>

        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row">Description</th>
                    <td>
                        <textarea id="description" name="description" rows="8" class="large-text"></textarea>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Minimum Age Limit (optional)</th>
                    <td>
                        <input type="text" id="min_age" name="min_age" class="small-text"> Years
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Maximum Age Limit (optional)</th>
                    <td>
                        <input type="text" id="max_age" name="max_age" class="small-text"> Years
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="dojo-info">
            Don't forget to add this program to a <strong>contract</strong> to make it available to members.
        </div>

        <p class="submit">
            <button class="button button-primary button-large">Create Program</button>
        </p>

    </form>
</div>


