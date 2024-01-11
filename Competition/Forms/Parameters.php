<?php

declare(strict_types=1);

namespace app\models\Competition\Forms;

use yii\base\Model;
use yii\helpers\Json;

class Parameters extends Model
{
    public string $name;
    public string $inn;
    public float $nmck;
    public string $purchaseSubject;
    public ?int $purchaseSubjectId;
    public ?string $feature=null;
    public bool $isLifeCycleCriteria;
    public  $criteria;

    public function rules(): array
    {
        return [
            [['name', 'inn', 'purchaseSubject', 'feature'], 'string'],
            [['isLifeCycleCriteria'], 'boolean'],
            [['criteria'], 'validateCriteria'],
            [['nmck', 'purchaseSubjectId'], 'number']
        ];
    }

    public function validateCriteria($attribute)
    {
        $validationModel = new Criteria();
        if ($validationModel->load($this->{$attribute}, '') && $validationModel->validate()) {
            $this->{$attribute} =  $validationModel;
        } else {
            $this->addError($attribute, Json::encode($validationModel->getErrors()));
        }
    }

}