<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$this->enqueue_ajax( 'login' );
?>

<div class="dojo-login-container" style="max-width:580px;margin:0 auto;">
    <strong>Please Log In</strong>
    <p>or <a href="<?php echo esc_attr( $this->membership_url( '' ) ) ?>">create an account</a>.<p>

    <form id="dojo-login" action="#" method="POST">
        <div class="dojo-field">
			<?php if ( $this->get_setting( 'membership_enable_username' ) ) : ?>
				<label for="login">Username or Email</label>
			<?php else : ?>
				<label for="login">Email</label>
			<?php endif; ?>
            <input type="text" id="login" name="login" size="30" required>
        </div>
        <div class="dojo-field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" size="30" required>
        </div>
        <div class="dojo-field">
            <div class="dojo-error" style="display:none;">
            </div>
        </div>

        <div class="dojo-field">
            <div class="dojo-please-wait" style="display:none;">Please wait...</div>
            <button type="submit" class="dojo-submit-button">Log In</button>
        </div>

		<div class="dojo-field">
			<div class="dojo-name">&nbsp;</div>
			<div class="dojo-value">
				Forgot password? <a href="<?php echo esc_attr( wp_lostpassword_url() ) ?>">click here</a>
			</div>
		</div>

    </form>
</div>

<script>

</script>
