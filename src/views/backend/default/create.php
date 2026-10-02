<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Blocks\forms\backend\BlockForm;
use yii\web\View;

/* @var $this View */
/* @var $model BlockForm */
/* @var $areaOptions array<string,string> */
/* @var $hasCatalog bool */

$this->title = 'Новый блок';
$this->params['breadcrumbs'][] = ['label' => 'Блоки темы', 'url' => ['backend/default/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="container-fluid">
    <?= $this->render('_form', ['model' => $model, 'areaOptions' => $areaOptions, 'hasCatalog' => $hasCatalog]) ?>
</div>
