<?php

namespace app\models\Competition\Forms;

use yii\base\Model;


class Documents extends Model
{
    public  $confirming;
    public ?string $activities = null;
    public ?string $availability = null;
    public ?bool $isExecutedContractOnly=null;


    public function rules(): array
    {
        return [
            [['activities', 'availability'], 'string'], // TODO сделать массивами
            [['confirming'], 'safe'],
            [['isExecutedContractOnly'], 'boolean'],
        ];
    }
}