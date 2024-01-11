<?php

namespace app\models\Competition\Forms;

use yii\base\Model;

class Okved extends Model
{
    public int $id;
    public string $name;
    public string $code;

    public function rules(): array
    {
        return [
            [['id'], 'integer'],
            [['name'], 'string'],
            [['code'], 'string'],
        ];
    }
}
