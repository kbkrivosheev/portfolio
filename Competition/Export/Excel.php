<?php

namespace app\models\Competition\Export;

use app\models\Competition\Forms\Base;
use app\models\Organization\Objects\Entities\Organizations;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use yii\helpers\ArrayHelper;


class Excel
{
    const NORMAL_LINE_HEIGHT = 30;
    const TITLE_LINE_HEIGHT = 50;

    /**
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function create(Base $form, array $data): array
    {
        $prices = [];
        $expenses = [];
        $characteristics = [];
        $qualifications = [];
        $finalGrade = [];

        foreach ($data as $id => $item) {
            $name = $this->getParameterValue($form->requests, $id, 'name');
            $prices[$name] = $item['prices'];
            $expenses[$name] = $item['expenses'];
            $characteristics[$name] = $item['characteristics'];
            $qualifications[$name] = $item['qualifications'];
            $finalGrade[$name] = $item['finalGrade'];
        }

        $docTitle = 'Результат оценки заявок';
        $spreadsheet = new Spreadsheet();
        $organizationName = Organizations::find()
            ->select('full_name')
            ->where(['inn' => $form->parameters->inn])
            ->andWhere(['status' => 1])
            ->scalar() ?: ' - ';

        $sheet = $spreadsheet->getActiveSheet();

        $fontName = 'Times New Roman';
        $titleStyle = [
            'font' => [
                'bold' => true,
                'size' => 14,
                'name' => $fontName,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],

        ];

        $titleStyle1 = [
            'font' => [
                'bold' => true,
                'size' => 12,
                'name' => $fontName,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],

        ];

        $textStyle = [
            'font' => [
                'bold' => false,
                'size' => 12,
                'name' => $fontName,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];


        $sheet->setTitle($docTitle);

        for ($i = 2; $i <= 11; $i++) {
            $sheet->mergeCells("A{$i}:C{$i}");
            $sheet->mergeCells("D{$i}:I{$i}");
        }

        for ($i = 1; $i <= 11; $i++) {
            $sheet->getRowDimension((string)($i))->setRowHeight(self::NORMAL_LINE_HEIGHT);
        }
        $sheet->getRowDimension('4')->setRowHeight(70);

        for ($i = 2; $i <= 11; $i++) {
            $sheet->getStyle("D{$i}")->applyFromArray($textStyle);
        }
        for ($i = 1; $i <= 12; $i++) {
            if (in_array($i, [1, 6, 12])) {
                $sheet->getStyle("A{$i}")->applyFromArray($titleStyle);
            } else {
                $sheet->getStyle("A{$i}")->applyFromArray($titleStyle1);
            }
        }
        $sheet->getStyle("C7")->applyFromArray($titleStyle1);
        $sheet->getStyle("D7")->applyFromArray($titleStyle1);


        /** ПАРАМЕТРЫ */
        $sheet->mergeCells("A1:I1");
        $sheet->setCellValue(
            'A1',
            $docTitle
        );

        $sheet->setCellValue('A2', 'Наименование организации:');
        $sheet->setCellValue('A3', 'Наименование объекта закупки:');
        $sheet->setCellValue('A4', 'Предмет закупки:');
        $sheet->setCellValue('A5', 'НМЦК');

        $sheet->setCellValue('D2', $organizationName);
        $sheet->setCellValue('D3', $form->parameters->name);
        $sheet->setCellValue('D4', $form->parameters->purchaseSubject);
        $sheet->setCellValue('D5', $form->parameters->nmck);


        $sheet->mergeCells("A6:I6");
        $sheet->setCellValue('A6', 'Предельные значимости критериев');

        $sheet->setCellValue('A7', 'Наименование критерия');
        $sheet->setCellValue('D7', 'Значимость критерия');

        $criteria = $form->parameters->criteria;

        $sheet->setCellValue('A8', 'Цена контракта, сумма цен единиц ТРУ');
        $sheet->setCellValue('D8', $criteria->contractPrice ?? 0);
        $sheet->setCellValue('A9', 'Расходы');
        $sheet->setCellValue('D9', $criteria->expense ?? 0);
        $sheet->setCellValue('A10', 'Характеристики объекта закупки');
        $sheet->setCellValue('D10', $criteria->characteristic ?? 0);
        $sheet->setCellValue('A11', 'Квалификация участников закупки');
        $sheet->setCellValue('D11', $criteria->qualification ?? 0);
        $j = 12;

        /** ЦЕНА  */
        if ($criteria->contractPrice) {
            $j = $this->addTitle($sheet, "Цена ({$criteria->contractPrice})", $j, $titleStyle);
            $sheet->mergeCells("B{$j}:C{$j}");
            $sheet->mergeCells("E{$j}:F{$j}");
            $sheet->mergeCells("G{$j}:I{$j}");
            $priceCellIdTitle = [
                'A' => '№',
                'B' => 'Номер заявки',
                'D' => 'Предложение',
                'E' => 'Оценка по критерию',
                'G' => 'Итоговая оценка'
            ];
            foreach ($priceCellIdTitle as $cellId => $title) {
                $sheet->setCellValue("{$cellId}{$j}", $title);
                $sheet->getStyle("{$cellId}{$j}")->applyFromArray($titleStyle1);
            }
            $sheet->getRowDimension((string)($j))->setRowHeight(self::NORMAL_LINE_HEIGHT);
            ++$j;
            $num = 1;
            foreach ($prices as $name => $price) {
                $sheet->mergeCells("B{$j}:C{$j}");
                $sheet->mergeCells("E{$j}:F{$j}");
                $sheet->mergeCells("G{$j}:I{$j}");
                $sheet->setCellValue("A{$j}", $num++);
                $sheet->setCellValue("B{$j}", $name);
                $sheet->setCellValue("D{$j}", $price['offer']);
                $sheet->setCellValue("E{$j}", $price['grade']);
                $sheet->setCellValue("G{$j}", $price['total']);
                foreach ($priceCellIdTitle as $cellId => $title) {
                    $sheet->getStyle("{$cellId}{$j}")->applyFromArray($textStyle);
                }
                $sheet->getRowDimension((string)($j))->setRowHeight(self::NORMAL_LINE_HEIGHT);
                $j++;
            }
        }

        /** РАСХОДЫ */
        if ($criteria->expense) {
            $j = $this->addTitle($sheet, "Расходы ({$criteria->expense})", $j, $titleStyle);
            $j = $this->addIndicatorTable($sheet, $j, $expenses, $titleStyle1, $textStyle);
        }


        /** ХАРАКТЕРИСТИКИ */
        if ($criteria->characteristic) {
            $characteristicsTitles = ArrayHelper::map($form->characteristics, 'id', 'name');
            $characteristicsCriteria = ArrayHelper::map($form->characteristics, 'id', 'criteria');
            $j = $this->addTitle($sheet, "Характеристики ({$criteria->characteristic})", $j, $titleStyle);

            $characteristicsTransformed = $this->transformCriteria(
                $characteristics,
                $characteristicsTitles,
                $characteristicsCriteria
            );

            foreach ($characteristicsTransformed as $characteristicTitle => $indicators) {
                $j = $this->addTitle($sheet, $characteristicTitle, $j, $titleStyle);
                $j = $this->addIndicatorTable($sheet, $j, $indicators, $titleStyle1, $textStyle);
            }

            $j = $this->addTitle(
                $sheet,
                'Итоговая оценка по критерию "Характеристики объекта закупки"',
                $j,
                $titleStyle
            );
            $finalTitles = ['Итоговая оценка по показателям', 'Итоговая оценка по критерию характеристики'];
            $characteristicsTitles = array_merge($characteristicsTitles, $finalTitles);
            $j = $this->addCriterionFinalTable(
                $sheet,
                $j,
                $characteristicsTitles,
                $characteristicsTransformed,
                $characteristics,
                $titleStyle1,
                $textStyle
            );
        }
        /** КВАЛИФИКАЦИЯ */
        if ($criteria->qualification) {
            $qualificationTitles = ArrayHelper::map($form->qualifications, 'id', 'name');
            $qualificationCriteria = ArrayHelper::map($form->qualifications, 'id', 'criteria');
            $j = $this->addTitle($sheet, "Квалификация ({$criteria->qualification})", $j, $titleStyle);
            $qualificationTransformed = $this->transformCriteria(
                $qualifications,
                $qualificationTitles,
                $qualificationCriteria
            );
            foreach ($qualificationTransformed as $qualificationTitle => $indicators) {
                $j = $this->addTitle($sheet, $qualificationTitle, $j, $titleStyle);
                $j = $this->addIndicatorTable($sheet, $j, $indicators, $titleStyle1, $textStyle);
            }

            $j = $this->addTitle(
                $sheet,
                'Итоговая оценка по критерию "Квалификация участников закупки"',
                $j,
                $titleStyle
            );
            $finalTitles = ['Итоговая оценка по показателям', 'Итоговая оценка по критерию квалификация'];
            $qualificationTitles = array_merge($qualificationTitles, $finalTitles);
            $j = $this->addCriterionFinalTable(
                $sheet,
                $j,
                $qualificationTitles,
                $qualificationTransformed,
                $qualifications,
                $titleStyle1,
                $textStyle
            );
        }

        $j = $this->addTitle($sheet, 'Итоговая оценка', $j, $titleStyle);

        /** ИТОГОВАЯ */
        $finalGradeTitle = [
            'A' => '№',
            'B' => 'Номер заявки',
            'C' => 'Цена',
            'D' => 'Расходы',
            'E' => 'Характеристики',
            'F' => 'Квалификация',
            'G' => 'Итоговая оценка'
        ];

        $j = $this->addGradeFinalTable($sheet, $j, $finalGrade, $finalGradeTitle, $titleStyle1, $textStyle);
        --$j;


        foreach (range('B', 'I') as $columnID) {
            $sheet->getColumnDimension($columnID)->setWidth(20);
        }
        $sheet->getStyle("A1:I{$j}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A1:I{$j}")->getAlignment()->setWrapText(true);


        $fileName = $docTitle . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename=' . $fileName);
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save("php://output");
        $xlsData = ob_get_clean();
        return [
            'op' => 'ok',
            'file' => "data:application/vnd.ms-excel; base64," . base64_encode($xlsData),
            'name' => $fileName
        ];
    }


    /**
     * @param array $parameters
     * @param string $id
     * @param string $name
     * @return mixed
     */
    private function getParameterValue(array $parameters, string $id, string $name)
    {
        $key = array_search($id, array_column($parameters, 'id'));
        return $parameters[$key]->$name;
    }

    private function addIndicatorTable(
        Worksheet $sheet,
        int $j,
        array $data,
        array $titleStyle1,
        array $textStyle
    ): int {
        $columnIdsTitlesArray = [
            'A' => '№',
            'B' => 'Номер заявки',
            'C' => 'Наименование детализирующего показателя',
            'E' => 'Предложение',
            'F' => 'Оценка по детализирующему показателю',
            'G' => 'Итоговая оценка',
            'H' => 'Оценка по всем детализирующим показателям',
            'I' => 'Итоговая оценка по всем детализирующим показателям',
        ];
        $sheet->mergeCells("C{$j}:D{$j}");
        foreach ($columnIdsTitlesArray as $columnId => $title) {
            $sheet->setCellValue("{$columnId}{$j}", $title);
            $sheet->getStyle("{$columnId}{$j}")->applyFromArray($titleStyle1);
        }

        $sheet->getRowDimension((string)($j))->setRowHeight(self::TITLE_LINE_HEIGHT);
        ++$j;
        $num = 1;

        foreach ($data as $name => $item) {
            $sheet->setCellValue("A{$j}", $num++);
            $sheet->setCellValue("B{$j}", $name);
            $sheet->setCellValue("H{$j}", $item['indicatorsGrade']);
            $sheet->setCellValue("I{$j}", $item['indicatorsTotal']);
            $tempRow = $j;
            foreach ($item as $indicator) {
                if (is_array($indicator)) {
                    $sheet->mergeCells("C{$j}:D{$j}");
                    $sheet->setCellValue("C{$j}", $indicator['name']);
                    $sheet->setCellValue("E{$j}", $indicator['offer']);
                    $sheet->setCellValue("F{$j}", $indicator['grade']);
                    $sheet->setCellValue("G{$j}", $indicator['total']);
                    foreach ($columnIdsTitlesArray as $columnId => $title) {
                        $sheet->getStyle("{$columnId}{$j}")->applyFromArray($textStyle);
                    }
                    $sheet->getRowDimension((string)($j))->setRowHeight(self::NORMAL_LINE_HEIGHT);
                    $j++;
                }
            }
            --$j;
            $sheet->mergeCells("A{$tempRow}:A{$j}");
            $sheet->mergeCells("B{$tempRow}:B{$j}");
            $sheet->mergeCells("H{$tempRow}:H{$j}");
            $sheet->mergeCells("I{$tempRow}:I{$j}");
            $j++;
        }
        return $j;
    }

    /**
     * @param Worksheet $sheet
     * @param string $title
     * @param int $j
     * @param array $titleStyle
     * @return int
     * @throws Exception
     */
    private function addTitle(Worksheet $sheet, string $title, int $j, array $titleStyle): int
    {
        $sheet->mergeCells("A{$j}:I{$j}");
        $sheet->setCellValue("A{$j}", $title);
        $sheet->getStyle("A{$j}")->applyFromArray($titleStyle);
        $sheet->getRowDimension((string)($j))->setRowHeight(self::NORMAL_LINE_HEIGHT);
        return ++$j;
    }

    /**
     * @param array $criteria
     * @param array $titleArray
     * @return array
     */

    private function transformCriteria(array $criteria, array $titleArray, $criteriaValueArray): array
    {
        $result = [];
        foreach ($criteria as $name => $criterion) {
            if (is_array($criterion) && !empty($criterion)) {
                foreach ($criterion as $type => $indicator) {
                    $title = $titleArray[$type] && $criteriaValueArray[$type] ?
                        $titleArray[$type] . ' (' . $criteriaValueArray[$type] . ')'
                        : ' - ';
                    $result[$title][$name] = array_merge(
                        $indicator['numericIndicators'] ?? [],
                        $indicator['notNumericIndicators'] ?? [],
                        $indicator['containingIndicators'] ?? []
                    );

                    $result[$title][$name]['indicatorsGrade'] = $indicator['indicatorsGrade'];
                    $result[$title][$name]['indicatorsTotal'] = $indicator['indicatorsTotal'];
                }
            }
        }

        array_pop($result);
        return $result;
    }

    /**
     * @param array $criteriaTransformed
     * @param array $criteria
     * @return array
     */
    private function getFinalTableData(array $criteriaTransformed, array $criteria): array
    {
        $result = [];
        foreach ($criteriaTransformed as $item) {
            foreach ($item as $name => $ind) {
                $result[$name][] = $ind['indicatorsTotal'];
            }
        }

        foreach ($criteria as $name => $criterion) {
            $result[$name][] = $criterion['totalIndicatorsGrade'];
            $result[$name][] = $criterion['totalCharacteristicsGrade'] ?: $criterion['totalQualificationsGrade'];
        }
        return $result;
    }

    private function addCriterionFinalTable(
        Worksheet $sheet,
        int $j,
        $criteriaTitles,
        array $criteriaTransformed,
        array $criteria,
        array $titleStyle1,
        array $textStyle
    ): int {
        $columnArray = $this->getColumnArray(range('C', 'I'), count($criteriaTitles));
        $columnIdsTitles = array_combine($columnArray, $criteriaTitles);
        $lastColumn = array_key_last($columnIdsTitles);
        if ($lastColumn !== 'I') {
            $sheet->mergeCells("{$lastColumn}{$j}:I{$j}");
        }
        $sheet->setCellValue("A{$j}", '№');
        $sheet->setCellValue("B{$j}", 'Номер заявки');
        foreach ($columnIdsTitles as $columnId => $title) {
            $sheet->setCellValue("{$columnId}{$j}", $title);
        }
        foreach (range('A', 'I') as $columnId) {
            $sheet->getStyle("{$columnId}{$j}")->applyFromArray($titleStyle1);
        }
        $sheet->getRowDimension((string)($j))->setRowHeight(90);
        ++$j;

        $criteriaFinalData = $this->getFinalTableData($criteriaTransformed, $criteria);

        $num = 1;
        foreach ($criteriaFinalData as $name => $item) {
            $columnArray = $this->getColumnArray(range('C', 'I'), count($item));
            $data = array_combine($columnArray, $item);
            $lastVColumn = array_key_last($data);
            if ($lastVColumn !== 'I') {
                $sheet->mergeCells("{$lastVColumn}{$j}:I{$j}");
            }
            $sheet->setCellValue("A{$j}", $num++);
            $sheet->setCellValue("B{$j}", $name);
            foreach ($data as $cellId => $value) {
                $sheet->setCellValue("{$cellId}{$j}", $value);
            }
            foreach (range('A', 'I') as $columnId) {
                $sheet->getStyle("{$columnId}{$j}")->applyFromArray($textStyle);
            }
            $sheet->getRowDimension((string)($j))->setRowHeight(self::NORMAL_LINE_HEIGHT);
            ++$j;
        }
        return $j;
    }

    private function addGradeFinalTable(
        Worksheet $sheet,
        int $j,
        array $finalGrade,
        array $finalGradeTitle,
        array $titleStyle1,
        array $textStyle
    ): int {
        $sheet->mergeCells("G{$j}:I{$j}");
        foreach ($finalGradeTitle as $columnId => $title) {
            $sheet->setCellValue("{$columnId}{$j}", $title);
            $sheet->getStyle("{$columnId}{$j}")->applyFromArray($titleStyle1);
        }
        $sheet->getRowDimension((string)($j))->setRowHeight(self::TITLE_LINE_HEIGHT);
        ++$j;
        $num = 1;
        foreach ($finalGrade as $name => $item) {
            $sheet->mergeCells("G{$j}:I{$j}");
            $sheet->setCellValue("A{$j}", $num++);
            $sheet->setCellValue("B{$j}", $name);
            $sheet->setCellValue("C{$j}", $item['price'] ?? 0);
            $sheet->setCellValue("D{$j}", $item['expense'] ?? 0);
            $sheet->setCellValue("E{$j}", $item['characteristic'] ?? 0);
            $sheet->setCellValue("F{$j}", $item['qualification'] ?? 0);
            $sheet->setCellValue("G{$j}", $item['total'] ?? 0);
            foreach (range('A', 'G') as $columnId) {
                $sheet->getStyle("{$columnId}{$j}")->applyFromArray($textStyle);
            }
            $sheet->getRowDimension((string)($j))->setRowHeight(self::NORMAL_LINE_HEIGHT);
            ++$j;
        }
        return $j;
    }

    private function getColumnArray(array $maxColumnArray, int $criteriaTitlesCount): array
    {
        return (count($maxColumnArray) !== $criteriaTitlesCount) ?
            array_slice($maxColumnArray, 0, $criteriaTitlesCount) :
            $maxColumnArray;
    }
}