<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Blocks\forms\search;

use Besnovatyj\Blocks\entities\Block;
use Besnovatyj\Forms\BaseForm;
use yii\data\ActiveDataProvider;

/**
 * Поиск/фильтрация блоков для грида админки.
 */
class BlockSearch extends BaseForm
{
    public ?string $area = null;
    public ?string $title = null;
    public ?int $status = null;

    public function rules(): array
    {
        return [
            ['status', 'integer'],
            [['area', 'title'], 'safe'],
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Block::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['area' => SORT_ASC, 'sort_order' => SORT_ASC, 'id' => SORT_ASC],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'area' => $this->area,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'title', $this->title]);

        return $dataProvider;
    }
}
