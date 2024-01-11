<?php

namespace app\models\Okpd2\Command\SplitByLot;

use app\models\Okpd2\Forms\TagForm;
use yii\base\Model;

final class Form extends Model
{
    public string $id;

    /**
     * @var TagForm[]
     */
    private array $nationalRegimeTags = [];

    /**
     * @var TagForm[]
     */
    private array $preferenceTags = [];

    public function rules(): array
    {
        return [
            [['id'], 'required'],
            [['nationalRegimeTags', 'preferenceTags'], 'safe']
        ];
    }

    public function setNationalRegimeTags($tags): void
    {
        if (!is_array($tags)) {
            throw new \DomainException('National regime tags must be an array.');
        }

        foreach ($tags as $tag) {
            $form = new TagForm();
            if (!$form->load($tag, '')) {
                throw new \DomainException('Failed to load national regime tags.');
            }
            $this->nationalRegimeTags[] = $form;
        }
    }

    public function setPreferenceTags($tags): void
    {
        if (!is_array($tags)) {
            throw new \DomainException('Preference tags must be an array.');
        }

        foreach ($tags as $tag) {
            $form = new TagForm();
            if (!$form->load($tag, '')) {
                throw new \DomainException('Failed to load preference tags.');
            }
            $this->preferenceTags[] = $form;
        }
    }

    public function getNationalRegimeTags(): array
    {
        return $this->nationalRegimeTags;
    }

    public function getPreferenceTags(): array
    {
        return $this->preferenceTags;
    }
}
