<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$contract = $this->selected_contract;
$programs = $this->programs;
$documents = $this->documents;
$contract_programs = $this->contract_programs;
$contract_price_plan = $this->contract_price_plan;
$contract_documents = $this->contract_documents;

if ( null === $contract ) {
    $new = true;
} else {
    $new = false;
}
?>

<div class="wrap dojo">
    <h1><?php echo $new ? 'New Contract' : 'Edit Contract' ?></h1>

    <form name="post" action="<?php echo esc_attr( $this->ajax( 'save_contract' ) ) ?>" method="post" id="post" autocomplete="off">
        <?php if ( ! $new ) : ?>
        <input type="hidden" id="contract_id" name="contract_id" value="<?php echo esc_attr( $contract->ID ) ?>">
        <input type="hidden" id="is_active" name="is_active" value="<?php echo esc_attr( $contract->is_active ) ?>">
        <?php else : ?>
        <input type="hidden" id="is_active" name="is_active" value="1">
        <?php endif; ?>
        
        <div id="titlediv">
            <input type="text" name="title" size="30" value="<?php echo $new ? '' : esc_attr( $contract->title ) ?>" id="title" spellcheck="true" autocomplete="off" placeholder="Enter title here" required>
        </div>

        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row">Contract Length</th>
                    <td>
                        <input type="text" id="term_months" name="term_months" class="small-text" value="<?php echo $new ? '' : esc_attr( $contract->term_months ) ?>"> Months
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Cancellation Policy</th>
                    <td>
                        <select id="cancellation_policy" name="cancellation_policy">
                            <option <?php echo $new ? '' : selected( $contract->cancellation_policy, 'anytime' ) ?> value="anytime">Any Time</option>
                            <option <?php echo $new ? '' : selected( $contract->cancellation_policy, 'none' ) ?> value="none">No Cancellations</option>
                            <option <?php echo $new ? '' : selected( $contract->cancellation_policy, 'days' ) ?> value="days">Notice Required</option>
                        </select>
                    </td>
                </tr>

                <tr valign="top" class="cancellation-days-row"<?php echo ( ! $new && 'days' == $contract->cancellation_policy ) ? '' : ' style="display:none;"' ?>>
                    <th scope="row">Cancellation Notice Required</th>
                    <td>
                        <input type="text" id="cancellation_days" name="cancellation_days" class="small-text" value="<?php echo $new ? '' : esc_attr( $contract->cancellation_days ) ?>"> Days in advance
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Qualifying Restrictions</th>
                    <td>
                        <input type="radio" id="membership_restriction" name="membership_restriction" value="none" <?php echo $new ? 'checked' : checked( $contract->new_memberships_only . $contract->continuing_memberships_only, '00') ?>>
                        No restrictions.<br />
                        <input type="radio" id="membership_restriction" name="membership_restriction" value="new" <?php echo $new ? '' : checked( $contract->new_memberships_only, '1') ?>>
                        Only available to new members.<br />
                        <input type="radio" id="membership_restriction" name="membership_restriction" value="continuing" <?php echo $new ? '' : checked( $contract->continuing_memberships_only, '1' ) ?>>
                        Only available to existing members continuing their contract.<br />
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="form-table">
            <tbody>
                 <tr valign="top">
                    <th scope="row">Price Plan</th>
                    <td>
                        <?php $contract_price_plan->render_edit() ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="form-table">
            <tbody>
                 <tr valign="top">
                    <th scope="row">Terms and Conditions</th>
                    <td>
                        <label for="terms_url">URL of Terms and Conditions Page</label><br />
                        <input type="text" class="regular-text" id="terms_url" name="terms_url" value="<?php echo $new ? '' : esc_attr( $contract->terms_url ) ?>">
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row">Contract Documents</th>
                    <td>
                        <p>Select documents applicants will need to complete for this contract.</p>
                        <?php if ( 0 == count( $documents ) ) : ?>
                            <div class="dojo-info">
                                No documents created yet. Select <a href="<?php echo esc_attr( admin_url( 'admin.php?page=dojo-documents' ) ) ?>">Documents</a> from the menu to manage and add documents.
                            </div>
                        <?php else : ?>
                            <?php foreach ( $documents as $document ) : ?>
                            <label for="document_<?php echo esc_attr( $document->ID ) ?>">
                                <input type="checkbox" id="document_<?php echo esc_attr( $document->ID ) ?>" name="document_<?php echo esc_attr( $document->ID ) ?>" value="<?php echo esc_attr( $document->ID ) ?>" <?php echo isset( $contract_documents[ $document->ID ] ) ? 'checked="checked"' : '' ?>>
                                <?php echo esc_html( $document->title ) ?><br />
                            </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row">Included Programs</th>
                    <td>
                        <?php foreach ( $programs as $program ) : ?>
                        <label for="program_<?php echo esc_attr( $program->ID ) ?>">
                            <input type="checkbox" id="program_<?php echo esc_attr( $program->ID ) ?>" name="program_<?php echo esc_attr( $program->ID ) ?>" value="<?php echo esc_attr( $program->ID ) ?>" <?php echo isset( $contract_programs[ $program->ID ] ) ? 'checked="checked"' : '' ?>>
                            <?php echo esc_html( $program->title ) ?><br />
                        </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary button-large">Save Contract</button>
        </p>

    </form>
</div>

<script>
jQuery(function($) {
    $('#cancellation_policy').change(function() {
        if ('days' == $(this).val()) {
            $('.cancellation-days-row').show();
        }
        else {
            $('.cancellation-days-row').hide();
        }
    });
});
</script>
