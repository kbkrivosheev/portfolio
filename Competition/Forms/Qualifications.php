<?php
declare(strict_types=1);
namespace app\models\Competition\Forms;

use yii\base\Model;
use yii\helpers\Json;

class Qualifications extends Model
{
    public string $id;
    public ?string $name=null;
    public ?float $criteria=null;
    public  $numericIndicators;
    public  $notNumericIndicators;
    public  $containingIndicators;

    public function rules(): array
    {
        return [
            [['id', 'name'], 'string'],
            [['criteria'], 'number'],
            [['numericIndicators'], 'validateNumericIndicators'],
            [['notNumericIndicators'], 'validateNotNumericIndicators'],
            [['containingIndicators'], 'validateContainingIndicators'],
        ];
    }
    public function validateNumericIndicators($attribute)
    {
        $count = count($this->{$attribute});
        for ($i = 0; $i < $count; $i++) {
            $validationModels[] = new NumericIndicators();
        }
        if (Model::loadMultiple($validationModels, $this->{$attribute}, '') &&
            Model::validateMultiple($validationModels)) {
            $this->{$attribute} = array_replace($this->{$attribute}, $validationModels);
        } else {
            $this->addError($attribute, Json::encode($validationModels[0]->getErrors()));
        }

    }
    public function validateNotNumericIndicators($attribute)
    {
        $count = count($this->{$attribute});
        for ($i = 0; $i < $count; $i++) {
            $validationModels[] = new NotNumericIndicators();
        }
        if (Model::loadMultiple($validationModels, $this->{$attribute}, '') &&
            Model::validateMultiple($validationModels)) {
            $this->{$attribute} = array_replace($this->{$attribute}, $validationModels);
        } else {
            $this->addError($attribute, Json::encode($validationModels[0]->getErrors()));
        }
    }
    public function validateContainingIndicators($attribute)
    {
        $count = count($this->{$attribute});
        for ($i = 0; $i < $count; $i++) {
            $validationModels[] = new ContainingIndicators();
        }
        if (Model::loadMultiple($validationModels, $this->{$attribute}, '') &&
            Model::validateMultiple($validationModels)) {
            $this->{$attribute} = array_replace($this->{$attribute}, $validationModels);
        } else {
            $this->addError($attribute, Json::encode($validationModels[0]->getErrors()));
        }
    }

}