<?php
$post = $this->post;
?>

<h1>
    <?php echo $post->post_title ?>
    <br>
    <span class="dojo-event-start" style="font-size:.6em;"><?php echo Dojo_Event::get_formatted_start_time( $post ) ?></span>
</h1>
<p>
<?php echo $post->post_content ?>
</p>

