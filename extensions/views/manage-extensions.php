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

<div class="dojo-info" style="font-size:16px;">
    <strong>Current License:</strong> <?php echo esc_html( $info['license'] ) ?>
</div>
<div class="dojo-clear-space"></div>

<?php foreach ( $installed_extensions as $extension_id => $extension ) : ?>
<div class="dojo-extension-block">
    <div class="dojo-large-icon dojo-left">
        <span class="dashicons dashicons-<?php echo $info['icons'][ $extension_id ] ?>"></span>
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
            <?php echo esc_html( $extension->version() ) ?>
        </div>
        <?php if ( $info['versions'][ $extension_id ] != $extension->version() ) : ?>
        <div>
            <a href="javascript:;" class="button button-primary">Update</a>
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

    $('.dojo-install').click(function() {
        $(this).hide();
        $(this).next().show();
        var extension = $(this).closest('.dojo-extension-block').attr('data-extension');
        $.post('<?php echo $this->ajax( 'install_extension' ) ?>', { extension: extension }, function(response) {
            $(this).closest('.dojo-extension-block').html(response);
        });
    });
});
</script>
