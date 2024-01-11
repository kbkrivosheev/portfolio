<?php
declare(strict_types=1);
namespace app\models\Competition\Forms;

use yii\base\Model;
use yii\helpers\Json;

/**
 * @property string $name
 * @property float $criteria
 * @property string|null $additionalInformation
 * @property string $id
 * @property float|null $value
 * @property $unit
 */

class Expenses extends Model

{
    public string $name;
    public float $criteria;
    public ?string $additionalInformation=null;
    public string $id;
    public ?float $value=null;
    public  $unit;


    public function rules(): array
    {
        return [
            [['name', 'additionalInformation', 'id'], 'string'],
            [['criteria', 'value'], 'number'],
            [['unit'], 'validateUnit'],
        ];
    }

    public function validateUnit($attribute)
    {
        $validationModel = new Unit();
        if ($validationModel->load($this->{$attribute}, '') && $validationModel->validate()) {
            $this->{$attribute} =  $validationModel;
        } else {
            $this->addError($attribute, Json::encode($validationModel->getErrors()));
        }
    }

}