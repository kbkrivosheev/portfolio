<?php

declare(strict_types=1);

namespace app\Features\PurposePurchase\Export\Excel\Forms;


use DomainException;
use yii\base\Model;

class Base extends Model
{
    /**
     * @var string|null $type Тип объекта закупки (сейчас только 'T' - "Tовар")
     * @var int|null $link Ссылка на позицию-контейнер (сейчас не вноситься)
     * @var int|null $parent Идентификатор «родительского» объекта закупки (пока не используеться)
     * @var string|null $volume Объем работы, услуги (для типа "Работа" или "Услуга".)
     * @var int|null $method Способ указания объема выполнения работы, оказания услуги
     * (только для типа "Работа" или "Услуга".)
     * @var string|null $name Наименование товара, работы, услуги
     * @var Okpd2|null $okpd2 ОКПД2
     * @var Ktru|null $ktru КТРУ
     * @var Unit|null $unit Единица измерения
     * @var float|null $quantity кол-во (для типа "Tовар")
     * @var float|null $price Цена за единицу
     * @var string|null $cost Стоимость позиции
     * @var Characteristic[] Характеристики
     * @var string|null $trademark Товарный знак (марка)
     * @var string|null $mark Знаки обслуживания, фирменные наименования,патенты, полезные модели,
     * промышленные образцы (модель)
     * @var int|null $equivalent Допускается поставка эквивалента (1- допускается, 0 - не допускается)
     * @var string|null $userKtruJustification Обоснования включения дополнительной информации в сведения
     * о товаре, работе, услуге при добавлении пользовательских КТРУ
     */

    public ?string $name = null;
    public ?float $quantity = null;
    public ?float $price = null;
    public ?float $cost = null;
    public ?string $trademark = null;
    public ?string $mark = null;
    public ?int $equivalent = null;
    public ?string $userKtruJustification = null;

    private ?Unit $unit = null;
    private ?Okpd2 $okpd2 = null;
    private ?Ktru $ktru = null;
    private array $characteristics = [];


    /** не используем или заполняем сами */
    public ?string $type = null;
    public ?int $link = null;
    public ?int $method = null;
    public ?string $volume = null;
    public ?int $parent = null;


    public const PRODUCT_TYPE = 'T';

    /** пока не используем */
    public const JOB_TYPE = 'R';
    public const SERVICE_TYPE = 'U';


    public function rules(): array
    {
        return [
            [['type', 'userKtruJustification'], 'string'],
            [['equivalent', 'method'], 'integer'],
            [['quantity', 'price', 'cost'], 'number'],
            [['name'], 'string', 'max' => 2000],
            [['trademark'], 'string', 'max' => 500],
            [['mark'], 'string', 'max' => 500],
            [['volume'], 'string', 'max' => 500],
            [['link'], 'integer', 'max' => 12],
            [['parent'], 'integer', 'max' => 20],
            [['type'], 'default', 'value' => self::PRODUCT_TYPE],
            [['equivalent'], 'default', 'value' => 0],
            [['method'], 'default', 'value' => 1],
            [['volume'], 'default', 'value' => ''],
            [['okpd2', 'ktru', 'unit', 'characteristics'], 'safe'],
        ];
    }


    public function setCharacteristics($characteristics): void
    {
        if (!is_array($characteristics)) {
            throw new \DomainException('Characteristics must be an array.');
        }
        foreach ($characteristics as $characteristic) {
            $characteristicForm = new Characteristic();
            if (!$characteristicForm->load($characteristic, '')) {
                throw new DomainException('Failed to load сharacteristics.');
            }
            $this->characteristics[] = $characteristicForm;
        }
    }

    /**
     * @return Characteristic[]
     */
    public function getCharacteristics(): array
    {
        return $this->characteristics;
    }

    /**
     * @param $attributeNames
     * @param $clearErrors
     * @return bool
     */
    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        if (!parent::validate($attributeNames, $clearErrors)) {
            return false;
        }

        foreach ($this->characteristics as $characteristic) {
            if (!empty($characteristic->getErrors()) || !$characteristic->validate()) {
                $this->addErrors($characteristic->getErrors());
                return false;
            }
        }
        return true;
    }

    /**
     * @param $okpd2
     * @return void
     */
    public function setOkpd2($okpd2): void
    {
        if (!$okpd2) {
            return;
        }
        $okpd2Form = new Okpd2();
        if (!$okpd2Form->load($okpd2, '') || !$okpd2Form->validate()) {
            throw new DomainException('Failed to load Okpd2');
        }
        $this->okpd2 = $okpd2Form;

    }

    /**
     * @return null|Okpd2
     */
    public function getOkpd2(): ?Okpd2
    {
        return $this->okpd2;
    }

    /**
     * @param $ktru
     * @return void
     */
    public function setKtru($ktru): void
    {
        if (!$ktru) {
            return;
        }
        $ktruForm = new Ktru();
        if (!$ktruForm->load($ktru, '') || !$ktruForm->validate()) {
            throw new DomainException('Failed to load Ktru');
        }
        $this->ktru = $ktruForm;

    }

    /**
     * @return null|Ktru
     */
    public function getKtru(): ?Ktru
    {
        return $this->ktru;
    }

    /**
     * @param $unit
     * @return void
     */
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
     * @return null|Unit
     */
    public function getUnit(): ?Unit
    {
        return $this->unit;
    }
}

