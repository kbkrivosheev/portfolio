<?php

declare(strict_types=1);

namespace app\Features\PurposePurchase\Export\Excel\Forms;

use DomainException;
use yii\base\Model;

class Characteristic extends Model
{
    /**
     * @var string|null $name Наименование характеристики
     *
     * @var int|null $instructionType Тип инструкции по заполнению характеристик в заявке.
     * Укажите "1"-"Участник закупки указывает в заявке диапазон значений характеристики",
     * "2"-"Участник закупки указывает в заявке конкретное значение характеристики",
     * "3"-"Участник закупки указывает в заявке только одно значение характеристики",
     * "4"-"Участник закупки указывает в заявке одно или несколько значений характеристики",
     * "5"-"Участник закупки указывает в заявке все значения характеристики",
     * "6"-"Значение характеристики не может изменяться участником закупки".
     *
     * @var int|null $format Формат значения характеристики
     * "1" - Тестовый формат значений характеристики TEXT_FORMAT,
     * "2" - Числовой формат значений характеристики NUMBER_FORMAT,
     * "3" - Диапазон формат значений характеристики RANGE_FORMAT,
     *
     * @var int|null $type Тип характеристики
     *  "1" - КТРУ характеристики - подгружаються с кодом (не более 10) -  KTRU_TYPE,
     *  "2" - КТРУ пользовательские характеристики - могут быть введены пользователем в дополнение к
     * КТРУ с кодом (не более 3) -  KTRU_USER_TYPE ,
     *  "3" - ОКПД2 - вводятся пользователем при отсутвии КТРУ (не более 15) -  OKPD2_TYPE,
     *
     * @var int|null $typeValue Тип значения характеристики
     *   "1" - Качественная,
     *   "2" - Количественная
     *
     * @var int|null $isRequired Обязателья - "1" или не обязательая - "0"
     *
     * @var int|null $kind - не изменяемая "1" или изменяемая "2" -выбор одного значения, "3" -выбор нескольких значений
     * @var string|null $values - одно или несколько значений характеристики в виде строки через ";"
     * @var Unit|null $unit единица измерения характеристики
     */
    public ?string $name = null;
    public ?int $instructionType = null;
    public ?int $format = null;
    public ?int $type = null;
    public ?int $typeValue = null;
    public ?int $isRequired = null;
    public ?int $kind = null;
    public ?string $values = null;
    private ?Unit $unit = null;


    public const KIND_IMMUTABLE = 1;


    public const TEXT_FORMAT = 1;
    public const NUMBER_FORMAT = 2;
    public const RANGE_FORMAT = 3;


    public const KTRU_TYPE = 1;
    public const KTRU_USER_TYPE = 2;
    public const OKPD2_TYPE = 3;


    public function rules(): array
    {
        return [
            [['values'], 'string'],
            [['name'], 'string', 'max' => 32767],
            [['instructionType', 'format', 'isRequired', 'kind', 'type', 'typeValue'], 'integer'],
            [['unit'], 'safe'],


        ];
    }

    public function setUnit($unit): void
    {
        if (!$unit) {
            return;
        }
        $unitForm = new Unit();
        if (!$unitForm->load($unit, '') || !$unitForm->validate()) {
            throw new DomainException('Failed to load unit');
        }
        $this->unit = $unitForm;
    }

    /**
     * @return Unit|null
     */
    public function getUnit(): ?Unit
    {
        return $this->unit;
    }


}