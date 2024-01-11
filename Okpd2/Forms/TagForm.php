<?php

declare(strict_types=1);

namespace app\models\Okpd2\Forms;

use app\models\Okpd2ToActs;
use yii\base\Model;

final class TagForm extends Model
{
    public int $id;
    public string $name;
    public ?string $description = null;
    public ?string $lawLink = null;
    public ?string $conditionLink = null;
    public bool $isActive;
    public ?string $icon;
    public int $color = Okpd2ToActs::GREEN_COLOR_TYPE;

    public function rules(): array
    {
        return [
            [['id', 'name'], 'required'],
            [['name', 'description', 'lawLink', 'conditionLink', 'icon'], 'string'],
            [['id', 'color'], 'integer'],
            [['isActive'], 'safe']
        ];
    }

    public static function create(
        int $id,
        string $name,
        ?string $description,
        ?string $lawLink,
        ?string $conditionLink,
        bool $isActive,
        ?string $icon,
        int $color
    ): self
    {
        $form = new self();
        $form->id = $id;
        $form->name = $name;
        $form->description = $description;
        $form->lawLink = $lawLink;
        $form->conditionLink = $conditionLink;
        $form->isActive = $isActive;
        $form->icon = $icon;
        $form->color = $color;

        return $form;
    }
}
