<?php
declare(strict_types=1);

namespace app\models\Okpd2\Command\Export\Excel;

use yii\base\Model;

final class Okpd2Form extends Model
{
    public const DEFAULT_LOT_INDEX = 0;
    public const DEFAULT_SUB_LOT_INDEX = 0;

    public string $id;
    public ?string $name = null;
    public ?string $okpd2 = null;
    public ?string $ktru = null;
    public array $nationalRegimeTags = [];
    public array $preferenceTags = [];
    public array $otherTags = [];
    public array $contractLinks = [];
    public int $lotIndex = self::DEFAULT_LOT_INDEX;
    public int $subLotIndex = self::DEFAULT_SUB_LOT_INDEX;

    public function rules(): array
    {
        return [
            [['id'], 'required'],
            [['okpd2', 'ktru', 'name'], 'string'],
            [['lotIndex', 'subLotIndex'], 'integer'],
            [['nationalRegimeTags', 'preferenceTags', 'otherTags', 'contractLinks'], 'each', 'rule' => ['string']],
        ];
    }
}
