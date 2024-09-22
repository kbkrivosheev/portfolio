<?php

declare(strict_types=1);

namespace app\Features\PurposePurchase\Export\Excel\Forms;

use DomainException;
use yii\base\Model;

class Purchases extends Model
{
    private array $purchases = [];

    public function rules(): array
    {
        return [
            ['purchases', 'required'],
        ];
    }

    /**
     * @throws DomainException
     */
    public function setPurchases($purchases): void
    {
        if (!is_array($purchases)) {
            throw new DomainException('Purchases must be an array.');
        }

        foreach ($purchases as $item) {
            $form = new Base();
            if (!$form->load($item, '')) {
                throw new DomainException('Failed to load purchases');
            }
            $this->purchases[] = $form;
        }
    }

    /**
     * @return Base[]
     */
    public function getPurchases(): array
    {
        return $this->purchases;
    }

    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        if (!parent::validate($attributeNames, $clearErrors)) {
            return false;
        }
        foreach ($this->purchases as $item) {
            if (!empty($item->getErrors()) || !$item->validate()) {
                $this->addErrors($item->getErrors());
                return false;
            }
        }
        return true;
    }

}