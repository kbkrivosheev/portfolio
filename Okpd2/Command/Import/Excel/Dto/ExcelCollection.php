<?php

declare(strict_types=1);

namespace app\models\Okpd2\Command\Import\Excel\Dto;

final class ExcelCollection
{
    /**
     * @var Excel[]
     */
    private array $excelData = [];
    /**
     * @var string[]
     */
    private array $okpd2Codes = [];
    /**
     * @var string[]
     */
    private array $ktruCodes = [];

    public function addExcel(Excel $excel): void
    {
        $this->excelData[] = $excel;
    }

    public function addOkpd2Codes(string $code): void
    {
        $this->okpd2Codes[] = $code;
    }

    public function addKtruCodes(string $code): void
    {
        $this->ktruCodes[] = $code;
    }

    /**
     * @return Excel[]
     */
    public function getExcelData(): array
    {
        return $this->excelData;
    }

    public function getOkpd2Codes(): array
    {
        return $this->okpd2Codes;
    }

    public function getKtruCodes(): array
    {
        return $this->ktruCodes;
    }
}
