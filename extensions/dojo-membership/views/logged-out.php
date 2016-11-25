<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

if ( $this->get_setting( 'membership_use_wp_login' ) ) {
	$login_url = wp_login_url( $this->membership_url( '' ) );
} else {
	$login_url = $this->membership_url( '?login' );
}

$this->enqueue_ajax( 'signup' );
?>

<?php echo $settings->get( 'membership_signup_header' ) ?>

<div class="dojo-logged-out">
	<p>
	Already have an account? <a href="<?php echo $login_url ?>" title="Log In">Log in here</a>.
	</p>

	<form id="dojo-signup" action="#" method="POST">
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
			<div class="dojo-error" style="display:none;">
			</div>
		</div>
		<div class="dojo-field">
			<div class="dojo-please-wait" style="display:none;">Please wait...</div>
			<button type="submit" class="dojo-submit-button">Create Account</button>
		</div>
	</form>

	<div class="dojo-signup-results" style="display:none;">
		<h1>Thank You</h1>
	</div>
</div>


