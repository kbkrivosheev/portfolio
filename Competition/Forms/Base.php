<?php

declare(strict_types=1);

namespace app\models\Competition\Forms;

use yii\base\Model;
use yii\helpers\Json;

class Base extends Model
{
    public $parameters;
    public $expenses;
    public $characteristics;
    public $qualifications;
    public $requests;

    public function rules(): array
    {
        return [
            [['parameters'], 'validateParameters'],
            [['expenses'], 'validateExpenses'],
            [['characteristics'], 'validateCharacteristics'],
            [['qualifications'], 'validateQualifications'],
            [['requests'], 'validateRequests']
        ];
    }

    public function validateParameters($attribute)
    {
        $validationModel = new Parameters();
        if ($validationModel->load($this->{$attribute}, '') && $validationModel->validate()) {
            $this->{$attribute} = $validationModel;
        } else {
            $this->addError($attribute, Json::encode($validationModel->getErrors()));
        }
    }

    public function validateExpenses($attribute)
    {
        $validationModels = [];
        $count = count($this->{$attribute});
        for ($i = 0; $i < $count; $i++) {
            $validationModels[] = new Expenses();
        }
        if (Model::loadMultiple($validationModels, $this->{$attribute}, '') &&
            Model::validateMultiple($validationModels)) {
            $this->{$attribute} = array_replace($this->{$attribute}, $validationModels);
        } else {
            $this->addError($attribute, Json::encode($validationModels[0]->getErrors()));
        }
    }

    public function validateCharacteristics($attribute)
    {
        $validationModels = [];
        $count = count($this->{$attribute});
        for ($i = 0; $i < $count; $i++) {
            $validationModels[] = new Characteristics();
        }
        if (Model::loadMultiple($validationModels, $this->{$attribute}, '') &&
            Model::validateMultiple($validationModels)) {
            $this->{$attribute} = array_replace($this->{$attribute}, $validationModels);
        } else {
            $this->addError($attribute, Json::encode($validationModels[0]->getErrors()));
        }
    }

    public function validateQualifications($attribute)
    {
        $validationModels = [];
        $count = count($this->{$attribute});
        for ($i = 0; $i < $count; $i++) {
            $validationModels[] = new Qualifications();
        }
        if (Model::loadMultiple($validationModels, $this->{$attribute}, '') &&
            Model::validateMultiple($validationModels)) {
            $this->{$attribute} = array_replace($this->{$attribute}, $validationModels);
        } else {
            $this->addError($attribute, Json::encode($validationModels[0]->getErrors()));
        }
    }

    public function validateRequests($attribute)
    {
        $validationModels = [];
        $count = count($this->{$attribute});
        for ($i = 0; $i < $count; $i++) {
            $validationModels[] = new Requests();
        }
        if (Model::loadMultiple($validationModels, $this->{$attribute}, '') &&
            Model::validateMultiple($validationModels)) {
            $this->{$attribute} = array_replace($this->{$attribute}, $validationModels);
        } else {
            $this->addError($attribute, Json::encode($validationModels[0]->getErrors()));
        }
    }
}


