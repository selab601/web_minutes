<?php
if (!isset($show_navigation_bar)) { $show_navigation_bar = true; }
if (!isset($class)) { $class = ""; }
?>

<header class=<?= '"' . $class . '"' ?>>
    <div class="flex-navbar">
        <div class="flex-navbar-icon">
            <?=
                $this->Html->link(
                    $this->Html->image('icon.png'),
                    [
                        'controller' => 'pages',
                        'action' => 'display',
                        'home',
                        'escape' => false
                    ],
                    [
                        'escape' => false
                    ]
                );
            ?>
        </div>
        <div class="flex-navbar-navigation">
            <?php
                if ($show_navigation_bar) {
                    echo $this->element('navigation');
                }
            ?>
        </div>
        <ul class="flex-navbar-contents">
            <?php if (($this->request->session()->read('Auth.User')) == false): ?>
                <!-- 未ログインユーザ用メニュー -->

                <li>
                    <?=
                        $this->Html->link(
                            'ログイン', [
                                'controller' => 'Users',
                                'action' => 'login',
                            ]
                        );
                    ?>
                </li>
                <li>
                    <?=
                        $this->Html->link(
                            '新規登録', [
                                'controller' => 'Users',
                                'action' => 'signup',
                            ]
                        );
                    ?>
                </li>
            <?php else: ?>
                <!-- ログインユーザ用メニュー -->

                <!-- 管理者ユーザ用メニュー -->
                <?php if ($this->request->session()->read('Auth.User.is_authorized') == 1): ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">管理<span class="caret"></span></a>
                        <ul class="dropdown-menu" role="menu">
                            <li><?=  $this->Html->link('ユーザ', ['controller'=>'Users', 'action'=>'index']) ?></li>
                            <li><?=  $this->Html->link('プロジェクト', ['controller'=>'Projects', 'action'=>'index']) ?></li>
                            <li><?=  $this->Html->link('担当種別', ['controller'=>'Roles', 'action'=>'index']) ?></li>
                            <li><?=  $this->Html->link('案件大項目', ['controller'=>'ItemMetaCategories', 'action'=>'index']) ?></li>
                            <li><?=  $this->Html->link('案件種別', ['controller'=>'ItemCategories', 'action'=>'index']) ?></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- 一般ユーザ用メニュー -->
                <li class="dropdown" id="flex-navbar-contents-account">
                    <?php
                        $full_name =
                            $this->request->session()->read('Auth.User.last_name') .
                            " " .
                            $this->request->session()->read('Auth.User.first_name') .
                            " さん";
                        echo $this->Html->link(
                            $this->Html->image($class=="home"?'icon_account_white.png':'icon_account_black.png') .
                            $full_name .
                            "  <span class=\"caret\"></span>",
                            "#",
                            [
                                "class" => "dropdown-toggle",
                                "data-toggle" => "dropdown",
                                "role" => "button",
                                "aria-expanded" => "false",
                                "escape" => false
                            ]
                        );
                    ?>
                    <ul class="dropdown-menu" role="menu">
                        <li><?=  $this->Html->link('プロフィール', ['controller'=>'Users', 'action'=>'view', $this->request->session()->read('Auth.User.id')]) ?></li>
                        <li><?=  $this->Html->link('参加プロジェクト', ['controller'=>'Users', 'action'=>'projectsView', $this->request->session()->read('Auth.User.id')]) ?></li>
                        <li><?=  $this->Html->link('ログアウト', ['controller'=>'Users', 'action'=>'logout' ]) ?></li>
                    </ul>
                </li>

            <?php endif; ?>
        </ul>
    </div>
</header>

<?= $this->element('flashModal') ?>
