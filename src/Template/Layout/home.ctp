<!DOCTYPE html>
<html>
<head>
    <?= $this->Html->charset() ?>
    <title>
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->Html->meta('icon') ?>

    <?= $this->Html->css([
        'main.css',
        'lib/bootstrap.min.css'
        ]) ?>
    <?= $this->Html->script([
        'lib/jquery.js',
        'lib/jquery-ui.min.js',
        'lib/bootstrap.min.js',
        ]) ?>

    <link href="https://fonts.googleapis.com/css?family=Inconsolata" rel="stylesheet">
    <link href='https://fonts.googleapis.com/css?family=Londrina+Solid' rel='stylesheet' type='text/css'>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body>
    <?= $this->element('header', [
        'class' => 'home',
        'show_navigation_bar' => false,
        ]) ?>

    <?= $this->fetch('content') ?>

    <div class="footer home">
        <p class="text-muted">© Copyright 2016 Software Engineering Ueda's Laboratory</p>
    </div>
</body>
</html>
