<?php
$this->Html->css('minute.css', ['block' => true]);
$this->Html->script(['elementFromAbsolutePoint.js'],  ['block' => true]);
?>
<script>
    $(function () {
        $("#sortable").sortable({
            update: function(event, ui) {
                var order = [];
                var i=0;
                $(".table-content.no").each(function (item, index) {
                    if (i==0) { i++; return; }
                    order.push($(this).text().replace(/\s+/g, ""));
                    $(this).text(i);
                    i++;
                });
                var json = JSON.stringify(order);
                console.log(json);

                sendPost(
                    "/webminutes/minutes/ajaxUpdateItemOrder/<?= $minute->id ?>",
                    {
                        order: json,
                        minute_id: <?= $minute->id ?>
                    },
                    null)
                    .done(function(result) {
                        console.log(result);
                        if (result == "success") {
                            alert("案件の順序を更新しました");
                        } else {
                            alert("案件の順序の更新に失敗しました．ページをリロードして再度お試しください");
                        }
                    });
            }
        });
        $("#sortable").disableSelection();
    });
</script>

<div class="contents">

    <div class="main-contents">

        <center>
            <h4>案件一覧</h4>
            <p>
                議事録は，複数の「案件」からなります．<br>
                また，案件には「タスク」と「議事項目」という大項目があります．<br>
                「タスク」となる案件は，期限内に目標を完遂できたかどうかを課長が確認します<br>
                確認時点で課長が案件を「フォロー」します．<br>
            </p>
        </center>
        <center>
            <?=
                $this->Html->link('印刷用ページ', [
                    'action'=>'createHtml',
                    $minute->id
                ])
            ?>
        </center>

        <div class="page-in-minute-view">
            <!-- 案件一覧 -->
            <div class="table">

                <div class="table-row header">
                    <div class="table-content no">No</div>
                    <div class="table-content category">項目</div>
                    <div class="table-content text">内容</div>
                    <div class="table-content primary">優先度</div>
                    <div class="table-content responsibility">担当</div>
                    <div class="table-content deadline">期限</div>
                    <div class="table-content follow">フォロー</div>
                    <div class="table-content actions"></div>
                </div>

                <?php if (!empty($items)): ?>
                    <div id="sortable">
                        <?php foreach ($items as $item): ?>
                            <div class="table-row ui-state-default">
                                <div class="table-content no">
                                    <?= h($item->order_in_minute) ?>
                                </div>
                                <div class="table-content category">
                                    <?php
                                        if ($item->item_meta_category_name == "タスク") {
                                            echo "【タスク】<br>";
                                        }
                                    ?>
                                    <?= h($item->item_category_name) ?>
                                </div>
                                <div class="table-content text">
                                    <?= nl2br($item->contents) ?>
                                </div>
                                <div class="table-content primary">
                                    <?= h($item->primary_char) ?>
                                </div>
                                <div class="table-content responsibility">
                                    <?php
                                        if (empty($item->user_names)) {
                                            echo "-";
                                        } else {
                                            echo "<ul>";
                                            foreach($item->user_names as $user_name) {
                                                echo "<li>".$user_name."</li>";
                                            }
                                            echo "</ul>";
                                        }
                                    ?>
                                </div>
                                <div class="table-content deadline">
                                    <?php
                                        if ($item->overed_at != NULL) {
                                            echo $item->overed_at->format('Y/m/d');
                                        } else {
                                            echo "-";
                                        }
                                    ?>
                                </div>
                                <div class="table-content follow">
                                    <?php
                                        if ($item->is_followed) {
                                            echo $item->followed_at->format('Y/m/d');
                                            echo "<br>";
                                            echo $item->followed_user_name;
                                        } else {
                                            echo "-";
                                        }
                                    ?>
                                </div>
                                <div class="table-content actions">
                                    <?php
                                        if ($item->overed_at != NULL && $item->is_followed == false) {
                                            echo $this->Form->postLink(__('フォロー'), [
                                                'controller' => 'Items',
                                                'action' => 'follow',
                                                $item->id
                                            ],
                                                                       [
                                                                           'confirm' => '案件の終了を確認し，フォローを行います．よろしいですか？この操作は取り消せません'
                                                                       ]);
                                            echo "<br>";
                                        }
                                    ?>
                                    <?= $this->Html->link(__('編集'), ['controller' => 'Items', 'action' => 'edit', $item->id]) ?>
                                    <?= "<br>" ?>
                                    <?= $this->Form->postLink(__('削除'), ['controller' => 'Items', 'action' => 'delete', $item->id], ['confirm' => __('Are you sure you want to delete # {0}?', $item->id)]) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <center>
                <?=
                    $this->Html->link('新規作成', [
                        'controller'=>'Items',
                        'action'=>'add',
                        $minute->id
                    ])
                ?>
            </center>
        </div>
    </div>

    <div class="side-contents right">
        <h4>議事録の詳細</h4>
        <table class="table minute minute-detail-table">
            <tr>
                <th>議事録名</th>
                <td><b><?= h($minute->name) ?></b></td>
            </tr>
            <tr>
                <th>日時</th>
                <td><?= h($minute->holded_at->format('Y/m/d H:i')) ?></td>
            </tr>
            <tr>
                <th>場所</th>
                <td><?= h($minute->holded_place) ?></td>
            </tr>
            <tr>
                <th>作成日</th>
                <td><?= h($minute->created_at->format('Y/m/d')) ?></td>
            </tr>
            <tr>
                <th>更新日</th>
                <td><?= h($minute->updated_at->format('Y/m/d')) ?></td>
            </tr>
        </table>

        <!-- 出席情報 -->
        <div class="user-table minute participation-table">
            <div class="user-table-row">
                <div class="user-table-row-elem th">出席状況( ◯ : 参加, △ : 遅刻, ✕ : 不参加 )</div>
            </div>
            <?= $this->element('userTable', [
                "users"=>$user_array,
                "add_participation"=>true,
                "col_num"=>2,
                "classes"=>"project-member",
                "participation_classes"=>"participation",
                ]) ?>
        </div>

        <center>
            <?=
                $this->Html->link('編集', [
                    'action'=>'edit',
                    $minute->id
                ])
            ?>
        </center>
    </div>

</div>
