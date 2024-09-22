<?php

declare(strict_types=1);

namespace app\Features\PurposePurchase\Export\Excel;

use app\Features\PurposePurchase\Export\Excel\Forms\Base;
use app\Features\PurposePurchase\Export\Excel\Forms\Characteristic;
use app\Features\PurposePurchase\Export\Excel\Forms\Purchases;
use app\Features\PurposePurchase\Export\Excel\Handbooks\Styles;
use DomainException;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\RichText\Run;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class NoticeHandler
{
    private const INSTRUCTIONS = [
        1 => "Участник закупки указывает в заявке диапазон значений характеристики",
        2 => "Участник закупки указывает в заявке конкретное значение характеристики",
        3 => "Участник закупки указывает в заявке только одно значение характеристики",
        4 => "Участник закупки указывает в заявке одно или несколько значений характеристики",
        5 => "Участник закупки указывает в заявке все значения характеристики",
        6 => "Значение характеристики не может изменяться участником закупки"
    ];
    private const OKPD2_EIS_CHARACTERISTICS_LIMIT = 15;
    private const KTRU_USERS_EIS_CHARACTERISTICS_LIMIT = 3;
    private const KTRU_NOT_REQUIRED_EIS_CHARACTERISTICS_LIMIT = 10;


    /**
     * @param Purchases $purchases
     * @return string
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function handle(Purchases $purchases): string
    {

        $end = 'H';
        $title = 'ОПИСАНИЕ ОБЪЕКТОВ ЗАКУПКИ';
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        // Заголовок
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:D3');
        $sheet->mergeCells('E3:H3');
        $sheet->mergeCells('A4:A5');
        $sheet->mergeCells('B4:B5');
        $sheet->mergeCells('G4:G5');
        $sheet->mergeCells('H4:H5');
        $sheet->mergeCells('C4:F4');
        $linkText = 'СГЕНЕРИРОВАНО НА ПОРТАЛЕ ПРОГОСЗАКАЗ.РФ';
        $linkUrl = 'https://xn--80aahqcqybgko.xn--p1ai/';
        $sheet->setCellValue('A1', $title);
        $sheet->setCellValue('A2', $linkText);
        $sheet->setCellValue('A3', 'Тип объекта закупки');
        $sheet->setCellValue('E3', 'Товар');
        $sheet->setCellValue('A4', 'Наименование товара, работы, услуги');
        $sheet->setCellValue('B4', 'Код позиции');
        $sheet->setCellValue('C4', 'Характеристики товара, работы, услуги');
        $sheet->setCellValue('C5', 'Наименование характеристики');
        $sheet->setCellValue('D5', 'Значение характеристики');
        $sheet->setCellValue('E5', 'Единица измерения характеристики');
        $sheet->setCellValue('F5', 'Инструкция по заполнению характеристик в заявке');
        $sheet->setCellValue('G4', 'Количество(объем работы, услуги)');
        $sheet->setCellValue('H4', 'Единица измерения');

        $sheet->getCell('A2')->getHyperlink()->setUrl($linkUrl);
        //Название листа
        $sheet->setTitle($title);
        //Границы
        $sheet->getStyle("A3:{$end}5")->applyFromArray(Styles::BORDER_BOTTOM);

        //Стили заголовков таблицы
        $sheet->getStyle("A1:{$end}5")->applyFromArray(Styles::TITLE_NOTICE_STYLE);

        //Высота строк
        $sheet->getRowDimension(5)->setRowHeight(50);

        /**
         * Ширина столбцов
         */
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(40);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(30);

        /**
         * Данные
         */
        $baseRow = 6;
        $isHighlightRows = false;
        foreach ($purchases->getPurchases() as $purchase) {
            $startLimitRow = 0;
            $startLimitUserRow = 0;
            $sheet->setCellValue("A{$baseRow}", $purchase->name);
            if ($purchase->userKtruJustification) {
                $this->setUserKtruJustification($purchase, $sheet, $baseRow);
            }
            $sheet->setCellValue("B{$baseRow}", $this->getOkpd2KtruCodeName($purchase));
            $sheet->setCellValue("G{$baseRow}", $purchase->quantity);
            $sheet->setCellValue("H{$baseRow}", $purchase->getUnit() ? $purchase->getUnit()->name : '-');
            $row = $baseRow;
            $characteristics = $this->getCharacteristics($purchase->getCharacteristics());
            $mainCounter = 0;
            if (!empty($characteristics['ktruAndOkpd'])) {
                foreach ($characteristics['ktruAndOkpd'] as $characteristic) {
                    $row = $this->addCharacteristic($characteristic, $sheet, $row);
                    $ktru = $purchase->getKtru();
                    $limit = $ktru
                        ? self::KTRU_NOT_REQUIRED_EIS_CHARACTERISTICS_LIMIT
                        : self::OKPD2_EIS_CHARACTERISTICS_LIMIT;
                    if (!$ktru && ++$mainCounter === $limit) {
                        $startLimitRow = $row;
                    } elseif ($ktru && $characteristic->kind !== $characteristic::KIND_IMMUTABLE
                        && ++$mainCounter === $limit) {
                        $startLimitRow = $row;
                    }
                }
            }
            if ($startLimitRow) {
                $isHighlightRows = $this->highlightOverLimitRows($sheet, $startLimitRow, $row - 1);
            }
            if (!empty($characteristics['ktruUsers'])) {
                $sheet->mergeCells("C{$row}:F{$row}");
                $sheet->setCellValue("C{$row}", 'Дополнительные характеристики');
                ++$row;
                $usersCounter = 0;
                foreach ($characteristics['ktruUsers'] as $characteristic) {
                    $row = $this->addCharacteristic($characteristic, $sheet, $row);
                    if (++$usersCounter === self::KTRU_USERS_EIS_CHARACTERISTICS_LIMIT) {
                        $startLimitUserRow = $row;
                    }
                }
            }
            --$row;
            $sheet->mergeCells("A{$baseRow}:A{$row}");
            $sheet->mergeCells("B{$baseRow}:B{$row}");
            $sheet->mergeCells("G{$baseRow}:G{$row}");
            $sheet->mergeCells("H{$baseRow}:H{$row}");
            $baseRow = $row + 1;
            if ($startLimitUserRow) {
                $isHighlightRows = $this->highlightOverLimitRows($sheet, $startLimitUserRow, $row);
            }
        }

        if ($isHighlightRows) {
            $sheet->mergeCells("A{$baseRow}:{$end}{$baseRow}");
            $sheet->setCellValue("A{$baseRow}", '*Жёлтым выделены характеристики, не вошедшие в отчёт для ЕИС по причине ограничений ЕИС');
        }
        --$baseRow;
        $sheet->getStyle("A3:{$end}{$baseRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A6:{$end}{$baseRow}")->applyFromArray(Styles::TEXT_STYLE);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save("php://output");
        if ($xlsData = ob_get_clean()) {
            return $xlsData;
        }

        throw new DomainException('Ошибка сохранения файла, обратитесь к администратору.');
    }

    /**
     * @param array $characteristics
     * @return array
     */
    private function getCharacteristics(array $characteristics): array
    {
        $result = [];
        if (!$characteristics) {
            return $result;
        }

        foreach ($characteristics as $characteristic) {
            if (!$characteristic) {
                continue;
            }

            if ($this->isKtruUsersCharacteristics($characteristic)) {
                $result['ktruUsers'][] = $characteristic;
            } else {
                $result['ktruAndOkpd'][] = $characteristic;
            }
        }
        return $result;
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
     * @param Base $purchase
     * @return string
     */
    private function getOkpd2KtruCodeName(Base $purchase): string
    {
        $result = '';
        if (($okpd2 = $purchase->getOkpd2()) && ($ktru = $purchase->getKtru())) {
            $result = $okpd2->code . ' - ' . $okpd2->name . ' / ' . $ktru->code . ' - ' . $ktru->name;
        } elseif ($okpd2) {
            $result = $okpd2->code . ' - ' . $okpd2->name;
        }
        return $result;
    }

    /**
     * @param int $type
     * @return string
     */
    private function getInstruction(int $type): string
    {
        return self::INSTRUCTIONS[$type] ?? '';
    }

    /**
     * @param Characteristic $characteristic
     * @param Worksheet $sheet
     * @param int $row
     * @return int
     * @throws Exception
     */
    private function addCharacteristic(Characteristic $characteristic, Worksheet $sheet, int $row): int
    {
        $sheet->setCellValue("C{$row}", $characteristic->name);
        $sheet->setCellValue("E{$row}", $characteristic->getUnit() ? $characteristic->getUnit()->name : '-');
        $sheet->setCellValue("F{$row}", $this->getInstruction($characteristic->instructionType));
        return $this->addCharacteristicValues($characteristic, $sheet, $row);
    }

    /**
     * @param Characteristic $characteristic
     * @param Worksheet $sheet
     * @param int $row
     * @return int
     * @throws Exception
     */
    private function addCharacteristicValues(Characteristic $characteristic, Worksheet $sheet, int $row): int
    {
        if (strpos($characteristic->values, ';') !== false) {
            $valuesArray = explode(";", $characteristic->values);
            $valRow = $row;
            foreach ($valuesArray as $value) {
                $sheet->setCellValue("D{$valRow}", $value);
                $valRow++;
            }
            --$valRow;
            $sheet->mergeCells("C{$row}:C{$valRow}");
            $sheet->mergeCells("E{$row}:E{$valRow}");
            $sheet->mergeCells("F{$row}:F{$valRow}");
            $row = $valRow + 1;
        } else {
            $sheet->setCellValue("D{$row}", $characteristic->values);
            $sheet->setCellValue("E{$row}", $characteristic->getUnit() ? $characteristic->getUnit()->name : '-');
            $sheet->setCellValue("F{$row}", $this->getInstruction($characteristic->instructionType));
            ++$row;
        }
        return $row;
    }

    /**
     * @param Run $changeable
     * @param bool $italic
     * @return void
     */
    private function setFontParams(Run $changeable, bool $italic = false): void
    {
        if ($changeable->getFont() === null) {
            return;
        }
        $changeable->getFont()->setSize(Styles::FONT_SIZE);
        $changeable->getFont()->setName(Styles::FONT_NAME);
        $changeable->getFont()->setItalic($italic);
    }

    /**
     * @param Base $purchase
     * @param Worksheet $sheet
     * @param int $baseRow
     * @return void
     */
    private function setUserKtruJustification(Base $purchase, Worksheet $sheet, int $baseRow): void
    {
        $richText = new RichText();
        $changeable = $richText->createTextRun($purchase->name . PHP_EOL . PHP_EOL);
        $this->setFontParams($changeable);
        $changeable = $richText->createTextRun(
            'Обоснование включения дополнительной информации в сведения о товаре, работе, услуге: '
        );
        $this->setFontParams($changeable, true);
        $changeable = $richText->createTextRun(PHP_EOL . $purchase->userKtruJustification);
        $this->setFontParams($changeable);
        $sheet->setCellValue("A{$baseRow}", $richText);
        $sheet->getRowDimension($baseRow)->setRowHeight(85);
    }

    /**
     * @param Worksheet $sheet
     * @param int $startLimitRow
     * @param int $endLimitRow
     * @return bool
     * Выделяет строки характеристик, которые не вошли в отчёт для ЕИС
     * @throws Exception
     */
    private function highlightOverLimitRows(Worksheet $sheet, int $startLimitRow, int $endLimitRow): bool
    {
        if ($startLimitRow > $endLimitRow) {
            return false;
        }
        $sheet->getStyle("C{$startLimitRow}:F{$endLimitRow}")->applyFromArray(Styles::YELLOW_HIGHLIGHT);
        return true;
    }
}