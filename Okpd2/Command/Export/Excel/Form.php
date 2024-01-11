<?php
declare(strict_types=1);

namespace app\models\Okpd2\Command\Export\Excel;

use yii\base\Model;

final class Form extends Model
{
    /**
     * @var Okpd2Form[]
     */
    private array $okpd2List = [];

    public function rules(): array
    {
        return [
            [['okpd2List'], 'required'],
        ];
    }

    public function setOkpd2List($okpd2List): void
    {
        if (!is_array($okpd2List)) {
            throw new \DomainException('Okpd2List must be an array.');
        }

        foreach ($okpd2List as $okpd2) {
            $okpd2Form = new Okpd2Form();
            if (!$okpd2Form->load($okpd2, '')) {
                throw new \DomainException('Failed to load okpd2List.');
            }
            $this->okpd2List[] = $okpd2Form;
        }
    }

    /**
     * @return Okpd2Form[]
     */
    public function getOkpd2List(): array
    {
        return $this->okpd2List;
    }

    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        if (!parent::validate($attributeNames, $clearErrors)) {
            return false;
        }

        foreach ($this->okpd2List as $okpd2) {
            if (!empty($okpd2->getErrors()) || !$okpd2->validate()) {
                $this->addErrors($okpd2->getErrors());
                return false;
            }
        }
        return true;
    }
}
