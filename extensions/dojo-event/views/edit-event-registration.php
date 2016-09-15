<?php

if ( ! defined( 'ABSPATH' ) ) { die(); }

$enable_registration = $this->get_meta( 'registration_enable' );
$enable_guest        = $this->get_meta( 'registration_enable_guest' );
$enable_limit        = $this->get_meta( 'registration_enable_limit' );
$limit               = $this->get_meta( 'registration_limit' );
$enable_payment      = $this->get_meta( 'registration_enable_payment' );
$price               = $this->get_meta( 'registration_price' );

$price_plan = new Dojo_Price_Plan( $price );

?>

<table class="form-table">
    <tbody>
        <tr valign="top">
            <th scope="row">Enable Registration</th>
            <td>
                <p>
                    <input type="checkbox" id="enable-registration" name="registration[enable]" value="1" <?php checked( $enable_registration, 1 ) ?>>
                </p>
            </td>
        </tr>
    </tbody>
</table>
 
<div class="registration-options" style="display:none;">
    <table class="form-table">
        <tbody>
            <tr valign="top">
                <th scope="row" style="text-decoration:line-through">Enable Guest Registration</th>
                <td>
                    (Feature coming soon)
                    <p>
                        <input type="checkbox" id="enable-guest" name="registration[enable_guest]" value="1" <?php checked( $enable_guest, 1 ) ?>>
                    </p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Registration Limit</th>
                <td>
                    <p>
                        <label for="limit-registration">
                            <input type="checkbox" id="limit-registration" name="registration[enable_limit]" value="1" <?php checked( $enable_limit, 1 ) ?>>
                            Limit how many people can register for this event.
                        </label>
                    </p>
                    <p class="reg-limit" style="display:none;">
                        <label for="reg-limit">Max number of people: </label>
                        <input type="text" id="reg-limit" name="registration[limit]" size="8" style="width:auto;" value="<?php echo esc_attr( $limit ) ?>">
                    </p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Registration Cost</th>
                <td>
                    <label for="take-payment">
                        <input type="checkbox" id="take-payment" name="registration[enable_payment]" value="1" <?php checked( $enable_payment, 1 ) ?>>
                        Require payment to register.
                    </label>
                </td>
            </tr>
            <tr valign="top" class="price-rules" style="display:none;">
                <th></th>
                <td>
                    <?php $price_plan->render_edit() ?>
                </td>
            </tr>
        </tbody>
    </table>

</div>

