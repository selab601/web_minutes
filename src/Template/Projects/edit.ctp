<?= $this->assign('title', 'プロジェクト編集') ?>
<?php
    // ページ固有の CSS, JS の読み込み
    $this->Html->css([
        'lib/jquery-ui.min.css',
        'lib/jquery-ui.theme.min.css',
        'lib/jquery-ui.structure.min.css',
    ], ['block' => true]);
    $this->html->script([
        'toggleRoleList.js'
    ], ['block' => true]);
?>
<script>
    $(document).ready(function () {
        toggleRoleList(jQuery, '<?= $roles ?>', '<?= json_encode($members) ?>');
        $("#datepicker1").datepicker({dateFormat: 'yy/mm/dd'});
        $("#datepicker2").datepicker({dateFormat: 'yy/mm/dd'});
    });
</script>

<?= $this->element('formContainerTemplate') ?>
<?= $this->Form->create($project, ['class'=>'form-container add-project']); ?>
<fieldset>
    <legend>プロジェクトを編集する</legend>
    <p>
        <b>注意</b> : 現在バグにより，登録済みの参加者を取り除こうとすると，エラーが生じる可能性があります<br>
        取り除きたい場合は，兎澤 (15nm722x@vc.ibaraki.ac.jp) までご一報ください．
    </p>
    <div class="form-container-fields add-project">
        <?= $this->Form->input('name', ['label' => 'プロジェクト名 : ']) ?>
        <?= $this->Form->input('budget', ['label' => '予算 : ']) ?>
        <?= $this->Form->input('customer_name', ['label' => '顧客名 : ']) ?>
        <?= $this->element('spanForm', [
            'form' => $this->form,
            'started_at' => $started_at,
            'finished_at' => $finished_at,
            ]) ?>
        <?= $this->element('checkboxForm', [
            'name' => 'users._ids',
            'label' => '参加者 : ',
            'classes' => 'add-project',
            'form' => $this->Form,
            'options' => $users_array,
            'default' => $checked_users_array,
            ]) ?>
    </div>
</fieldset>
<div class="form-container-footer">
    <?= $this->Form->button("決定") ?>
</div>
<?= $this->Form->end() ?>
