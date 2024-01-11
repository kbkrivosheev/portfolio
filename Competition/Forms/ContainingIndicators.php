<?php

namespace app\models\Competition\Forms;

use yii\base\Model;
use yii\helpers\Json;

class ContainingIndicators extends Model
{
    public string $id;
    public ?string $parentId = null;
    public string $name;
    public ?bool $isContaining = null;
    public ?bool $value = null;
    public float $criteria;
    public ?string $additionalInformation = null;
    public $documents;

    public function rules(): array
    {
        return [
            [['id', 'name', 'parentId', 'additionalInformation'], 'string'],
            [['isContaining', 'value'], 'boolean'],
            [['documents'], 'validateDocuments'],
            [['criteria'], 'number'],
            [['value'], 'default', 'value' => false],
        ];
    }

    public function validateDocuments($attribute)
    {
        $validationModel = new Documents();
        if ($validationModel->load($this->{$attribute}, '') && $validationModel->validate()) {
            $this->{$attribute} = $validationModel;
        } else {
            $this->addError($attribute, Json::encode($validationModel->getErrors()));
        }
    }
}