<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$document = $this->selected_document;

if ( null === $document ) {
    $new = true;
} else {
    $new = false;
}
?>

<div class="wrap dojo">
    <h1><?php echo $new ? 'New Document' : 'Edit Document' ?></h1>

    <form name="post" action="<?php echo esc_attr( $this->ajax( 'save_document' ) ) ?>" method="post" id="post" autocomplete="off" enctype="multipart/form-data">
        <?php if ( ! $new ) : ?>
        <input type="hidden" id="document_id" name="document_id" value="<?php echo esc_attr( $document->ID ) ?>">
        <?php endif; ?>
        
        <div id="titlediv">
            <input type="text" name="title" size="30" value="<?php echo $new ? '' : esc_attr( $document->title ) ?>" id="title" spellcheck="true" autocomplete="off" placeholder="Enter title here" required>
        </div>

        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row">Upload PDF File</th>
                    <td>
                        <?php if ( ! empty( $document->filename ) ) : ?>
                        <a href="<?php echo esc_attr( Dojo::instance()->url_of( 'docs/' . $document->ID . '/' . $document->filename ) ) ?>" download><?php echo esc_html( $document->filename ) ?></a>
                        <br />
                        <?php endif; ?>
                        <p>Max upload file size: <?php echo esc_html( ini_get( 'upload_max_filesize' ) ) ?></p>
                        <?php if ( isset( $_GET['e'] ) ) : ?>
                        <div class="dojo-danger" style="margin-bottom:10px"><?php echo esc_html( $_GET['e'] ) ?></div>
                        <?php endif; ?>
                        <input name="doc" type="file" style="margin-bottom:10px;">
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary button-large save-document-button">Save Document</button>
        </p>
    </form>
</div>

<script>
jQuery(function($) {
});
</script>

