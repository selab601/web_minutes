$(document).ready(function() {
  // 全案件の行末尾に改行文字を加える
  $(".table-content.text").each(function() {
    ApplyLineBreaks(this);
  })

  var page_no = 1;
  var i = 0;
  while (true) {
    if (isPageBreakedByItem(page_no)) {
      addNewPage();
      var result = retrieveBreakingPageItem(page_no);
      var item = splitTextInItem(result.breaked_item, page_no);
      var hided_items = result.hided_items;
      addItemsToPage(item, result.hided_items, page_no+1);
      page_no++;
    } else {
      break;
    }
    // safety break
    if (++i>1000){break;}
  }
});

// book 末尾に空のページを加える
function addNewPage() {
  $('<div class="page"><div class="subpage"><div class="contents"></div></div></div>')
    .appendTo($('.book').last());

  var tableHtml = '<div class="table">'
    + '  <div class="table-row header">'
    + '    <div class="table-content no">No</div>'
    + '    <div class="table-content category">項目</div>'
    + '    <div class="table-content text">内容</div>'
    + '    <div class="table-content primary">優先度</div>'
    + '    <div class="table-content responsibility">担当</div>'
    + '    <div class="table-content deadline">期限</div>'
    + '    <div class="table-content follow">フォロー</div>'
    + '  </div>'
    + '</div>';
  $(tableHtml).appendTo($('.contents').last());
}

// 案件群をページに加える
function addItemsToPage($item, $items, page_no) {
  // テーブル要素は，案件テーブルの他に議事録情報，承認等欄の2つがある
  // また，要素番号は 0 から始まるので 1 を引く
  var index = page_no + 2 - 1;
  $item.appendTo($('.table:eq('+index+')'));
  $items.appendTo($('.table:eq('+index+')'));
}

// はみだしている案件群を取得
function retrieveBreakingPageItem(page_no) {
  var index = page_no - 1;
  $page_contents = $('.contents:eq('+index+')');
  if ($page_contents.get(0) === undefined) {
    return;
  }

  var position = $page_contents.position();
  // 外側の要素を取得してしまうことがあるので，各々少し内側(-5, 100)から取得
  var bottom = position.top + $page_contents.outerHeight(true) - 5;
  var left = position.left + 100;

  var item_no = document.elementFromAbsolutePoint(left,bottom);
  var $item = $(item_no).parent();
  var $breaked_items = $item.nextAll().clone();
  $item.nextAll().each(function(key, value) {
    $(this).remove();
  });

  return {
    breaked_item : $item,
    hided_items : $breaked_items
  };
}

// ページから案件がはみ出しているか
function isPageBreakedByItem(page_no) {
  var page_index = page_no - 1;
  var table_index = page_no + 2 - 1;
  $subpage = $('.subpage:eq('+page_index+')');
  $table = $('.table:eq('+table_index+')');
  if ($subpage.get(0) === undefined) {
    return;
  }
  var subpage_bottom = $subpage.position().top + $subpage.outerHeight(true);
  var table_bottom = $table.position().top + $table.outerHeight(true);

  return (subpage_bottom < table_bottom);
}

// 複数行に渡っている箇所は改行文字を付加する
function ApplyLineBreaks(text) {
  var MAX_BYTE = 50;
  var new_lines = [];
  var lines = $(text).text().split('\n');
  for(var i=0; i<lines.length; i++) {
    if (countLength(lines[i]) > MAX_BYTE) {
      var new_line = "";
      var bytes = 0;
      for (var j=0; j<lines[i].length; j++) {
        bytes += countByte(lines[i].charCodeAt(j));
        if (bytes > MAX_BYTE) {
          new_lines.push(new_line);
          new_line = "";
          bytes = countByte(lines[i].charCodeAt(j));
        }
        new_line += lines[i][j];
      }

      if (!(new_line === "")) {
        new_lines.push(new_line);
      }
    } else {
      new_lines.push(lines[i]);
    }
  }
  $(text).html(new_lines.join('<br>'))
}

function countByte(c) {
  // Shift_JIS: 0x0 ～ 0x80, 0xa0 , 0xa1 ～ 0xdf , 0xfd ～ 0xff
  // Unicode : 0x0 ～ 0x80, 0xf8f0, 0xff61 ～ 0xff9f, 0xf8f1 ～ 0xf8f3
  if ( (c >= 0x0 && c < 0x81) || (c == 0xf8f0) || (c >= 0xff61 && c < 0xffa0) || (c >= 0xf8f1 && c < 0xf8f4)) {
    return 1;
  } else {
    return 2;
  }
}

function countLength(str) {
  var r = 0;
  for (var i = 0; i < str.length; i++) {
    var c = str.charCodeAt(i);
    r += countByte(c);
  }
  return r;
}

// 案件の内容を，改ページしている場所で分割する
function splitTextInItem($item, page_no) {
  // 何ページ目か
  var page_index = page_no - 1;
  // ページ内で何番目のテーブル要素か
  // 案件群のテーブル以外に，議事録詳細テーブル，案件/承認/作成者テーブルが存在する
  var table_index = page_no + 2 - 1;
  $subpage = $('.subpage:eq('+page_index+')');
  $table = $('.table:eq('+table_index+')');
  if ($subpage.get(0) === undefined) {
    return;
  }

  // ページの枠からテーブルがどのくらいはみ出しているか
  var subpage_bottom = $subpage.position().top + $subpage.outerHeight(true);
  var table_bottom = $table.position().top + $table.outerHeight(true);
  var extra_height = table_bottom - subpage_bottom;

  // 案件全体の行数，はみ出した分の行数を各々取得
  var line_height = parseInt($item.css('line-height'));
  var item_height = $item.outerHeight(true);
  var item_line_numbers = parseInt(item_height/line_height);
  var extra_line_numbers = parseInt(extra_height/line_height);
  var extra_line_index = item_line_numbers - extra_line_numbers + 1;

  var text_lines = $item.children(".text").html().split('<br>');
  var username_lines = $item.children(".responsibility").html().split('<br>');
  var $breaked_item = $item.clone();

  // 改ページされる案件の位置が2行以下なら，案件ごと次ページに回す
  if (extra_line_index <= 3) {
    $item.remove();
    return $breaked_item;
  }

  if (Object.keys(text_lines).length >= extra_line_index) {
    var text = text_lines.slice(0, extra_line_index-1);
    $item.children(".text").html(text.join('<br>'));
    var hided_text = text_lines.slice(extra_line_index-1, text_lines.length);
    $breaked_item.children(".text").html(hided_text.join('<br>'));
  } else {
    $breaked_item.children(".text").html("");
  }

  if (Object.keys(username_lines).length >= extra_line_index) {
    var names = username_lines.slice(0, extra_line_index-1);
    $item.children(".responsibility").html(names.join('<br>'));
    var hided_names = username_lines.slice(extra_line_index-1, username_lines.length);
    $breaked_item.children(".responsibility").html(hided_names.join('<br>'));
  } else {
    $breaked_item.children(".responsibility").html("");
  }

  return $breaked_item;
}
