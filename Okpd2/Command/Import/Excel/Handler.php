<?php

declare(strict_types=1);

namespace app\models\Okpd2\Command\Import\Excel;

use app\components\ExcelReader;
use app\models\NsiKtru;
use app\models\Okpd2\Command\Import\Excel\Dto\Excel;
use app\models\Okpd2\Command\Import\Excel\Dto\ExcelCollection;
use app\models\Okpd2\Command\Import\Excel\Dto\Result;
use app\models\Okpd2\DTO\Ktru;
use app\models\Okpd2\DTO\Okpd2;
use app\models\Okpd2\DTO\Okpd2Builder;
use app\models\Okpd2\DTO\Unit;
use app\models\Okpd2\Objects\Entities\Okpd2Catalog;
use app\ReadModels\Ktru\Find\Query as KtruQuery;
use app\ReadModels\Okpd2\Find\Query as Okpd2Query;
use DomainException;

final class Handler
{
    private KtruQuery $ktruQuery;
    private Okpd2Builder $okpd2Builder;

    public function __construct(
        KtruQuery    $ktruQuery,
        Okpd2Builder $okpd2Builder
    )
    {
        $this->ktruQuery = $ktruQuery;
        $this->okpd2Builder = $okpd2Builder;
    }

    /**
     * @return Result[]
     * @throws DomainException
     */
    public function handle(Command $form): array
    {
        $excelReader = new ExcelReader($form->file->tempName, 'A', null, 2, 1000, [
            'reader' => ExcelReader::getExcelReader($form->file->getExtension())
        ]);
        $excelCollection = $this->getExcelCollection($excelReader);

        return $this->getResults($excelCollection);
    }

    /**
     * @throws DomainException
     */
    private function getExcelCollection(ExcelReader $excelReader): ExcelCollection
    {
        $excelCollection = new ExcelCollection();

        foreach ($excelReader->getData() as $row) {
            if (empty(array_diff($row, ['']))) {
                continue;
            }

            $excel = new Excel();
            if (!$excel->load($row, '') || !$excel->validate()) {
                throw new DomainException('Failed to load excel');
            }

            $excelCollection->addExcel($excel);
            if ($excel->okpd2) {
                $excelCollection->addOkpd2Codes($excel->okpd2);
            }
            if ($excel->ktru) {
                $excelCollection->addKtruCodes($excel->ktru);
            }
        }

        return $excelCollection;
    }

    private function getResults(ExcelCollection $excelCollection): array
    {
        $ktruModels = !empty($excelCollection->getKtruCodes())
            ? $this->ktruQuery->findByCodes($excelCollection->getKtruCodes())
            : [];

        $data = [];
        foreach ($excelCollection->getExcelData() as $excel) {
            $data[] = new Result(
                $excel->name,
                $this->okpd2Builder->buildFromCode($excel->okpd2, $excel->ktru),
                $this->getKtruDto($ktruModels[$excel->ktru] ?? null)
            );
        }

        return $data;
    }

    private function getKtruDto(?NsiKtru $model): ?Ktru
    {
        if (!$model) {
            return null;
        }
        return new Ktru(
            $model->id,
            $model->name,
            $model->code,
            $model->units
                ? new Unit(
                $model->units->id,
                $model->units->full_name . " (" . $model->units->local_symbol . ")"
            )
                : null
            ,
        );
    }
}
