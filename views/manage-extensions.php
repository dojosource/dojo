<?php

if ( ! defined( 'ABSPATH' ) ) { die(); }

$settings = Dojo_Settings::instance();
$active = ( '' != $settings->get( 'site_key' ) );
$extension_manager = Dojo_Extension_Manager::instance();
?>

<div class="dojo-manage-extensions">
<?php if ( $active ) : ?>
    <span style="margin-right:10px;font-size:16px;">Contacting Dojo Source...</span>
    <img src="/wp-includes/js/tinymce/skins/lightgray/img/loader.gif" style="vertical-align:middle;">
<?php else : ?>
    <div class="dojo-warn">
        Please enter a site key to access add-ons.
    </div>
<?php endif; ?>
</div>

<?php if ( $active ) : ?>
<script>
jQuery(function($) {
    $.post('<?php echo $extension_manager->ajax( 'get_management_view' ) ?>', {}, function(response) {
        $('.dojo-manage-extensions').html(response);
    });
});
</script>
<?php endif; ?>
