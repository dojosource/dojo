<?php

if ( ! defined( 'ABSPATH' ) ) { die(); }

$enable_registration = $this->get_meta( 'registration_enable' );
$enable_guest        = $this->get_meta( 'registration_enable_guest' );
$enable_limit        = $this->get_meta( 'registration_enable_limit' );
$limit               = $this->get_meta( 'registration_limit' );
$enable_payment      = $this->get_meta( 'registration_enable_payment' );

$price = array();
$price_count = array();
for ( $rule = 1; $rule <= 5; $rule ++ ) {
    $price[$rule]       = $this->get_meta( 'registration_price' . $rule );
    $price_count[$rule] = $this->get_meta( 'registration_price_count' . $rule );
}

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
                <th scope="row">Enable Guest Registration</th>
                <td>
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
        </tbody>
    </table>

    <div class="price-rules" style="display:none;">
    <?php for ( $rule = 1; $rule <= 5; $rule ++ ) : ?>
        <table class="form-table" data-rule="<?php echo $rule ?>"<?php echo 1 == $rule ? '' : ' style="display:none;"' ?>>
            <tbody>
                <tr valign="top">
                    <th scope="row"><?php echo 1 == $rule ? 'Cost' : 'then cost' ?> per person</th>
                    <td>
                        <span>$<span><input type="text" id="price<?php echo $rule ?>" name="registration[price<?php echo $rule ?>]" size="8" style="display:inline;width:auto;" value="<?php echo esc_attr( $price[$rule] ) ?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">for <?php echo 1 == $rule ? 'first' : 'next' ?></th>
                    <td>
                        <select class="price-count" id="price-count<?php echo $rule ?>" name="registration[price_count<?php echo $rule ?>]" style="display:inline; width:auto;">
                        <?php for ( $count = 0; $count <= ( 5 == $rule ? 0 : 5 ); $count ++ ) : ?>
                            <option value="<?php echo $count ?>" <?php selected( $price_count[$rule], $count ) ?>><?php echo 0 == $count ? 'Unlimited' : $count ?></option>
                        <?php endfor; ?>
                        </select>
                        family members
                    </td>
            </tbody>
        </table>
    <?php endfor; ?>
    </div>
</div>

