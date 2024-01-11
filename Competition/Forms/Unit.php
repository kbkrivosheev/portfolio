<?php

namespace app\models\Competition\Forms;

use yii\base\Model;

class Unit extends Model
{
    public int $id;
    public string $name;


    public function rules(): array
    {
        return [
            [['id'], 'integer'],
            [['name'], 'string'],
        ];
    }

}
