<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Backend\Widgets\grid\ActionColumn;
use Besnovatyj\Backend\Widgets\pagination\LinkPager;
use Besnovatyj\Blocks\entities\Block;
use Besnovatyj\Blocks\forms\search\BlockSearch;
use Besnovatyj\Contracts\theme\ThemeArea;
use Besnovatyj\Kernel\security\AccessHelper;
use yii\bootstrap5\Html;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\web\View;

/* @var $this View */
/* @var $searchModel BlockSearch */
/* @var $dataProvider ActiveDataProvider */
/* @var $areaRows list<array{area:ThemeArea, declared:bool, total:int, active:int}> */
/* @var $areaOptions array<string,string> */
/* @var $hasCatalog bool */

$this->title = 'Блоки темы';
$this->params['breadcrumbs'][] = $this->title;
?>

<p>
    <?= Html::a('Создать блок', ['create'], ['class' => 'btn btn-success']) ?>
</p>

<div class="container-fluid">
    <div class="card mb-3">
        <div class="card-header">Места активной темы</div>
        <div class="card-body table-responsive">
            <?php if (!$hasCatalog): ?>
                <p class="text-body-secondary mb-0">
                    Пакет тем не установлен, список мест взять неоткуда: место указывается в блоке вручную,
                    тем идентификатором, который тема передаёт виджету.
                </p>
            <?php elseif ($areaRows === []): ?>
                <p class="text-body-secondary mb-0">
                    Активная тема не объявляет мест для блоков (файл <code>areas.php</code> в корне темы).
                </p>
            <?php else: ?>
                <table class="table detail-view mb-0">
                    <thead>
                    <tr>
                        <th>Место</th>
                        <th>Где и что</th>
                        <th>Блоков (включено)</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($areaRows as $row): ?>
                        <?php $area = $row['area']; ?>
                        <tr>
                            <td>
                                <?= Html::encode($area->label) ?><br>
                                <code><?= Html::encode($area->id) ?></code>
                            </td>
                            <td>
                                <?php if (!$row['declared']): ?>
                                    <span class="badge text-bg-warning">Тема не объявляет</span>
                                    Блоки этого места на сайт не выводятся.
                                <?php else: ?>
                                    <?= Html::encode($area->hint) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['total'] === 0): ?>
                                    <span class="text-body-secondary">пусто</span>
                                <?php else: ?>
                                    <?= Html::a(
                                        $row['total'] . ' (' . $row['active'] . ')',
                                        ['index', Html::getInputName($searchModel, 'area') => $area->id],
                                    ) ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($row['declared']): ?>
                                    <?= Html::a('Добавить блок', ['create', 'area' => $area->id], ['class' => 'btn btn-sm btn-outline-success']) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Блоки</div>
        <div class="card-body table-responsive">
            <?= GridView::widget([
                'options' => ['class' => 'table detail-view'],
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'layout' => "{summary}\n{items}",
                'columns' => [
                    [
                        'attribute' => 'area',
                        'value' => static function (Block $model) use ($areaOptions) {
                            return $areaOptions[$model->area] ?? $model->area;
                        },
                        'filter' => $areaOptions,
                    ],
                    [
                        'attribute' => 'title',
                        'value' => static function (Block $model) {
                            return Html::a(Html::encode($model->title), ['update', 'id' => $model->id]);
                        },
                        'format' => 'raw',
                    ],
                    [
                        'label' => 'Показ',
                        'value' => static function (Block $model) {
                            if ($model->show_from === null && $model->show_to === null) {
                                return 'всегда';
                            }
                            $from = $model->show_from !== null ? 'с ' . substr($model->show_from, 0, 16) : '';
                            $to = $model->show_to !== null ? 'до ' . substr($model->show_to, 0, 16) : '';
                            return trim($from . ' ' . $to);
                        },
                    ],
                    'sort_order',
                    [
                        'attribute' => 'status',
                        'value' => static function (Block $model) {
                            $next = $model->isActive() ? Block::STATUS_DRAFT : Block::STATUS_ACTIVE;
                            return Html::a(
                                Block::statusList()[(int)$model->status] ?? (string)$model->status,
                                ['status', 'id' => $model->id, 'status' => $next],
                                [
                                    'class' => 'badge text-decoration-none ' . ($model->isActive() ? 'text-bg-success' : 'text-bg-secondary'),
                                    'title' => $model->isActive() ? 'Выключить' : 'Включить',
                                    'data-method' => 'post',
                                ],
                            );
                        },
                        'format' => 'raw',
                        'filter' => Block::statusList(),
                    ],
                    [
                        'class' => ActionColumn::class,
                        'template' => AccessHelper::filterActionColumn(['update', 'delete']),
                    ],
                ],
            ]) ?>
        </div>
        <div class="card-footer clearfix">
            <nav aria-label="" class="nav-pagination">
                <?= LinkPager::widget([
                    'pagination' => $dataProvider->getPagination(),
                ]) ?>
            </nav>
        </div>
    </div>
</div>
