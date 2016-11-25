jQuery(function($) {
  $('.dojo-rank-types-edit form[name=post]').submit(function() {
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

  $('.dojo-rank-types-edit input[name=title]').change(function() {
    $('.dojo-ranks-label').text($(this).val() + ' Ranks');
  });

  $('.dojo-rank-types-edit input[name=new_rank]').keydown(function(ev) {
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

  $('.dojo-rank-types-edit .dojo-add-rank').click(function(ev) {
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
        $.post(dojo.ajax('membership', 'delete_rank'), { rank_id: rank_id }, function(response) {
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
    });
  }

  $('.dojo-rank-types-edit .dojo-rank-list a').click(function(ev) {
    rankEdit(ev);
  });

  $('.dojo-rank-types-edit .dojo-rank-list .dojo-delete').click(function(ev) {
    rankDelete(ev);
  });

  checkRanks();

  $('.dojo-rank-types-edit .dojo-rank-list').sortable();
});

