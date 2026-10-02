<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\controllers\backend;

use Besnovatyj\Blocks\entities\Block;
use Besnovatyj\Blocks\forms\backend\BlockForm;
use Besnovatyj\Blocks\forms\search\BlockSearch;
use Besnovatyj\Blocks\repositories\BlockRepository;
use Besnovatyj\Blocks\services\AreaDirectory;
use Besnovatyj\Blocks\services\manage\BlockManageService;
use Besnovatyj\Kernel\controller\ControllerTrait;
use Throwable;
use Yii;
use yii\db\Exception;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

/**
 * CRUD-админка блоков: места активной темы и их начинка.
 */
class DefaultController extends Controller
{
    use ControllerTrait;

    public function __construct(
        $id,
        $module,
        private readonly BlockManageService $service,
        private readonly BlockRepository $repo,
        private readonly AreaDirectory $areas,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'status' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $searchModel = new BlockSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'areaRows' => $this->areas->rows(),
            'areaOptions' => $this->areas->options(),
            'hasCatalog' => $this->areas->hasCatalog(),
        ]);
    }

    /**
     * @param string|null $area место, в которое заводится блок (из списка мест на главной админки)
     */
    public function actionCreate(?string $area = null): Response|string
    {
        $form = new BlockForm(null, $this->areaRange());
        $form->area = (string)$area;

        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $block = $this->service->create($form);
                return $this->redirect(['update', 'id' => $block->id]);
            } catch (Exception $e) {
                $this->handleDomainException($e, 'Ошибка');
            }
        }
        return $this->render('create', [
            'model' => $form,
            'areaOptions' => $this->areas->options($form->area),
            'hasCatalog' => $this->areas->hasCatalog(),
        ]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $block = $this->repo->get($id);

        // Блок уже лежит в месте, которое тема перестала объявлять, — сохранить его там можно,
        // иначе правка текста заставила бы сначала переносить блок.
        $range = $this->areaRange();
        if ($range !== null && !in_array($block->area, $range, true)) {
            $range[] = $block->area;
        }

        $form = new BlockForm($block, $range);
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $this->service->edit($block->id, $form);
                return $this->redirect(['update', 'id' => $block->id]);
            } catch (Exception $e) {
                $this->handleDomainException($e, 'Ошибка');
            }
        }
        return $this->render('update', [
            'model' => $form,
            'block' => $block,
            'areaOptions' => $this->areas->options($block->area),
            'hasCatalog' => $this->areas->hasCatalog(),
            'declared' => $this->areas->isDeclared($block->area),
        ]);
    }

    /**
     * Включить/выключить блок из грида.
     */
    public function actionStatus(int $id, int $status): Response
    {
        try {
            $this->service->setStatus($id, $status === Block::STATUS_ACTIVE ? Block::STATUS_ACTIVE : Block::STATUS_DRAFT);
        } catch (Throwable $e) {
            $this->handleDomainException($e, 'Ошибка');
        }
        return $this->goReferer();
    }

    public function actionDelete(int $id): Response
    {
        try {
            $this->service->remove($id);
        } catch (Throwable $e) {
            $this->handleDomainException($e, 'Ошибка');
        }
        return $this->redirect(['index']);
    }

    /**
     * Допустимые места для формы: объявленные темой; null — пакета тем нет, места свободные.
     *
     * @return list<string>|null
     */
    private function areaRange(): ?array
    {
        if (!$this->areas->hasCatalog()) {
            return null;
        }

        $range = [];
        foreach ($this->areas->rows() as $row) {
            if ($row['declared']) {
                $range[] = $row['area']->id;
            }
        }

        return $range;
    }
}
