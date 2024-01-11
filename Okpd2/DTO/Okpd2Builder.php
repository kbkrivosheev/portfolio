<?php

declare(strict_types=1);

namespace app\models\Okpd2\DTO;

use app\models\NsiKtru;
use app\models\Okpd2\Forms\TagForm;
use app\models\Okpd2\Objects\Entities\Okpd2Catalog;
use app\models\Okpd2Contracts;
use app\models\Okpd2ToActs;
use app\ReadModels\Okpd2\Find\Query as Okpd2Query;
use app\Repository\NsiKtruRepository;
use yii\helpers\ArrayHelper;

final class Okpd2Builder
{
    /**
     * Массив с полными кодами ОКПД2 и НКМИ для  878 НПА
     */
    private const NKMI_CODES = [
        '26.60.11.113' => [271710, 271720, 271740, 271780, 271790, 271800, 271830, 271850, 282950],
        '26.60.12.110' => [271710, 271720, 271740, 271780, 271790, 271800, 271830, 271850, 282950],
        '26.60.12.129' => [
            271710, 271720, 271740, 271780, 271790, 271800, 271830, 271850, 282950, 279450, 172070, 172450, 172460
        ],
        '32.50.21.112' => [271710, 271720, 271740, 271780, 271790, 271800, 271830, 271850, 282950],
        '26.60.11.120' => [191060, 280530, 125970, 142570, 158270, 310450],
        '26.60.11.129' => [125970, 142570, 158270, 310450],
        '26.60.13.190' => [125970, 142570, 158270, 310450, 119850,
            126460, 126470, 126500, 130380, 210150, 233940, 262390, 262430, 262440, 334660, 334670, 334680, 172870,
            204120, 335380, 209840, 335370, 326010, 212340],
        '32.50.50.190' => [125970, 142570, 158270, 310450, 129360, 187160, 127550, 131980, 132020, 132060, 132070,
            152690, 152700, 160030, 270540, 292620, 122990, 143910, 145090, 215850, 261620, 321680, 127550],
        '26.60.12.119' => [279450, 172070, 172450, 172460],
        '26.70.22.150' => [279450, 172070],
        '27.40.39.110' => [279450, 172070, 129360, 187160],
        '32.50.13.120' => [279450, 172070, 119890, 126550, 127830, 172260, 228980, 228990, 229000, 260140, 260500,
            268390, 282800, 282950, 172450, 172460, 271710, 271720, 271740, 271780, 271790, 271800, 271830, 271850,
            282950],
        '26.60.12.120' => [145190, 149980, 150000, 150010, 150020, 170280, 218360,
            218410, 232490, 249320, 288690, 317710, 345960, 152710, 232490, 260980, 291870, 291820, 291830, 292080],
        '26.60.13.140' => [127180, 204130, 216570, 236610, 172870, 204120, 335380, 209840, 335370, 326010, 212340],
        '32.50.13.190' => [119850, 126460, 126470, 126500, 130380, 210150, 233940, 262390, 262430, 262440, 334660,
            334670, 334680, 119890, 126550, 127830, 172260, 228980, 228990, 229000, 260140,
            260500, 268390, 282800, 282950, 119890, 126550, 127830, 172260, 228980, 228990, 229000, 260140, 260500,
            268390, 282800, 282950, 172450, 172460,
            271710, 271720, 271740, 271780, 271790, 271800, 271830, 271850, 282950],
        '28.25.13.110' => [122990, 143910, 145090, 215850, 261620, 321680],
    ];
    /**
     * Массив с короткими кодами ОКПД2 и НКМИ для  878 НПА
     */
    private const NKMI_SHORT_CODES = [
        '32.50.1' => [271710, 271720, 271740, 271780, 271790, 271800, 271830, 271850, 282950, 119890, 126550, 127830,
            172260, 228980, 228990, 229000, 260140, 260500, 268390, 282800, 282950],
        '32.50.13' => [172450, 172460, 271710, 271720, 271740, 271780, 271790, 271800, 271830, 271850, 282950, 119890,
            126550, 127830, 172260, 228980, 228990, 229000, 260140, 260500, 268390, 282800, 282950],
        '27.40.39' => [129360, 187160],
        '32.50.50' => [122990, 143910, 145090, 215850, 261620, 321680, 127550],
        '26.60.13' => [172870, 204120, 335380, 209840, 335370, 326010, 212340],
        '32.99.59' => [259390, 259380, 157680, 335650],

    ];
    private const NPA_878_TAG = '878';
    private Okpd2Query $okpd2Query;
    private NsiKtruRepository $nsiKtruRepository;

    public function __construct(
        Okpd2Query        $okpd2Query,
        NsiKtruRepository $nsiKtruRepository)
    {
        $this->okpd2Query = $okpd2Query;
        $this->nsiKtruRepository = $nsiKtruRepository;
    }

    /**
     * @param string|null $okpd2
     * @param string|null $ktru
     * @return Okpd2|null
     * Тут при создании DTO проверяем:
     *  - если пользователь вводит ОКПД2 для которых установлены исключения ключи массива $okpd2Nkmi,
     *  то тег 878 отображается оранжевым. Пример: ОКПД - 26.60.12.110;
     *  - если пользователь вводит ОКПД2+КТРУ и в КТРУ есть НКМИ (classifiers_code) значения массива
     *  $okpd2Nkmi, то 878 отображается зеленым. Пример: ОКПД - 26.60.13.190 КТРУ - 26.60.13.190-00000018;
     *  - если пользователь вводит ОКПД2+КТРУ и в КТРУ нет НКМИ (classifiers_code) значения массива
     *   $okpd2Nkmi, то 878 не отображается. Пример: ОКПД - 26.60.11.113 КТРУ - 26.60.11.113-00000002;
     */
    public function buildFromCode(?string $okpd2, ?string $ktru = null): ?Okpd2
    {
        /**
         * @var Okpd2Catalog $model
         * @var NsiKtru $ktruModel
         */
        if (!$okpd2 || !($model = $this->okpd2Query->findByCode($okpd2))) {
            return null;
        }
        $okpd2Nkmi = $this->getAllOkpd2Nkmi();
        if ($ktru) {
            $ktruModel = $this->nsiKtruRepository->findByCode($ktru);
        }
        $dto = new Okpd2(
            $model->id,
            $model->name,
            $model->code,
            ArrayHelper::getColumn($model->contracts, static function (Okpd2Contracts $contract) {
                return $contract->getLink();
            }),
        );
        $dto->addOkpd2Ktru($model->code, $ktruModel->code ?? null);
        foreach ($model->okpd2Acts as $okpd2Act) {
            $act = $okpd2Act->act;
            $tag = TagForm::create(
                $act->id,
                $act->tag,
                $act->full_name,
                $act->link,
                $act->conditions,
                (bool)$act->status,
                $act->icon,
                $okpd2Act->color
            );
            if ($tag->name === self::NPA_878_TAG && array_key_exists($model->code, $okpd2Nkmi)) {
                $tag->color = Okpd2ToActs::ORANGE_COLOR_TYPE;
                if ($ktruModel && $ktruModel->classifiers_code) {
                    if (!in_array($ktruModel->classifiers_code, array_merge(...array_values($okpd2Nkmi)))) {
                        continue;
                    }
                    $tag->color = Okpd2ToActs::GREEN_COLOR_TYPE;
                }
            }
            if ($act->isNationalRegimeType()) {
                $dto->addNationalRegimeTag($tag);
            } elseif ($act->isPreferencesType()) {
                $dto->addPreferenceTag($tag);
            } elseif ($act->isOtherType()) {
                $dto->addOtherTag($tag);
            }
        }

        return $dto;
    }

    /**
     * @param string $okpd2Code
     * @param array $nkmiArray
     * @return array
     */
    private function getFullOkpd2Nkmi(string $okpd2Code, array $nkmiArray): array
    {
        $result = [];
        if (!($okpd2List = $this->okpd2Query->findFullCode($okpd2Code))) {
            return $result;
        }
        foreach ($okpd2List as $code) {
            if (array_key_exists($code, self::NKMI_CODES)) {
                continue;
            }
            $result[$code] = $nkmiArray;
        }
        return $result;
    }

    /**
     * @return array
     */
    private function getAllOkpd2Nkmi(): array
    {
        $result = [];
        foreach (self::NKMI_SHORT_CODES as $okpd2Code => $nkmiArray) {
            $result[] = $this->getFullOkpd2Nkmi($okpd2Code, $nkmiArray);
        }
        return array_merge(array_merge(...$result), self::NKMI_CODES);
    }
}
