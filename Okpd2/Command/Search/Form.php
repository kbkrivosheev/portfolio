<?php

namespace app\models\Okpd2\Command\Search;

use yii\base\Model;

final class Form extends Model
{
    public string $query;
    public ?int $limit = null;
    public int $isSearchByContracts;

    public function rules(): array
    {
        return [
            [['query'], 'required'],
            [['query'], 'string'],
            [['limit', 'isSearchByContracts'], 'integer'],
            [['limit'], 'default', 'value' => 100]
        ];
    }
}