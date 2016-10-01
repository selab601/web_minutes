<!doctype html>
<html>
    <head>
        <?= $this->html->css('bootstrap.min.css') ?>
        <?= $this->html->css('main.css') ?>
        <?= $this->html->css('jquery.datetimepicker.css') ?>
        <?= $this->html->script(['jquery.js', 'jquery.datetimepicker.full.js', 'bootstrap.min.js']) ?>
    </head>
    <script>
        $(function () {
            $('#datetimepicker').datetimepicker();
        });
    </script>
    <body>
        <?= $this->element('header') ?>

        <?php
            $this->Form->templates([
                'inputContainer' => '<div class="form-container-field">{{content}}</div>',
                'input' => '<input class="form-container-field-input" type="{{type}}" name="{{name}}" {{attrs}} />',
            ]);
            $users_array = [];
            foreach ($projects_users as $projects_user) {
                $user = $projects_user->toArray()["user"];
                $users_array[$projects_user->id] =
                    $user["last_name"] . " " . $user["first_name"];
            }
        ?>

        <div class="form-container-wrapper">
            <?php
                echo $this->Form->create('Minute', [
                    'class'=>'form-container add-minute',
                ]);
            ?>

            <fieldset>
                <legend>議事録の追加</legend>
                <div class="form-container-fields add-minute">
                    <?php
                        echo $this->Form->input('name', ['label'=>'議事録名 : ']);
                        echo $this->Form->input('holded_place', ['label'=>'開催場所 : ']);

                        $now = new \DateTime();
                        echo $this->Form->input('holded_at', [
                            'type' => 'datetime',
                            'default' => $now->format('Y/m/d H:i'),
                            'label' => '開催時刻 : ',
                            'type'=>'text',
                            'id'=>'datetimepicker',
                        ]);

                    ?>
                    <div class="checkbox-form form-container-field add-minute">
                        <label>参加者 : </label>
                        <div class="checkbox-form-input-wrapper">
                            <div class="checkbox-form-input">
                                <?php
                                    echo $this->Form->input('projects_users._ids', [
                                        'options' => $users_array,
                                        'checked' => true,
                                        'default' => [$auth_projects_user->id],
                                        'multiple' => 'checkbox',
                                        'label' => false,
                                        'templates' => [
                                            'inputContainer' => '{{content}}',
                                        ],
                                ]);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>

            <div class="form-container-footer">
                <?= $this->Form->button("追加") ?>
            </div>
            <?= $this->Form->end() ?>
        </div>
    </body>
</html>
