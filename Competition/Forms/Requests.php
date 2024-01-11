<?php
declare(strict_types=1);
namespace app\models\Competition\Forms;

use yii\base\Model;
use yii\helpers\Json;

class Requests extends Model
{
    public string $id;
    public string $name;
    public bool $isCriteriaReduced;
    public ?float $price = null;
    public $expenses;
    public $characteristics;
    public $qualifications;

    public function rules(): array
    {
        return [
            [['id', 'name'], 'string'],
            [['isCriteriaReduced'], 'boolean'],
            [['price'], 'number'],
            [['expenses'], 'validateExpenses'],
            [['characteristics'], 'validateCharacteristics'],
            [['qualifications'], 'validateQualifications'],
        ];
    }


    public function validateExpenses($attribute)
    {
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


}