<?php

namespace app\models\Okpd2\Command\Export\Excel;

use app\helpers\ExcelHelper;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class Handler
{
    private const TITLE_STYLE = [
        'font' => [
            'bold' => true,
            'size' => 14,
            'name' => 'Times New Roman',
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => [
                'argb' => 'FFDBB6'
            ]
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ]
    ];
    private const TEXT_STYLE = [
        'font' => [
            'bold' => false,
            'size' => 14,
            'name' => 'Times New Roman',
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ]
    ];

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function handle(Form $form): array
    {
        $sortedOkpd2List = $this->sortOkpd2List($form->getOkpd2List());
        $groupedOkpd2List = $this->groupByLotIndexes($sortedOkpd2List);
        return $this->createExcel($groupedOkpd2List);
    }

    /**
     * @param Okpd2Form[] $okpd2List
     * @return Okpd2Form[][][]
     */
    private function groupByLotIndexes(array $okpd2List): array
    {
        $group = [];
        foreach ($okpd2List as $okpd2) {
            $group[$okpd2->lotIndex][$okpd2->subLotIndex][] = $okpd2;
        }
        return $group;
    }

    /**
     * @param Okpd2Form[][][] $groupedOkpd2List
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    private function createExcel(array $groupedOkpd2List): array
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(date('d-m-Y'));

        $sheet->getStyle("A:G")->applyFromArray(self::TEXT_STYLE);
        $sheet->getStyle("A1:G1")->applyFromArray(self::TITLE_STYLE);
        $sheet->getRowDimension(1)->setRowHeight(30);
        ExcelHelper::setColumnWidth($sheet, 'A', 'G', 28);

        $sheet->setCellValue('A1', 'Наименование');
        $sheet->setCellValue('B1', 'ОКПД2');
        $sheet->setCellValue('C1', 'КТРУ');
        $sheet->setCellValue('D1', 'Национальный режим ');
        $sheet->setCellValue('E1', 'Преференции');
        $sheet->setCellValue('F1', 'Типовой контракт');
        $sheet->setCellValue('G1', 'Прочее');

        $row = 1;
        foreach ($groupedOkpd2List as $lotIndex => $subLotIndexes) {
            foreach ($subLotIndexes as $subLotIndex => $okpd2List) {
                $row++;
                $sheet->setCellValueExplicit(
                    "A{$row}",
                    $this->getLotName($lotIndex, $subLotIndex),
                    DataType::TYPE_STRING
                );
                $sheet->getStyle("A{$row}")->applyFromArray(self::TITLE_STYLE);
                $sheet->mergeCells("A{$row}:G{$row}");
                $sheet->getRowDimension($row)->setRowHeight(20);

                foreach ($okpd2List as $okpd2) {
                    $row++;

                    $sheet->setCellValueExplicit("A{$row}", $okpd2->name, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("B{$row}", $okpd2->okpd2, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("C{$row}", $okpd2->ktru, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit(
                        "D{$row}",
                        implode(', ', $okpd2->nationalRegimeTags),
                        DataType::TYPE_STRING
                    );
                    $sheet->setCellValueExplicit(
                        "E{$row}",
                        implode(', ', $okpd2->preferenceTags),
                        DataType::TYPE_STRING
                    );
                    $sheet->setCellValueExplicit(
                        "G{$row}",
                        implode(', ', $okpd2->otherTags),
                        DataType::TYPE_STRING
                    );
                    $sheet->getRowDimension($row)->setRowHeight(-1);

                    $contractLinkRowIndex = $row;
                    foreach ($okpd2->contractLinks as $index => $contractLink) {
                        $sheet->setCellValueExplicit(
                            "F{$contractLinkRowIndex}",
                            'Ссылка №' . (++$index),
                            DataType::TYPE_STRING
                        );
                        $sheet->getCell("F{$contractLinkRowIndex}")->getHyperlink()->setUrl($contractLink);
                        $contractLinkRowIndex++;
                    }

                    if ($row !== $contractLinkRowIndex) {
                        --$contractLinkRowIndex;
                        $sheet->mergeCells("A{$row}:A{$contractLinkRowIndex}");
                        $sheet->mergeCells("B{$row}:B{$contractLinkRowIndex}");
                        $sheet->mergeCells("C{$row}:C{$contractLinkRowIndex}");
                        $sheet->mergeCells("D{$row}:D{$contractLinkRowIndex}");
                        $sheet->mergeCells("E{$row}:E{$contractLinkRowIndex}");
                        $sheet->mergeCells("G{$row}:G{$contractLinkRowIndex}");
                        $row = $contractLinkRowIndex;
                    }
                }
            }
        }

        $sheet->getStyle("A1:G{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $fileName = 'ОКПД2 ' . date('d-m-Y H-i-s') . uniqid('', true) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename=' . $fileName);
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save("php://output");
        $xlsData = ob_get_clean();

        return [
            'status' => true,
            'file' => "data:application/vnd.ms-excel; base64," . base64_encode($xlsData),
            'name' => $fileName
        ];
    }

    /**
     * Сортировка по lotIndex и subLotIndex для вывода в правильном порядке.
     * Пример: Лот №1, Лот №2, Лот №2.1, Лот №2.2, Лот №..., Без лота
     * @param Okpd2Form[] $okpd2List
     * @return  Okpd2Form[]
     */
    private function sortOkpd2List(array $okpd2List): array
    {
        uasort($okpd2List, static function ($first, $second) {
            /** @var Okpd2Form $first */
            /** @var Okpd2Form $second */
            if ($first->lotIndex === $second->lotIndex) {
                return $first->subLotIndex <=> $second->subLotIndex;
            }
            if ($first->lotIndex === Okpd2Form::DEFAULT_LOT_INDEX
                || $second->lotIndex === Okpd2Form::DEFAULT_LOT_INDEX) {
                return $second->lotIndex <=> $first->lotIndex;
            }
            return $first->lotIndex <=> $second->lotIndex;
        });
        return $okpd2List;
    }

    private function getLotName(int $lotIndex, int $subLotIndex): string
    {
        if ($lotIndex === Okpd2Form::DEFAULT_LOT_INDEX) {
            return 'Без лота';
        }

        if ($subLotIndex === Okpd2Form::DEFAULT_SUB_LOT_INDEX) {
            return "Лот №" . $lotIndex;
        }

        return "Лот №" . $lotIndex . '.' . $subLotIndex;
    }
}
