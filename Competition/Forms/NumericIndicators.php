<?php

namespace app\models\Competition\Forms;

use yii\base\Model;
use yii\helpers\Json;

class NumericIndicators extends Model
{
    public string $id;
    public ?string $parentId = null;
    public string $name;
    public float $criteria;
    public ?float $value = null;
    public $unit;
    public $minValue;
    public $maxValue;
    public ?bool $isMaximumValueBest = null;
    public ?string $additionalInformation = null;
    public $documents;
    private ?Okved $okved2 = null;
    public ?string $contractSubject = null;

    public function rules(): array
    {
        return [
            [['id', 'name', 'additionalInformation', 'parentId', 'contractSubject'], 'string'],
            [['criteria', 'value'], 'number'],
            [['isMaximumValueBest'], 'boolean'],
            [['unit'], 'validateUnit'],
            [['documents'], 'validateDocuments'],
            [['minValue', 'maxValue', 'okved2'], 'safe']
        ];
    }

    public function validateUnit($attribute)
    {
        $validationModel = new Unit();
        if ($validationModel->load($this->{$attribute}, '') && $validationModel->validate()) {
            $this->{$attribute} = $validationModel;
        } else {
            $this->addError($attribute, Json::encode($validationModel->getErrors()));
        }
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

    public function setOkved2($okved): void
    {
        if (is_null($okved)) {
            return;
        }

        if (!is_array($okved)) {
            throw new \DomainException('Okved2 must be an array or null.');
        }

        $okvedForm = new Okved();
        if (!$okvedForm->load($okved, '')) {
            throw new \DomainException('Failed to load okved2.');
        }

        $this->okved2 = $okvedForm;
    }

    /**
     * @return Okved|null
     */
    public function getOkved2(): ?Okved
    {
        return $this->okved2;
    }

    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        if (!parent::validate($attributeNames, $clearErrors)) {
            return false;
        }

        if (!empty($this->okved2) && !$this->okved2->validate()) {
            $this->addErrors($this->okved2->getErrors());
            return false;
        }

        return true;
    }
}