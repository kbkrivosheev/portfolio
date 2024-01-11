<?php

namespace app\models\Competition\Forms;

use yii\base\Model;

class NotNumericIndicators extends Model
{
    public string $id;
    public ?string $parentId=null;
    public string $name;
    public ?string $meaning=null;
    public float $criteria;
    public ?float $value=null;
    public ?string  $propertiesList=null;



    public function rules(): array
    {
        return [
            [[ 'id', 'name', 'parentId', 'meaning', 'propertiesList'], 'string'],
            [['criteria', 'value'], 'number'],
        ];

    }

}