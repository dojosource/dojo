<?php echo $settings->get( 'membership_signup_header' ) ?>
<p>
Already have an account? <a href="<?php echo wp_login_url( get_permalink() ); ?>" title="Log In">Log in here</a>.
</p>

<form id="dojo-signup" action="<?= esc_url( $this->ajax( 'signup' ) ) ?>" method="POST">
    <div class="dojo-field">
        <label for="firstname">First Name</label>
        <input type="text" id="firstname" name="firstname" size="30" required>
    </div>
    <div class="dojo-field">
        <label for="lastname">Last Name</label>
        <input type="text" id="lastname" name="lastname" size="30" required>
    </div>
    <div class="dojo-field">
        <label for="email">Email</label>
        <input type="text" id="email" name="email" size="30" required>
    </div>
    <div class="dojo-field">
        <label for="phone">Phone</label>
        <input type="text" id="phone" name="phone" size="30" required>
    </div>
    <?php if ( '1' == $settings->get( 'membership_enable_username' ) ) : ?>
    <div class="dojo-field">
        <label for="username">Login Username</label>
        <input type="text" id="username" name="username" size="30" required>
    </div>
    <?php endif; ?>
    <div class="dojo-field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" size="30" required>
    </div>
    <div class="dojo-field">
        <label for="confirmpassword">Confirm Password</label>
        <input type="password" id="confirmpassword" name="confirmpassword" size="30" required>
    </div>
    <div class="dojo-field">
        <div class="dojo-error">
        </div>
    </div>
    <div class="dojo-field">
        <button type="submit">Create Account</button>
    </div>
</form>

<div class="dojo-signup-results" style="display:none;">
    <h1>Thank You</h1>
</div>

<script>
jQuery(function($) {
    $('#dojo-signup').submit(function(e) {
        e.preventDefault();

        var data = {};
        $(this).find('input').each(function() {
            var name = $(this).attr('name');
            var val = $(this).val();
            data[name] = val;
        });

        $.post('<?= $this->ajax( 'signup' ) ?>', data, function(response) {
            if (response != 'success') {
                $('.dojo-error').html(response);
                $('.dojo-error').show();
            }
            else {
                window.location.reload();
            }
        });
    });
});
</script>

