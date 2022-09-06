<?php Block::put('breadcrumb') ?>
    <ul>
        <li><a href="<?= $this->getActiveMainMenuItem()->url ?>"><?= e($this->getActiveMainMenuItem()->label) ?></a></li>
        <li><?= e(trans($this->pageTitle)) ?></li>
    </ul>
<?php Block::endPut() ?>

<?= $this->listRender() ?>
