<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

if ( ! $this->selected_rank_type ) {
    $is_new = true;
} else {
    $rank_type = $this->selected_rank_type;
    $ranks = $this->ranks;
    $is_new = false;
}

wp_enqueue_script( 'jquery-ui-sortable' );

?>

<div class="wrap">
    <h1><?php echo $is_new ? 'New' : 'Edit' ?> Rank Type <a href="?page=<?php echo esc_attr( $_REQUEST['page'] ) ?>&action=add-new" class="page-title-action">Add New</a></h1>

    <form name="post" action="<?php echo esc_attr( $this->ajax( 'save_rank_type' ) ) ?>" method="post" id="post" autocomplete="off">
        <?php if ( ! $is_new ) : ?>
        <input type="hidden" id="rank_type_id" name="rank_type_id" value="<?php echo esc_attr( $rank_type->ID ) ?>">
        <?php endif; ?>
        <div id="titlediv">
            <input type="text" name="title" size="30" value="<?php echo $is_new ? '' : esc_attr( $rank_type->title ) ?>" id="title" spellcheck="true" autocomplete="off" placeholder="Enter title here">
        </div>

        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row">Description</th>
                    <td>
                        <textarea id="description" name="description" rows="8" class="large-text"><?php echo $is_new ? '' : esc_html( $rank_type->description ) ?></textarea>
                    </td>
                </tr>
                <tr valign="top">
                    <th class="dojo-ranks-label" scope="row"><?php echo $is_new ? '' : esc_html( $rank_type->title ) ?> Ranks</th>
                    <td>
                        <p class="dojo-drag-notice" style="display:none;">Drag ranks to change order.</p>
                        <ul class="dojo-rank-list dojo-sortable" style="overflow:auto;">
                            <?php if ( ! $is_new ) : ?>
                                <?php foreach ( $ranks as $rank ) : ?>
                                <li data-rank-id="<?php echo esc_attr( $rank->ID ) ?>">
                                    <span class="dojo-rank"><?php echo esc_html( $rank->title ) ?></span>
                                    <span class="dojo-delete dashicons dashicons-dismiss dojo-right dojo-red" style="cursor:pointer;"></span>
                                    <a href="javascript:;" class="dojo-right" style="margin-right:10px;">edit</a>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                        <input type="text" name="new_rank" class="regular-text" placeholder="Enter new rank">
                        <button class="button dojo-add-rank">Add Rank</button>
                    </td>
                </tr>
            </tbody>
        </table>


        <p class="submit">
            <button class="button button-primary button-large">Save Rank Type</button>
        </p>

    </form>
</div>


<script>
jQuery(function($) {
    $('form[name=post]').submit(function() {
        // cancel out any in-process rank edits
        $('.dojo-rank-list .dojo-cancel-edit').click();

        var ranks=[];
        $('.dojo-rank-list li').each(function() {
            ranks.push({ id : $(this).attr('data-rank-id'), title : $(this).find('.dojo-rank').text() });
            var input = $('<input type="hidden" name="ranks">');
            input.val(JSON.stringify(ranks));
            $(this).append(input);
        });
    });

    function checkRanks() {
        if ($('.dojo-rank-list li').length >= 2) {
            $('.dojo-drag-notice').show();
        }
        else {
            $('.dojo-drag-notice').hide();
        }
    }

    $('input[name=title]').change(function() {
        $('.dojo-ranks-label').text($(this).val() + ' Ranks');
    });

    $('input[name=new_rank]').keydown(function(ev) {
        if (ev.keyCode == 13) {
            ev.preventDefault();
            $('.dojo-add-rank').click();
        }
    });

    function getRankLi(rank, rank_id) {
        var li = $('<li><span class="dojo-rank">' + rank + '</span> <span class="dojo-delete dashicons dashicons-dismiss dojo-right dojo-red" style="cursor:pointer;"></span><a href="javascript:;" class="dojo-right" style="margin-right:10px;">edit</a></li>');
        li.attr('data-rank-id', rank_id);
        li.find('a').click(function(ev) {
            rankEdit(ev);
        });
        li.find('.dojo-delete').click(function(ev) {
            rankDelete(ev);
        });
        return li;
    }

    $('.dojo-add-rank').click(function(ev) {
        ev.preventDefault();
        var rank = $('input[name=new_rank]').val();
        if (rank != '') {
            $('.dojo-rank-list').append(getRankLi(rank));
            checkRanks();
        }
        $('input[name=new_rank]').val('');
    });

    function rankEdit(ev) {
        var li = $(ev.currentTarget).closest('li');
        var rank = li.find('.dojo-rank').text();
        var rank_id = li.attr('data-rank-id');
        var input = $('<input type="text" style="width:auto;">');
        input.attr('value', rank);
        li.html('');
        li.append('<button class="button dojo-right">Save</button> <a href="javascript:;" class="dojo-cancel-edit dojo-right dojo-red-link" style="margin-right:10px;">cancel</a>');
        li.append(input);
        input.select();

        function saveRank() {
            rank = input.val();
            li.replaceWith(getRankLi(rank, rank_id));
        }
        function cancel() {
            li.replaceWith(getRankLi(rank, rank_id));
        }

        input.keydown(function(ev) {
            if (ev.keyCode == 13) {
                ev.preventDefault();
                saveRank();
            }
            if (ev.keyCode == 27) {
                cancel();
            }
        });
        li.find('a').click(function() {
            cancel();
        });
        li.find('button').click(function() {
            saveRank();
        });

    }

    function rankDelete(ev) {
        var li = $(ev.currentTarget).closest('li');
        var rank = li.find('.dojo-rank').text();
        var rank_id = li.attr('data-rank-id');

        li.html('');
        li.append('<button class="button dojo-right">Delete</button> <a href="javascript:;" class="dojo-cancel-edit dojo-right dojo-red-link" style="margin-right:10px;">cancel</a>');
        li.append('<strong>Delete this. Are you sure?</strong><br />' + rank);

        function confirmDelete() {
            if (rank_id) {
                $.post('<?php echo $this->ajax( 'delete_rank' ) ?>', { rank_id: rank_id }, function(response) {
                    if (response != 'success') {
                        alert(response);
                    }
                    else {
                        li.remove();
                    }
                });
            }
            else {
                li.remove();
            }
        }
        function cancel() {
            li.replaceWith(getRankLi(rank, rank_id));
        }

        li.find('a').click(function() {
            cancel();
        });

        li.find('button').click(function(ev) {
            ev.preventDefault();
            confirmDelete();
        })
    }

    $('.dojo-rank-list a').click(function(ev) {
        rankEdit(ev);
    });

    $('.dojo-rank-list .dojo-delete').click(function(ev) {
        rankDelete(ev);
    });

    checkRanks();

    $('.dojo-rank-list').sortable();
});
</script>
