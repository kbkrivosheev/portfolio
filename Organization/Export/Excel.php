<?php

declare(strict_types=1);

namespace app\models\Organization\Export;

use app\models\Organization\Objects\Entities\Organizations;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use yii\base\Model;

class Excel extends Model
{
    /**
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function create(array $models)
    {
        set_time_limit(3600);
        ini_set('memory_limit', '2512M');
        $model = new Organizations();
        $title = 'Реестр организаций';
        $date = (new \DateTimeImmutable)->format('d-m-Y');
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $titleStyle = [
            'font' => [
                'bold' => true,
                'size' => 16,
                'name' => 'Times New Roman',
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],

        ];
        $titleStyle1 = [
            'font' => [
                'bold' => true,
                'size' => 14,
                'name' => 'Times New Roman',
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
                'name' => 'Times New Roman',
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],

        ];

        $sheet->setTitle($title);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->getRowDimension(2)->setRowHeight(40);

        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->applyFromArray($titleStyle);
        $sheet->mergeCells('A1:N1');

        $sheet->setCellValue('A2', 'N п/п');
        $sheet->setCellValue('B2', $model->getAttributeLabel('full_name'));
        $sheet->setCellValue('C2', $model->getAttributeLabel('short_name'));
        $sheet->setCellValue('D2', $model->getAttributeLabel('inn'));
        $sheet->setCellValue('E2', $model->getAttributeLabel('kpp'));
        $sheet->setCellValue('F2', $model->getAttributeLabel('ogrn'));
        $sheet->setCellValue('G2', $model->getAttributeLabel('oktmo'));
        $sheet->setCellValue('H2', $model->getAttributeLabel('address'));
        $sheet->setCellValue('I2', $model->getAttributeLabel('phone') . '/' . $model->getAttributeLabel('fax'));
        $sheet->setCellValue('J2', $model->getAttributeLabel('email'));
        $sheet->setCellValue('K2', $model->getAttributeLabel('website'));
        $sheet->setCellValue('L2', $model->getAttributeLabel('region'));
        $sheet->setCellValue('M2', $model->getAttributeLabel('role'));
        $sheet->setCellValue('N2', $model->getAttributeLabel('status'));
        $sheet->getStyle('A2:N2')->applyFromArray($titleStyle1);

        $i = 3;
        $j = 1;
        foreach ($models as $item) {
            $sheet->setCellValue("A{$i}", $j++);
            $sheet->setCellValue("B{$i}", $item->full_name);
            $sheet->setCellValue("C{$i}", $item->short_name);
            $sheet->setCellValueExplicit("D{$i}", $item->inn, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$i}", $item->kpp, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$i}", $item->ogrn, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$i}", $item->oktmo, DataType::TYPE_STRING);
            $sheet->setCellValue("H{$i}", $item->address);
            $sheet->setCellValue("I{$i}", $item->phone . '  ' . $item->fax);
            $sheet->setCellValue("J{$i}", $item->email);
            $sheet->setCellValue("K{$i}", $item->website);
            $sheet->setCellValue("L{$i}", $item->region);
            $sheet->setCellValue("M{$i}", $item->role);
            $sheet->setCellValue("N{$i}", $item::statusLabels()[$item->status]??'');
            $sheet->getRowDimension($i)->setRowHeight(150);
            $i++;
        }
        $i--;
        $sheet->getStyle("A1:N{$i}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A1:N{$i}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("A3:N{$i}")->applyFromArray($textStyle);
        foreach (range('D', 'N') as $columnID) {
            $sheet->getColumnDimension($columnID)->setWidth(13);
        }
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(15);


        $fileName = $title . ' - ' . $date . '.xlsx';
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

}