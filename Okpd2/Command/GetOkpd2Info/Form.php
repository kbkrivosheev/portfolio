<?php

namespace app\models\Okpd2\Command\GetOkpd2Info;

use DomainException;
use yii\base\Model;

final class Form extends Model
{
    /**
     * @var Code[]
     */
    private ?array $codes=[];

    public function rules(): array
    {
        return [
            [['codes'], 'required'],
            [['codes'], 'safe'],

        ];
    }

    public function setCodes($codes): void
    {
        if (!is_array($codes)) {
            throw new DomainException('Codes must be an array');
        }

        foreach ($codes as $item) {
            $form = new Code();
            if (!$form->load($item, '')) {
                throw new DomainException("Failed to load codes");
            }
            $this->codes[] = $form;
        }
    }

    /**
     * @return Code[]
     */
    public function getCodes(): array
    {
        return $this->codes;
    }

    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        if (!parent::validate($attributeNames, $clearErrors)) {
            return false;
        }

        foreach ($this->codes as $item) {
            if (!empty($item->getErrors()) || !$item->validate()) {
                $this->addErrors($item->getErrors());
            }
        }

        return !$this->hasErrors();
    }
}