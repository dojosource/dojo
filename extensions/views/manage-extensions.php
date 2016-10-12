<?php

if ( ! defined( 'ABSPATH' ) ) { die(); }

$info = $this->extension_info;

$settings = Dojo_Settings::instance();
$extensions = $this->extensions();
$core_extensions = $this->core_extensions();
$installed_extensions = array();

foreach ( $extensions as $extension_class ) {
	if ( isset( $core_extensions[ $extension_class ] ) ) {
		// no configuration necessary for core extensions, they are always enabled
		continue;
	}
	$installed_extensions[ strtolower( substr( $extension_class, 5 ) ) ] = $this->get_instance( $extension_class );
}

$available_extensions = array();
foreach ( $info['extensions'] as $extension_id => $extension_title ) {
	if ( ! isset( $installed_extensions[ $extension_id ] ) ) {
		$available_extensions[ $extension_id ] = $extension_title;
	}
}



?>

<div style="font-size:20px;">
	<strong>Current License:</strong> <?php echo esc_html( $info['license'] ) ?>
</div>

<div class="dojo-clear-space"></div>

<div class="dojo-error-container" style="display:none;">
	<div class="dojo-error dojo-danger"></div>
	<div class="dojo-clear-space"></div>
</div>

<?php foreach ( $installed_extensions as $extension_id => $extension ) : ?>
<div class="dojo-extension-block" data-extension="<?php echo esc_attr( $extension_id ) ?>">
	<div class="dojo-large-icon dojo-left">
		<?php if ( isset( $info['icons'][ $extension_id ] ) ) : ?>
		<span class="dashicons dashicons-<?php echo $info['icons'][ $extension_id ] ?>"></span>
		<?php endif; ?>
	</div>
	<div class="dojo-left">
		<div class="dojo-extension-title">
			<?php echo esc_html( $extension->title() ) ?>
		</div>
		<?php if ( ! isset( $info['extensions'][ $extension_id ] ) ) : ?>
		<div class="dojo-red">
			Not licensed
		</div>
		<?php else : ?>
			<?php if ( $info['versions'][ $extension_id ] == $extension->version() ) : ?>
			<div class="dojo-green">
				Latest version
			</div>
			<?php else : ?>
			<div class="dojo-yellow">
				Updates available
			</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<div class="dojo-right">
		<div class="dojo-extension-version">
			v<?php echo esc_html( $extension->version() ) ?>
		</div>
		<div style="text-align:right;margin-bottom:5px;">
			<a href="javascript:;" class="dojo-remove dojo-red-link">Remove</a>
			<div style="display:none;">Removing... <img src="/wp-admin/images/spinner.gif"></div>
		</div>
		<?php if ( isset( $info['versions'][ $extension_id ] ) && $info['versions'][ $extension_id ] != $extension->version() ) : ?>
		<div>
			<a href="javascript:;" class="button button-primary dojo-update">Update Now</a>
			<div style="display:none;">Installing... <img src="/wp-admin/images/spinner.gif"></div>
		</div>
		<?php endif; ?>
	</div>
	<div class="dojo-clear"></div>
</div>
<?php endforeach; ?>

<?php foreach ( $available_extensions as $extension_id => $extension_title ) : ?>
<div class="dojo-extension-block" data-extension="<?php echo esc_attr( $extension_id ) ?>">
	<div class="dojo-large-icon dojo-left">
		<span class="dashicons dashicons-<?php echo $info['icons'][ $extension_id ] ?>"></span>
	</div>
	<div class="dojo-left">
		<div class="dojo-extension-title">
			<?php echo esc_html( $extension_title ) ?>
		</div>
		<div>
			Available to download
		</div>
	</div>
	<div class="dojo-right">
		<a href="javascript:;" class="button button-primary dojo-install">Install Now</a>
		<div style="display:none;">Installing... <img src="/wp-admin/images/spinner.gif"></div>
	</div>
	<div class="dojo-clear"></div>
	<div class="dojo-extension-info">
		<?php echo esc_html( $info['descriptions'][ $extension_id ] ) ?>
	</div>
</div>
<?php endforeach; ?>


<script>
jQuery(function($) {

	function doAction(action, context) {
		var btn = context;
		btn.hide();
		btn.next().show();
		$('.dojo-error-container').hide();
		var extension = btn.closest('.dojo-extension-block').attr('data-extension');
		var url = '<?php echo $this->ajax( '__ACTION__' ) ?>'.replace('__ACTION__', action);
		$.post(url, { extension: extension }, function(response) {
			if (response.indexOf('process_success') != -1) {
				window.location.reload();
			}
			else {
				btn.show();
				btn.next().hide();
				$('.dojo-error').html(response);
				$('.dojo-error-container').show();
			}
		});
	};

	$('.dojo-install').click(function() {
		doAction('install_extension', $(this));
	});

	$('.dojo-update').click(function() {
		doAction('update_extension', $(this));
	});

	$('.dojo-remove').click(function() {
		if (confirm('Are you sure you want to remove this add-on?')) {
			doAction('remove_extension', $(this));
		}
	});
});
</script>
