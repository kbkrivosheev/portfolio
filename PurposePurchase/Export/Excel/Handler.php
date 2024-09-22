<?php

declare(strict_types=1);

namespace app\Features\PurposePurchase\Export\Excel;

use app\Features\PurposePurchase\Export\Excel\Forms\Base;
use app\Features\PurposePurchase\Export\Excel\Forms\Characteristic;
use app\Features\PurposePurchase\Export\Excel\Forms\Purchases;
use app\Features\PurposePurchase\Export\Excel\Handbooks\Styles;
use app\Features\PurposePurchase\Export\Excel\Handbooks\Titles;
use app\helpers\Str;
use DomainException;
use PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use PhpOffice\PhpSpreadsheet\Cell\CellRange;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Handler
{
    private array $lettersList;
    private const CHARACTERISTIC_TITLE = 'Характеристика';

    /**
     * @throws DomainException
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function handle(Purchases $purchases): string
    {

        $end = 'GM';
        $this->lettersList = $this->getLetters($end);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // Заголовок
        $sheet->mergeCells('B1:I2');
        $sheet->mergeCells('A1:A2');
        $linkText = 'Сгенерировано на портале ПРОГОСЗАКАЗ.РФ';
        $linkUrl = 'https://xn--80aahqcqybgko.xn--p1ai/';
        $sheet->setCellValue('B1', $linkText);
        $sheet->getCell('B1')->getHyperlink()->setUrl($linkUrl);
        //Название листа
        $sheet->setTitle('Сведения об объектах закупки');
        //Границы
        $sheet->getStyle($this->getCellRange('A1', "{$end}6"))->applyFromArray(Styles::BORDER_BOTTOM);
        //Заливка жёлтым столбцов А и F
        $sheet->getStyle('A:A')->applyFromArray(Styles::YELLOW_FILL);
        $sheet->getStyle('F:F')->applyFromArray(Styles::YELLOW_FILL);
        //Стили первой строки
        $sheet->getStyle($this->getCellRange('A1', "{$end}1"))->applyFromArray(Styles::STYLE_ROW_1);
        $sheet->getStyle($this->getCellRange('A2', "{$end}2"))->applyFromArray(Styles::STYLE_ROW_1);
        //Стили второй строки
        $sheet->getStyle("Q2:R2")->applyFromArray(Styles::STYLE_ROW_2_BROWN);
        $sheet->getStyle($this->getCellRange('S2', 'BP2'))->applyFromArray(Styles::STYLE_ROW_2_DEEP_BLUE);
        $sheet->getStyle($this->getCellRange('BQ2', 'GM2'))->applyFromArray(Styles::STYLE_ROW_2_BLUE);
        $sheet->getStyle("CL2")->applyFromArray(Styles::STYLE_ROW_2_GREEN);
        //Стили заголовка
        $sheet->getStyle('B1:I2')->applyFromArray(Styles::TITLE_STYLE);
        //Стили строк 3-6
        $sheet->getStyle($this->getCellRange('A3', "{$end}3"))->applyFromArray(Styles::STYLE_ROW_3);
        $sheet->getStyle($this->getCellRange('A4', "{$end}4"))->applyFromArray(Styles::STYLE_ROW_4);
        $sheet->getStyle($this->getCellRange('A5', "{$end}5"))->applyFromArray(Styles::STYLE_ROW_5);
        $sheet->getStyle($this->getCellRange('A6', "{$end}6"))->applyFromArray(Styles::STYLE_ROW_6);

        /**
         * Высота строк 1-6
         */
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->getRowDimension(2)->setRowHeight(40);
        $sheet->getRowDimension(3)->setRowHeight(70);
        $sheet->getRowDimension(4)->setRowHeight(40);
        $sheet->getRowDimension(5)->setRowHeight(300);
        $sheet->getRowDimension(6)->setRowHeight(90);


        /**
         * Ширина столбцов
         */
        foreach ($this->getColumns('A', $end) as $columnID) {
            $sheet->getColumnDimension($columnID)->setWidth(20);
        }


        /**
         * Заголовки основных параметров + 1 заголовок КТРУ
         */
        foreach (range('A', 'W') as $column) {
            $this->getTitleCells($column, $sheet);
        }

        /**
         * Заголовки обязательных характеристик КТРУ 2-10
         */
        $ktruTitles = $this->getColumnsTitles($this->getColumns('X', 'BP'), 5, 'getKtruTitlesArray');
        foreach ($ktruTitles as $column => $titles) {
            $this->setTitleCharacteristics($column, $titles, $sheet);
        }

        /**
         * Заголовки пользовательской характеристики 1
         */
        foreach ($this->getColumns('BQ', 'BW') as $column) {
            $this->getTitleCells($column, $sheet);
        }

        /**
         * Заголовки пользовательских характеристик 2,3
         */
        $userTitles = $this->getColumnsTitles($this->getColumns('BX', 'CK'), 7, 'getUserTitlesArray');
        foreach ($userTitles as $column => $titles) {
            $this->setTitleCharacteristics($column, $titles, $sheet);
        }

        /**
         * Обоснование пользовательских характеристик CL + заголовки  характеристики 1 ОКПД2
         */
        foreach ($this->getColumns('CL', 'CS') as $column) {
            $this->getTitleCells($column, $sheet);
        }

        /**
         * Заголовки  характеристик ОКПД2 2-15
         */
        $okpdTitles = $this->getColumnsTitles($this->getColumns('CT', $end), 7, 'getOkpdTitlesArray');
        foreach ($okpdTitles as $column => $titles) {
            $this->setTitleCharacteristics($column, $titles, $sheet);
        }

        /**
         * Данные
         */
        $row = 7;
        foreach ($purchases->getPurchases() as $form) {
            $sheet->getRowDimension($row)->setRowHeight(40);
            /**
             * Purchases
             */
            foreach ($this->getValuesArray($form) as $letter => $val) {
                /**
                 * Base
                 */
                $sheet->setCellValue($letter . $row, $val);

            }
            /**
             * Значения характеристик КТРУ изменяемые
             */
            $characteristicsValues = $this->getCharacteristicsValues($form->getCharacteristics());
            $ktruValues = $this->getColumnsValues(
                $this->getColumns('S', 'BP'),
                5,
                'getKtruValuesArray',
                $characteristicsValues['ktruChangeable'] ?? null
            );
            foreach ($ktruValues as $letter => $val) {
                $sheet->setCellValue($letter . $row, $val);
            }

            /**
             * Значения характеристик КТРУ пользовательские
             */


            $ktruUserValues = $this->getColumnsValues($this->getColumns('BQ', 'CK'),
                7,
                'getKtruUserValuesArray',
                $characteristicsValues['ktruUsers'] ?? null
            );
            foreach ($ktruUserValues as $letter => $val) {
                $sheet->setCellValue($letter . $row, $val);
            }
            if ($form->userKtruJustification) {
                $sheet->setCellValue('CL' . $row, $form->userKtruJustification);
            }

            /**
             * Значения характеристик ОКПД2
             * Функционал совпадает с генерацией строк пользовательских КТРУ
             */

            $okpd2UserValues = $this->getColumnsValues($this->getColumns('CM', 'GM'),
                7,
                'getKtruUserValuesArray',
                $characteristicsValues['okpd2'] ?? null
            );
            foreach ($okpd2UserValues as $letter => $val) {
                $sheet->setCellValue($letter . $row, $val);
            }

            ++$row;
        }
        --$row;
        $sheet->getStyle($this->getCellRange('A1', "{$end}{$row}"))->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($this->getCellRange('A7', "{$end}{$row}"))->applyFromArray(Styles::STYLE_ROW_7);
        //Заливка жёлтым столбцов А и F
        $sheet->getStyle($this->getCellRange('A7', "A{$row}"))->applyFromArray(Styles::YELLOW_FILL);
        $sheet->getStyle($this->getCellRange('F7', "F{$row}"))->applyFromArray(Styles::YELLOW_FILL);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save("php://output");
        if ($xlsData = ob_get_clean()) {
            return $xlsData;
        }

        throw new DomainException('Ошибка сохранения файла, обратитесь к администратору.');
    }

    /**
     * @param string $endColumn
     * @return array
     * Создаёт массив координат нужного диапазона включая двоыйные буквы типа AA, AB, AC и т.д.
     */
    private function getLetters(string $endColumn): array
    {
        $columnsExtended = $columns = $coordinates = range('A', 'Z');
        foreach ($columnsExtended as $firstLetter) {
            foreach ($columns as $secondLetter) {
                $column = $firstLetter . $secondLetter;
                $coordinates[] = $column;
                if ($column === $endColumn) {
                    return $coordinates;
                }
            }
        }
        return $coordinates;
    }


    /**
     * @param string $startCell
     * @param string $endCell
     * @return CellRange
     */
    private function getCellRange(string $startCell, string $endCell): CellRange
    {
        return new CellRange(new CellAddress($startCell), new CellAddress($endCell));
    }

    /**
     * @param string $startColumn
     * @param string $endColumn
     * @return array
     */
    private function getColumns(string $startColumn, string $endColumn): array
    {
        $coordinates = $this->lettersList;
        $start = array_search($startColumn, $coordinates, true);
        $end = array_search($endColumn, $coordinates, true);
        return array_slice($coordinates, $start, ($end - $start + 1));
    }

    /**
     * @param string $column
     * @param Worksheet $sheet
     * @return void
     */
    private function getTitleCells(string $column, Worksheet $sheet): void
    {
        $titlesModel = new Titles();
        if (!isset($titlesModel->$column)) {
            return;
        }
        foreach ($titlesModel->$column as $key => $val) {
            $sheet->setCellValue($column . $key, $val);
        }
    }

    /**
     * @param array $columns
     * @param int $variety кратность (количество столбцов до следующей характеристики)
     * @param string $functionName название функции обработки данных
     * @return array
     */

    private function getColumnsTitles(array $columns, int $variety, string $functionName): array
    {
        $result = [];
        $number = 1;
        $key = 0;
        if (!$columns || !$variety || !method_exists(self::class, $functionName)) {
            return $result;
        }
        foreach ($columns as $i => $item) {
            if ($i % $variety === 0) {
                $key = 0;
                $number++;
            }
            $result[$item] = $this->$functionName($key, $number);
            $key++;
        }
        return $result;
    }

    /**
     * @param array $columns
     * @param int $variety
     * @param string $functionName
     * @param array|null $characteristics
     * @return array
     */
    private function getColumnsValues(array $columns, int $variety, string $functionName, ?array $characteristics): array
    {
        $result = [];
        if (!$characteristics
            || !$columns
            || !$variety
            || !method_exists(self::class, $functionName)) {
            return $result;
        }
        $columnsSliced = array_chunk($columns, $variety);
        foreach ($columnsSliced as $key => $item) {
            foreach ($item as $number => $v) {
                $result[$v] = $this->$functionName($number, $key, $characteristics);
            }
        }
        return $result;
    }

    /**
     * @param int $key
     * @param int $number
     * @return array|string[]
     */
    private function getKtruTitlesArray(int $key, int $number): array
    {
        switch ($key) {
            case 0:
                return $this->getTitlesFirstColumn(self::CHARACTERISTIC_TITLE, $number);
            case 1:
                return Titles::CHARACTERISTIC_COLUMN_2;
            case 2:
                return Titles::CHARACTERISTIC_COLUMN_3;
            case 3:
                return Titles::CHARACTERISTIC_COLUMN_4;
            case 4:
                return Titles::CHARACTERISTIC_COLUMN_5;
            default:
                return [];
        }
    }

    /**
     * @param $key
     * @param $number
     * @return array|string[]
     */
    private function getUserTitlesArray($key, $number): array
    {
        switch ($key) {
            case 0:
                return $this->getTitlesFirstColumn('Пользовательская характеристика', $number);
            case 1:
                return Titles::USER_CHARACTERISTIC_COLUMN_2;
            case 2:
                return Titles::USER_CHARACTERISTIC_COLUMN_3;
            case 3:
                return Titles::USER_CHARACTERISTIC_COLUMN_4;
            case 4:
                return Titles::USER_CHARACTERISTIC_COLUMN_5;
            case 5:
                return Titles::USER_CHARACTERISTIC_COLUMN_6;
            case 6:
                return Titles::USER_CHARACTERISTIC_COLUMN_7;
            default:
                return [];
        }
    }

    /**
     * @param $number
     * @param $key
     * @return array|string[]
     */

    private function getOkpdTitlesArray($number, $key): array
    {
        switch ($number) {
            case 0:
                return $this->getTitlesFirstColumn(self::CHARACTERISTIC_TITLE, $key);
            case 1:
                return Titles::OKPD_CHARACTERISTIC_COLUMN_2;
            case 2:
                return Titles::OKPD_CHARACTERISTIC_COLUMN_3;
            case 3:
                return Titles::OKPD_CHARACTERISTIC_COLUMN_4;
            case 4:
                return Titles::OKPD_CHARACTERISTIC_COLUMN_5;
            case 5:
                return Titles::OKPD_CHARACTERISTIC_COLUMN_6;
            case 6:
                return Titles::OKPD_CHARACTERISTIC_COLUMN_7;
            default:
                return [];
        }
    }

    /**
     * @param string $name
     * @param int $number
     * @return string[]
     */
    private function getTitlesFirstColumn(string $name, int $number): array
    {
        return [
            2 => $name . ' ' . $number,
            3 => 'Наименование характеристики',
            4 => 'Строка (2000)',
            6 => 'Наименование поля в ПРИЗ: "Наименование характеристики"'
        ];
    }


    /**
     * @param string $column
     * @param array $titles
     * @param Worksheet $sheet
     * @return void
     */
    private function setTitleCharacteristics(string $column, array $titles, Worksheet $sheet): void
    {
        foreach ($titles as $row => $title) {
            $sheet->setCellValue(new CellAddress($column . $row), $title);
        }
    }

    /**
     * @param Base $form
     * @return array
     */
    private function getValuesArray(Base $form): array
    {
        $characteristicsValuesToString = $this->getCharacteristicsValuesToString($form->getCharacteristics());
        return [
            'A' => $form->type,
            'C' => $form->getOkpd2() && !$form->getKtru() ? $form->getOkpd2()->code : '',
            'D' => $form->getKtru() ? $form->getKtru()->code : '',
            'E' => $form->getKtru() && $form->getOkpd2() ? $form->getOkpd2()->code : '',
            'F' => $form->name,
            'G' => $form->trademark,
            'H' => $form->trademark ? $form->equivalent : '',
            'I' => $form->mark,
            'J' => $form->method,
            'K' => $form->quantity,
            'M' => $form->getUnit() ? $form->getUnit()->code : '',
            'N' => $form->price,
            'O' => $form->cost,
            'Q' => $characteristicsValuesToString['name'] ? implode(';', $characteristicsValuesToString['name']) : '',
            'R' => $characteristicsValuesToString['instructionType'] ? implode(';', $characteristicsValuesToString['instructionType']) : '',
        ];
    }

    /**
     * @param int $number
     * @param int $key
     * @param Characteristic[]|null $characteristics
     * @return string
     */

    private function getKtruValuesArray(int $number, int $key, array $characteristics): string
    {
        if (!$characteristics || !$characteristics[$key]) {
            return '';
        }
        switch ($number) {
            case 0:
                return $characteristics[$key]->name ?? '';
            case 1:
                return $this->getCharacteristicValueByFormat($characteristics[$key], Characteristic::TEXT_FORMAT);
            case 2:
                return $this->getCharacteristicValueByFormat($characteristics[$key], Characteristic::NUMBER_FORMAT);
            case 3:
                return $this->getCharacteristicValueByFormat($characteristics[$key], Characteristic::RANGE_FORMAT);
            case 4:
                return isset($characteristics[$key]->instructionType)
                    ? (string)$characteristics[$key]->instructionType : '';
            default:
                return '';
        }
    }

    /**
     * @param int $number
     * @param int $key
     * @param Characteristic[]|null $characteristics
     * @return string
     */
    private function getKtruUserValuesArray(int $number, int $key, array $characteristics): string
    {
        if (!$characteristics || !$characteristics[$key]) {
            return '';
        }
        switch ($number) {
            case 0:
                return $characteristics[$key]->name ?? '';
            case 1:
                return isset($characteristics[$key]->typeValue) ? (string)$characteristics[$key]->typeValue : '';
            case 2:
                return $characteristics[$key]->getUnit() !== null ? $characteristics[$key]->getUnit()->code : '';
            case 3:
                return $this->getCharacteristicValueByFormat($characteristics[$key], Characteristic::TEXT_FORMAT);
            case 4:
                return $this->getCharacteristicValueByFormat($characteristics[$key], Characteristic::NUMBER_FORMAT);
            case 5:
                return $this->getCharacteristicValueByFormat($characteristics[$key], Characteristic::RANGE_FORMAT);
            case 6:
                return isset($characteristics[$key]->instructionType)
                    ? (string)$characteristics[$key]->instructionType : '';
            default:
                return '';
        }
    }

    /**
     * @param Characteristic[] $characteristics
     * @return array
     * Собирает атрибуты всех обязательных неизменяемых характеристик
     */
    private function getCharacteristicsValuesToString(array $characteristics): array
    {
        $result = [];
        if (!$characteristics) {
            return $result;
        }
        foreach ($characteristics as $characteristic) {
            if (!$characteristic) {
                continue;
            }
            if ($characteristic->isRequired && $characteristic->kind === $characteristic::KIND_IMMUTABLE) {

                if ($characteristic->name) {
                    $result['name'][] = $characteristic->name;
                }

                if ($characteristic->instructionType) {
                    $result['instructionType'][] = $characteristic->instructionType;
                }

            }
        }
        return $result;
    }

    /**
     * @param array $characteristics
     * @return Characteristic[]
     *
     */
    private function getCharacteristicsValues(array $characteristics): array
    {
        $result = [];
        if (!$characteristics) {
            return $result;
        }

        foreach ($characteristics as $characteristic) {
            if (!$characteristic) {
                continue;
            }
            if ($this->isKtruChangeableCharacteristics($characteristic)) {
                $result['ktruChangeable'][] = $characteristic;
            }

            if ($this->isKtruUsersCharacteristics($characteristic)) {
                $result['ktruUsers'][] = $characteristic;
            }

            if ($this->isOkpd2Characteristics($characteristic)) {
                $result['okpd2'][] = $characteristic;
            }

        }
        return $result;
    }

    /**
     * @param Characteristic $characteristic
     * @return bool
     */
    private function isKtruChangeableCharacteristics(Characteristic $characteristic): bool
    {
        return $characteristic->type === $characteristic::KTRU_TYPE
            && $characteristic->kind !== $characteristic::KIND_IMMUTABLE;

    }

    /**
     * @param Characteristic $characteristic
     * @return bool
     */
    private function isKtruUsersCharacteristics(Characteristic $characteristic): bool
    {
        return $characteristic->type === $characteristic::KTRU_USER_TYPE && !$characteristic->isRequired;
    }

    /**
     * @param Characteristic $characteristic
     * @return bool
     */
    private function isOkpd2Characteristics(Characteristic $characteristic): bool
    {
        return $characteristic->type === $characteristic::OKPD2_TYPE
            && !$characteristic->isRequired;

    }

    /**
     * @param Characteristic|null $characteristic
     * @param int $format
     * @return string
     */
    private function getCharacteristicValueByFormat(?Characteristic $characteristic, int $format): string
    {
        if (!$characteristic || !isset($characteristic->values) || $characteristic->format !== $format) {
            return '';
        }

        if (
            $format === Characteristic::TEXT_FORMAT
            && (
                $this->isOkpd2Characteristics($characteristic)
                || $this->isKtruUsersCharacteristics($characteristic)
            )
        ) {
            return Str::splitText($characteristic->values);
        }

        return $characteristic->values;
    }
}