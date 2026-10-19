<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\controllers\backend;

use Besnovatyj\Blocks\entities\Block;
use Besnovatyj\Blocks\files\BlockFiles;
use Besnovatyj\Blocks\forms\backend\BlockDataForm;
use Besnovatyj\Blocks\forms\backend\BlockForm;
use Besnovatyj\Blocks\forms\search\BlockSearch;
use Besnovatyj\Blocks\Module;
use Besnovatyj\Blocks\repositories\BlockRepository;
use Besnovatyj\Blocks\schema\DataNormalizer;
use Besnovatyj\Blocks\schema\DataValidator;
use Besnovatyj\Blocks\schema\FieldKind;
use Besnovatyj\Blocks\schema\Schema;
use Besnovatyj\Blocks\services\AreaDirectory;
use Besnovatyj\Blocks\services\LinkTargets;
use Besnovatyj\Blocks\services\manage\BlockManageService;
use Besnovatyj\Blocks\types\BlockTypeRegistry;
use Besnovatyj\Contracts\block\BlockType;
use Besnovatyj\Kernel\controller\ControllerTrait;
use Besnovatyj\Kernel\security\AccessHelper;
use Throwable;
use Yii;
use yii\db\Exception;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

/**
 * CRUD-админка блоков: места активной темы и их начинка.
 *
 * Создание в два шага: сначала место, тип и общие поля, после сохранения — данные по схеме типа
 * (у блока появляется папка файлов, а форма полей — проводник на эту папку).
 *
 * Код чужих виджетов (поле вида `code`) правит только тот, у кого есть
 * {@see Module::EMBED_PERMISSION}. Без него блок с кодом можно переносить, включать и переименовывать,
 * но его данные не принимаются из POST вовсе, а код показывается текстом.
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
        private readonly BlockTypeRegistry $types,
        private readonly BlockFiles $files,
        private readonly DataNormalizer $normalizer,
        private readonly DataValidator $validator,
        private readonly LinkTargets $linkTargets,
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
            'typeLabels' => array_map(static fn (BlockType $type) => $type->label, $this->types->all()),
        ]);
    }

    /**
     * @param string|null $area место, в которое заводится блок (из списка мест на главной админки)
     */
    public function actionCreate(?string $area = null): Response|string
    {
        $form = new BlockForm(null, $this->areaRange(), fn (string $area) => array_map('strval', array_keys($this->creatableTypes($area))));
        $form->scenario = BlockForm::SCENARIO_CREATE;
        $form->area = (string)$area;

        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $type = $this->types->get($form->type);
                $defaults = $type === null ? [] : $this->normalizer->normalize(Schema::fromArray($type->fields), [], null);
                $block = $this->service->create($form, $defaults);
                return $this->redirect(['update', 'id' => $block->id]);
            } catch (Exception $e) {
                $this->handleDomainException($e, 'Ошибка');
            }
        }
        return $this->render('create', [
            'model' => $form,
            'areaOptions' => $this->areas->options($form->area),
            'hasCatalog' => $this->areas->hasCatalog(),
            'types' => $this->creatableTypes($form->area),
        ]);
    }

    /**
     * Список типов для выбранного места на форме создания (HTMX: смена места перерисовывает список).
     */
    public function actionTypes(string $area = '', string $selected = ''): string
    {
        return $this->renderPartial('_types', [
            'types' => $this->creatableTypes($area),
            'selected' => $selected,
            'inputName' => (new BlockForm(null, null, static fn () => []))->formName() . '[type]',
        ]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $block = $this->repo->get($id);
        // Папки нет у блоков, созданных до появления папок: создаём при первом открытии формы.
        $this->files->ensure($block->id);

        // Блок уже лежит в месте, которое тема перестала объявлять, — сохранить его там можно,
        // иначе правка текста заставила бы сначала переносить блок.
        $range = $this->areaRange();
        if ($range !== null && !in_array($block->area, $range, true)) {
            $range[] = $block->area;
        }

        $form = new BlockForm($block, $range, fn (string $area) => $this->allowedTypeIds($area, $block));

        $type = $this->types->get($block->type);
        $schema = $type === null ? null : Schema::fromArray($type->fields);
        $canEditCode = $this->canEditCode();
        $dataEditable = $schema !== null && ($canEditCode || !$schema->contains(FieldKind::Code));
        $dataForm = $schema === null
            ? null
            : new BlockDataForm($schema, $block->id, $block->dataArray(), $this->normalizer, $this->validator);

        $post = Yii::$app->request->post();
        if ($form->load($post)) {
            $dataLoaded = $dataEditable && $dataForm->load($post);
            $valid = $form->validate();
            $valid = (!$dataLoaded || $dataForm->validate()) && $valid;
            if ($valid) {
                try {
                    $this->service->edit($block->id, $form, $dataLoaded ? $dataForm : null);
                    Yii::$app->session->setFlash('success', 'Блок сохранён.');
                    return $this->redirect(['update', 'id' => $block->id]);
                } catch (Exception $e) {
                    $this->handleDomainException($e, 'Ошибка');
                }
            }
        }

        return $this->render('update', [
            'model' => $form,
            'block' => $block,
            'type' => $type,
            'dataForm' => $dataForm,
            'dataEditable' => $dataEditable,
            'areaOptions' => $this->areas->options($block->area),
            'hasCatalog' => $this->areas->hasCatalog(),
            'declared' => $this->areas->isDeclared($block->area),
            'linkTargets' => $schema?->contains(FieldKind::Link) ? $this->linkTargets->options() : [],
            'files' => $this->files,
        ]);
    }

    /**
     * Новая пустая строка repeater для формы блока (HTMX).
     *
     * @param int    $id    блок
     * @param string $field имя поля-repeater в схеме типа
     * @throws BadRequestHttpException поле не repeater
     */
    public function actionRow(int $id, string $field): string
    {
        $block = $this->repo->get($id);
        $type = $this->types->get($block->type);

        foreach ($type === null ? [] : Schema::fromArray($type->fields)->fields as $candidate) {
            if ($candidate->name === $field && $candidate->kind === FieldKind::Repeater) {
                $row = $this->normalizer->normalize(new Schema($candidate->fields), [], $block->id);
                return $this->renderPartial('_row', [
                    'field' => $candidate,
                    'key' => 'n' . bin2hex(random_bytes(6)),
                    'row' => $row,
                    'path' => $candidate->name,
                    'blockId' => $block->id,
                    'dataForm' => null,
                    'linkTargets' => Schema::fromArray($type->fields)->contains(FieldKind::Link) ? $this->linkTargets->options() : [],
                    'files' => $this->files,
                ]);
            }
        }

        throw new BadRequestHttpException('Unknown repeater field.');
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

    /**
     * Типы, которые текущий пользователь может создать в месте: разрешённые местом, без типов с
     * кодом виджета, если нет {@see Module::EMBED_PERMISSION}.
     *
     * @return array<string, BlockType>
     */
    private function creatableTypes(string $area): array
    {
        $types = $this->types->allowedIn($this->areas->area($area));
        if ($this->canEditCode()) {
            return $types;
        }

        return array_filter(
            $types,
            static fn (BlockType $type) => !Schema::fromArray($type->fields)->contains(FieldKind::Code),
        );
    }

    /**
     * Id типов, которые место принимает (для переноса существующего блока). Тип, пропавший из
     * реестра, не мешает сохранить блок в его же месте.
     *
     * @return list<string>
     */
    private function allowedTypeIds(string $area, Block $block): array
    {
        // Ключи-числа PHP хранит как int, а сравнение в форме строгое.
        $ids = array_map('strval', array_keys($this->types->allowedIn($this->areas->area($area))));
        if ($area === $block->area && !in_array($block->type, $ids, true)) {
            $ids[] = $block->type;
        }

        return $ids;
    }

    /**
     * Может ли текущий пользователь создавать и править код чужих виджетов.
     */
    private function canEditCode(): bool
    {
        return AccessHelper::checkRoute(Module::EMBED_PERMISSION);
    }
}
