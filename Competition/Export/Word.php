<?php

declare(strict_types=1);

namespace app\models\Competition\Export;

use app\models\Competition\Forms\Base as BaseForm;
use app\models\Organization\Objects\Entities\Organizations;
use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\Style\Cell;
use Yii;


class Word
{
    const YES = 'Присваивается 100 баллов заявке (части заявки), содержащей предложение о наличии характеристики ' .
    'объекта закупки, а при отсутствии характеристики объекта закупки - 0 баллов ;';

    const NO = 'Присваивается 100 баллов заявке (части заявки), содержащей предложение об отсутствии характеристики ' .
    'объекта закупки, а при наличии характеристики - 0 баллов ;';

    const  NAME_EVALUATION_CRITERION_TEXT = 'Наименование критерия оценки: ';
    const EVALUATION_INDICATOR_TEXT = 'Показатель оценки ';

    /**
     * под цифрами 3 и 4 подразумевается номер ряда в который необходимо вставлять текст
     */
    const CHARACTERISTICS_OR_QUALIFICATIONS = [
        '3' => 'характеристики объекта закупки',
        '4' => 'квалификация участников закупки'
    ];

    /**
     * Добавляет заголовок к тексту положения
     * @param string $characteristic
     * @param string $litera
     * @return array[]
     */
    private function getPositionTitle(string $characteristic, string $litera): array
    {
        return [
            [
                'text' => "Значение количества баллов по детализирующему показателю, присваиваемых заявке, подлежащей в " .
                    "соответствии с Федеральным законом оценке по критерию оценки «{$characteristic}» (БХi), рассчитывается" .
                    " по формуле, предусмотренной пп. «{$litera}» п. 20 Положения:",
            ]

        ];
    }

    /**
     * Добавлять центральный текст к положению
     * @param string $characteristic
     * @return array[]
     */
    private function getPositionTypicalText(string $characteristic): array
    {
        return [
            [
                'text' => "где:"
            ],
            [
                'text' => "Хmax - максимальное значение характеристики объекта закупки, содержащееся в заявках (частях заявок)," .
                    "подлежащих в соответствии с Федеральным законом оценке по критерию оценки «{$characteristic}»;"
            ],
            [
                'text' => "Хi - значение характеристики объекта закупки, содержащееся в предложении участника закупки, " .
                    "заявка (часть заявки) которого подлежит в соответствии с Федеральным законом оценке по критерию оценки «{$characteristic}»;"
            ],
            [
                'text' => "Хmin - минимальное значение характеристики объекта закупки, содержащееся в заявках (частях заявок), подлежащих" .
                    " в соответствии с Федеральным законом оценке по критерию оценки «{$characteristic}»."
            ]
        ];
    }

    /**
     * Добавляет формулу к положению
     * @param string $litera
     * @return array[]
     */
    private function getPositionImage(string $litera): array
    {
        $images = [
            'а' => [
                [
                    'image' => [
                        'src' => Yii::getAlias("@app/web/img/word/position/position_a.png"),
                        'width' => 200,
                        'height' => 60,
                        'align' => 'center'
                    ]
                ]
            ],
            'б' => [
               [
                   'image' => [
                       'src' => Yii::getAlias("@app/web/img/word/position/position_b.png"),
                       'width' => 200,
                       'height' => 60,
                       'align' => 'center'
                   ]
               ]
            ],
            'в' => [
                [
                    'image' => [
                        'src' => Yii::getAlias("@app/web/img/word/position/position_v.png"),
                        'width' => 200,
                        'height' => 60,
                        'align' => 'center'
                    ]
                ]
            ],
            'г' => [
                [
                    'image' => [
                        'src' => Yii::getAlias("@app/web/img/word/position/position_g.png"),
                        'width' => 200,
                        'height' => 60,
                        'align' => 'center'
                    ]
                ]
            ],
            'д' => [
                [

                    'image' => [
                        'src' => Yii::getAlias("@app/web/img/word/position/position_d.png"),
                        'width' => 200,
                        'height' => 60,
                        'align' => 'center'
                    ]
                ]
            ],
            'е' => [
                [
                    'image' => [
                        'src' => Yii::getAlias("@app/web/img/word/position/position_e.png"),
                        'width' => 200,
                        'height' => 60,
                        'align' => 'center'
                    ]
                ]
            ],
            'ж' => [
                [
                    'image' => [
                        'src' => Yii::getAlias("@app/web/img/word/position/position_j.png"),
                        'width' => 200,
                        'height' => 60,
                        'align' => 'center'
                    ]
                ]
            ],
            'з' => [
                [
                    'image' => [
                        'src' => Yii::getAlias("@app/web/img/word/position/position_z.png"),
                        'width' => 200,
                        'height' => 60,
                        'align' => 'center'
                    ]
                ]
            ],
        ];
        return $images[$litera];
    }

    /**
     * Добавляет минимальное и мксимально значение
     * @param int|null $minValue
     * @param int|null $maxValue
     * @return array
     */
    private function getPositionSpecialText(?int $minValue, ?int $maxValue): array
    {
        $text = [];
        if ($maxValue !== null) {
            $text = array_merge($text, [['text' => "Х пред max - предельное максимальное значение характеристики, установленное заказчиком."]]);
            $text = array_merge($text, [['text' => "Предельное максимальное значение характеристики: {$maxValue}"]]);
        }
        if ($minValue !== null) {
            $text = array_merge($text, [['text' => "Х пред min - предельное минимальное значение характеристики объекта закупки, установленное заказчиком."]]);
            $text = array_merge($text, [['text' => "Предельное минимальное значение характеристики: {$minValue}"]]);
        }
        return $text;
    }


    /**
     * Собирает воедино массив с текстом для каждого положения
     * @param int $line
     * @param string $litera
     * @param int|null $minValue
     * @param int|null $maxValue
     * @return array
     */
    private function position(int $line, string $litera, ?int $minValue = null, ?int $maxValue = null): array
    {
        $characteristic = self::CHARACTERISTICS_OR_QUALIFICATIONS[$line];
        $title = $this->getPositionTitle($characteristic, $litera);
        $image = $this->getPositionImage($litera);
        $typical = $this->getPositionTypicalText($characteristic);
        $special = $this->getPositionSpecialText($minValue, $maxValue);

        return array_merge($title, $image, $typical, $special);

    }

    /**
     * @param BaseForm $form
     * @return string[]
     * @throws Exception
     */
    public function create(BaseForm $form): array
    {
        $phpWord = new PhpWord();


        $this->addParameters($form, $phpWord);
        $this->addCriteria($form, $phpWord);
        $this->addProvisions($form, $phpWord);

        $fileName = 'ПОРЯДОК рассмотрения и оценки заявок на участие в конкурсе.docx';
        header('Content-Disposition: attachment; filename=' . $fileName);
        header('Content-Type: application/octet-stream');
        header('Cache-Control: max-age=0');
        $objWriter = IOFactory::createWriter($phpWord);
        ob_start();
        $objWriter->save('php://output');
        $docData = ob_get_clean();

        return [
            'op' => 'ok',
            'file' => "data:application/msword; base64," . base64_encode($docData),
            'name' => $fileName,
        ];
    }

    private function addParameters(BaseForm $form, PhpWord $phpWord)
    {
        $organization = Organizations::find()
            ->where(['inn' => $form->parameters->inn])
            ->andWhere(['status' => 1])
            ->one();

        $section = $phpWord->addSection();
        $fontTitleStyleName = ['name' => 'Times New Roman', 'bold' => true, 'size' => 14];
        $fontStyleName = 'rStyle';
        $phpWord->addFontStyle($fontStyleName, ['name' => 'Times New Roman', 'size' => 14]);
        $paragraphStyleName = 'pStyle';
        $phpWord->addParagraphStyle($paragraphStyleName, ['alignment' => Jc::CENTER]);

        $noteFontStyle = ['name' => 'Times New Roman', 'size' => 10, 'italic' => true, 'bold' => true];

        $section->addText(
            'ПОРЯДОК*',
            $fontTitleStyleName,
            $paragraphStyleName
        );
        $section->addText(
            'рассмотрения и оценки заявок на участие в конкурсе',
            $fontTitleStyleName,
            $paragraphStyleName
        );
        $section->addText(
            'I. Информация о заказчике и закупке '
            . 'товаров, работ, услуг для обеспечения государственных и муниципальных нужд (далее - закупка)',
            $fontTitleStyleName,
            $paragraphStyleName
        );

        $section->addTextBreak();

        $height = 500;
        $widthCol_1 = 3600;
        $widthCol_2 = 3840;
        $widthCol_3 = 1680;
        $widthCol_4 = 1920;
        $parametersTableStyleName = 'Parameters Table';
        $fancyTableStyle = ['borderSize' => 1, 'alignment' => JcTable::CENTER];
        $phpWord->addTableStyle($parametersTableStyleName, $fancyTableStyle);
        $table = $section->addTable($fancyTableStyle);

        $row = $table->addRow($height);
        $row->addCell($widthCol_1, ['vMerge' => 'restart'])->addText('Полное наименование', $fontStyleName);
        $row->addCell($widthCol_2, ['vMerge' => 'restart'])->addText($organization->full_name ?? '', $fontStyleName);
        $row->addCell($widthCol_3)->addText('ИНН', $fontStyleName);
        $row->addCell($widthCol_4)->addText($form->parameters->inn, $fontStyleName);

        $row = $table->addRow($height);
        $row->addCell($widthCol_1, ['vMerge' => 'continue']);
        $row->addCell($widthCol_2, ['vMerge' => 'continue']);
        $row->addCell($widthCol_3)->addText('КПП', $fontStyleName);
        $row->addCell($widthCol_4)->addText($organization->kpp ?? '', $fontStyleName);

        $row = $table->addRow($height);
        $row->addCell($widthCol_1)->addText('Место нахождения, телефон, адрес электронной почты', $fontStyleName);
        $row->addCell($widthCol_2)->addText(
            $organization->address . ' ' . $organization->phone . ' ' . $organization->email ?? '',
            $fontStyleName
        );
        $row->addCell($widthCol_3)->addText('по ОКТМО', $fontStyleName);
        $row->addCell($widthCol_4)->addText($organization->oktmo ?? '', $fontStyleName);

        $row = $table->addRow($height);


        $row->addCell($widthCol_1, ['vMerge' => 'restart'])->addText(
            'Наименование бюджетного, автономного учреждения, ' .
            'государственного, муниципального унитарного предприятия, ' .
            'иного юридического лица, которому переданы полномочия ' .
            'государственного, муниципального заказчика**',
            $fontStyleName
        );

        $row->addCell($widthCol_2, ['vMerge' => 'restart']);
        $row->addCell($widthCol_3)->addText('ИНН', $fontStyleName);
        $row->addCell($widthCol_4);

        $row = $table->addRow($height);
        $row->addCell($widthCol_1, ['vMerge' => 'continue']);
        $row->addCell($widthCol_2, ['vMerge' => 'continue']);
        $row->addCell($widthCol_3)->addText('КПП', $fontStyleName);
        $row->addCell($widthCol_4);

        $row = $table->addRow($height);
        $row->addCell($widthCol_1)->addText('Место нахождения, телефон, адрес электронной почты', $fontStyleName);
        $row->addCell($widthCol_2);
        $row->addCell($widthCol_3)->addText('по ОКТМО', $fontStyleName);
        $row->addCell($widthCol_4);

        $row = $table->addRow($height);
        $row->addCell($widthCol_1)->addText('Наименование объекта закупки', $fontStyleName);
        $row->addCell($widthCol_2, ['gridSpan' => 3])->addText($form->parameters->name, $fontStyleName);

        $section->addTextBreak();
        $section->addText(
            '*Документ заполнен путем автозаполнения. Внимательно прочитайте документ и внесите в него '
            . 'коррективы по необходимости, чтобы избежать несогласованности. Также удалите это и все иные примечания.',
            $noteFontStyle,
            ['alignment' => Jc::BOTH]
        );
        $section->addText(
            '**Указывается в случае передачи в соответствии с Бюджетным кодексом Российской Федерации '
            . 'бюджетному, автономному учреждению, государственному, муниципальному унитарному предприятию, '
            . 'иному юридическому лицу полномочий государственного, муниципального заказчика.',
            $noteFontStyle,
            ['alignment' => Jc::BOTH]
        );
    }

    private function addCriteria(BaseForm $form, PhpWord $phpWord)
    {
        $section = $phpWord->addSection(['orientation' => 'landscape']);
        $fontTitleStyleName = ['name' => 'Times New Roman', 'bold' => true, 'size' => 14];
        $fontFirstRowStyleName = ['name' => 'Times New Roman', 'bold' => true, 'size' => 10];
        $fontStyleName = ['name' => 'Times New Roman', 'size' => 10];
        $pTitleStyle = ['alignment' => Jc::CENTER];
        $pStyle = ['alignment' => Jc::START];
        $section->addText(
            'II. Критерии и показатели оценки заявок на участие в закупке',
            $fontTitleStyleName,
            $pTitleStyle
        );
        $section->addTextBreak();
        $height = 500;
        $widthCol_1 = 400;
        $widthCol_2 = 2500;
        $widthCol_3 = 500;
        $widthCol_4 = 2500;
        $widthCol_5 = 600;
        $widthCol_6 = 2000;
        $widthCol_7 = 800;
        $widthCol_8 = 6300;

        $tableStyle = ['borderSize' => 1, 'alignment' => JcTable::CENTER];
        $tableCellStyle = ['valign' => 'top'];
        $tableCellStyleDir90 = ['valign' => 'top', 'textDirection' => Cell::TEXT_DIR_BTLR];
        $cellRowSpan = ['vMerge' => 'restart', 'valign' => 'top'];
        $cellRowContinue = ['vMerge' => 'continue'];
        $table = $section->addTable($tableStyle);

        $row = $table->addRow(5000);
        $row->addCell($widthCol_1, $tableCellStyle)->addText(
            '№',
            $fontFirstRowStyleName,
            $pTitleStyle
        );
        $row->addCell($widthCol_2, $tableCellStyle)->addText(
            'Критерий оценки',
            $fontFirstRowStyleName,
            $pTitleStyle
        );
        $row->addCell($widthCol_3, $tableCellStyleDir90)->addText(
            'Значимость критерия оценки, процентов',
            $fontFirstRowStyleName,
            $pTitleStyle
        );
        $row->addCell($widthCol_4, $tableCellStyle)->addText(
            'Показатель оценки',
            $fontFirstRowStyleName,
            $pTitleStyle
        );
        $row->addCell($widthCol_5, $tableCellStyleDir90)->addText(
            'Значимость показателя оценки, процентов',
            $fontFirstRowStyleName,
            $pTitleStyle
        );
        $row->addCell($widthCol_6, $tableCellStyle)->addText(
            'Показатель оценки, детализирующий показатель оценки',
            $fontFirstRowStyleName,
            $pTitleStyle
        );
        $row->addCell($widthCol_7, $tableCellStyleDir90)->addText(
            'Значимость показателя, детализирующего показатель оценки, процентов',
            $fontFirstRowStyleName,
            $pTitleStyle
        );
        $row->addCell($widthCol_8, $tableCellStyle)->addText(
            'Формула оценки или шкала оценки',
            $fontFirstRowStyleName,
            $pTitleStyle
        );


        $criteria = $form->parameters->criteria;
        $lineNumber = 1;
        if ($criteria->contractPrice) {
            $row = $table->addRow($height);
            $row->addCell($widthCol_1, $tableCellStyle)->addText((string)$lineNumber++, $fontStyleName, $pStyle);
            $row->addCell($widthCol_2, $tableCellStyle)->addText(
                'Цена контракта, сумма цен единиц товара, работы, услуги',
                $fontStyleName,
                $pStyle
            );
            $row->addCell($widthCol_3, $tableCellStyle)->addText($criteria->contractPrice, $fontStyleName);
            $row->addCell($widthCol_4, $tableCellStyle)->addText('-', $fontStyleName, $pStyle);
            $row->addCell($widthCol_5, $tableCellStyle)->addText('-', $fontStyleName, $pStyle);
            $row->addCell($widthCol_6, $tableCellStyle)->addText('-', $fontStyleName, $pStyle);
            $row->addCell($widthCol_7, $tableCellStyle)->addText('-', $fontStyleName, $pStyle);
            $col_8 = $row->addCell($widthCol_8, $tableCellStyle);
            $col_8->addTextRun();
            $col_8->addText('1. Значение количества баллов по критерию оценки «цена контракта, сумма цен единиц товара, работы, услуги», присваиваемое заявке, которая подлежит в соответствии с Федеральным законом оценке по указанному критерию оценки, (БЦi) определяется по одной из следующих формул предусмотренных Положением об оценке заявок на участие в закупке товаров, работ, услуг для обеспечения государственных и муниципальных нужд, утвержденного постановлением Правительства Российской Федерации от 31 декабря 2021 г. № 2604 «Об оценке заявок на участие в закупке товаров, работ, услуг для обеспечения государственных и муниципальных нужд, внесении изменений в пункт 4 постановления Правительства Российской Федерации от 20 декабря 2021 г. N 2369 и признании утратившими силу некоторых актов и отдельных положений некоторых актов Правительства Российской Федерации» (далее - Положение):', $fontStyleName,
                $pStyle);

            $col_8->addTextRun();

            $col_8->addText("а) за исключением случаев, предусмотренных подпунктом «б» настоящего пункта и пунктом 10 Положения, - по формуле: (абзац введен Постановлением Правительства РФ от 31.10.2022 N 1946)", $fontStyleName, $pStyle);

            $col_8->addImage(Yii::getAlias("@app/web/img/word/formula1_price_kontract_r2.png"),
                ['width' => 200,
                    'height' => 60,
                    'align' => 'center']
            );
            $col_8->addText("где:", $fontStyleName, $pStyle);
            $col_8->addTextRun();
            $col_8->addText("Цi - предложение участника закупки о цене контракта, или о сумме цен всех контрактов, заключаемых по результатам проведения совместного конкурса (в случае проведения совместного конкурса), или о сумме цен единиц товара, работы, услуги (в случае, предусмотренном частью 24 статьи 22 Федерального закона от 05.04.2013 № 44-ФЗ «О контрактной системе в сфере закупок товаров, работ, услуг для обеспечения государственных и муниципальных нужд» (далее - Федеральный закон), в том числе при проведении в этом случае совместного конкурса), заявка (часть заявки) которого подлежит в соответствии с Федеральным законом оценке по критерию оценки «цена контракта, сумма цен единиц товара, работы, услуги» (далее - ценовое предложение);", $fontStyleName, $pStyle);
            $col_8->addTextRun();
            $col_8->addText("Цл - наилучшее ценовое предложение из числа предложенных в соответствии с Федеральным законом участниками закупки, заявки (части заявки) которых подлежат оценке по критерию оценки «цена контракта, сумма цен единиц товара, работы, услуги»;", $fontStyleName, $pStyle);
            $col_8->addTextRun();
            $col_8->addText("б) в случае если по результатам применения формулы, предусмотренной подпунктом «а» настоящего пункта, при оценке хотя бы одной заявки получено значение, являющееся отрицательным числом, значение количества баллов по критерию оценки «цена контракта, сумма цен единиц товара, работы, услуги» всем заявкам, подлежащим в соответствии с Федеральным законом оценке по указанному критерию оценки (БЦi), определяется по формуле:", $fontStyleName, $pStyle);
            $col_8->addImage(Yii::getAlias("@app/web/img/word/formula2_price_kontract_r2.png"),
                ['width' => 200,
                    'height' => 60,
                    'align' => 'center']
            );
            $col_8->addText("где:", $fontStyleName, $pStyle);
            $col_8->addTextRun();
            $col_8->addText("Цнач - начальная (максимальная) цена контракта, или сумма начальных (максимальных) цен каждого контракта, заключаемого по результатам проведения совместного конкурса (в случае проведения совместного конкурса), или начальная сумма цен единиц товаров, работ, услуг (в случае, предусмотренном частью 24 статьи 22 Федерального закона, в том числе при проведении в таком случае совместного конкурса).", $fontStyleName, $pStyle);
            $col_8->addTextRun();
            $col_8->addText("2. Если при проведении процедуры подачи предложений о цене контракта либо о сумме цен единиц товара, работы, услуги (в случае, предусмотренном частью 24 статьи 22 Федерального закона) в соответствии с Федеральным законом подано ценовое предложение, предусматривающее снижение таких цены контракта либо суммы цен ниже нуля, значение количества баллов по критерию оценки «цена контракта, сумма цен единиц товара, работы, услуги» (БЦi) определяется в следующем порядке:", $fontStyleName, $pStyle);
            $col_8->addTextRun();
            $col_8->addText("а) для подлежащей в соответствии с Федеральным законом оценке заявки участника закупки, ценовое предложение которого не предусматривает снижение цены контракта либо суммы цен ниже нуля, по критерию оценки «цена контракта, сумма цен единиц товара, работы, услуги» значение количества баллов по указанному критерию оценки (БЦi) определяется по формуле:", $fontStyleName, $pStyle);
            $col_8->addTextRun();
            $col_8->addImage(Yii::getAlias("@app/web/img/word/formula3_price_kontract_r2.png"),
                ['width' => 200,
                    'height' => 60,
                    'align' => 'center']
            );
            $col_8->addText("б) для подлежащей в соответствии с Федеральным законом оценке заявки участника закупки, ценовое предложение которого предусматривает снижение цены контракта либо суммы цен ниже нуля, по критерию оценки «цена контракта, сумма цен единиц товара, работы, услуги» значение количества баллов по указанному критерию оценки (БЦi) определяется по формуле:", $fontStyleName, $pStyle);
            $col_8->addImage(Yii::getAlias("@app/web/img/word/formula4_price_kontract_r2.png"),
                ['width' => 200,
                    'height' => 60,
                    'align' => 'center']
            );
        }

        if ($criteria->expense) {
            $row = $table->addRow($height);
            $row->addCell($widthCol_1, $cellRowSpan)->addText((string)$lineNumber++, $fontStyleName, $pStyle);
            $row->addCell($widthCol_2, $cellRowSpan)->addText(
                'Расходы на эксплуатацию и ремонт товаров, использование результатов работ (далее - расходы)',
                $fontStyleName,
                $pStyle
            );
            $row->addCell($widthCol_3, $cellRowSpan)->addText($criteria->expense, $fontStyleName, $pStyle);

            foreach ($form->expenses as $k => $expense) {
                $row->addCell($widthCol_4, $tableCellStyle)->addText($expense->name, $fontStyleName, $pStyle);
                $row->addCell($widthCol_5, $tableCellStyle)->addText($expense->criteria, $fontStyleName, $pStyle);
                $row->addCell($widthCol_6, $tableCellStyle)->addText('-', $fontStyleName, $pStyle);
                $row->addCell($widthCol_7, $tableCellStyle)->addText('-', $fontStyleName, $pStyle);
                if ($k === 0) {
                    $row->addCell($widthCol_8, $cellRowSpan)->addText(
                        'оценка заявок осуществляется по формуле, предусмотренной пунктом 14 Положения',
                        $fontStyleName,
                        $pStyle
                    );
                } else {
                    $row->addCell($widthCol_8, $cellRowContinue);
                }
                if ($k < count($form->expenses) - 1) {
                    $row = $table->addRow($height);
                    $row->addCell($widthCol_1, $cellRowContinue);
                    $row->addCell($widthCol_2, $cellRowContinue);
                    $row->addCell($widthCol_3, $cellRowContinue);
                }
            }
        }

        if ($criteria->characteristic) {
            //Переменная $line используется для того чтобы понять какой ряд таблицы заполняется, чтобы можно было вывести разный текст для каждой строки
            $line = 3;
            $row = $table->addRow($height);
            $row->addCell($widthCol_1, $cellRowSpan)->addText((string)$lineNumber++, $fontStyleName, $pStyle);
            $row->addCell($widthCol_2, $cellRowSpan)->addText(
                'Качественные, функциональные и экологические характеристики объекта закупки',
                $fontStyleName,
                $pStyle
            );
            $row->addCell($widthCol_3, $cellRowSpan)->addText($criteria->characteristic, $fontStyleName, $pStyle);

            foreach ($form->characteristics as $k => $characteristic) {
                $indicators = array_merge(
                    $characteristic->numericIndicators ?? [],
                    $characteristic->notNumericIndicators ?? [],
                    $characteristic->containingIndicators ?? []
                );
                $row->addCell($widthCol_4, $cellRowSpan)->addText($characteristic->name, $fontStyleName, $pStyle);
                $row->addCell($widthCol_5, $cellRowSpan)->addText($characteristic->criteria, $fontStyleName, $pStyle);

                foreach ($indicators as $j => $indicator) {
                    $col_6 = $row->addCell($widthCol_6, $cellRowSpan);
                    $col_7 = $row->addCell($widthCol_7, $cellRowSpan);
                    $col_8 = $row->addCell($widthCol_8, $cellRowSpan);
                    $this->addIndicator($indicator, $line, $col_6, $col_7, $col_8, $fontStyleName, $pStyle);

                    if ($j < count($indicators) - 1) {
                        $row = $table->addRow($height);
                        $row->addCell($widthCol_1, $cellRowContinue);
                        $row->addCell($widthCol_2, $cellRowContinue);
                        $row->addCell($widthCol_3, $cellRowContinue);
                        $row->addCell($widthCol_4, $cellRowContinue);
                        $row->addCell($widthCol_5, $cellRowContinue);
                    }
                }
                if ($k < count($form->characteristics) - 1) {
                    $row = $table->addRow($height);
                    $row->addCell($widthCol_1, $cellRowContinue);
                    $row->addCell($widthCol_2, $cellRowContinue);
                    $row->addCell($widthCol_3, $cellRowContinue);
                }
            }
        }

        if ($criteria->qualification) {
            //Переменная $line используется для того чтобы понять какой ряд таблицы заполняется, чтобы можно было вывести разный текст для каждой строки
            $line = 4;
            $row = $table->addRow($height);
            $row->addCell($widthCol_1, $cellRowSpan)->addText((string)$lineNumber++, $fontStyleName, $pStyle);
            $row->addCell($widthCol_2, $cellRowSpan)->addText(
                'Квалификация участников закупки, в том числе наличие у них финансовых ресурсов, ' .
                'оборудования и других материальных ресурсов на праве собственности или ином законном ' .
                'основании, опыта работы, связанного с предметом контракта, и деловой репутации,' .
                'специалистов и иных работников определенного уровня квалификации',
                $fontStyleName,
                $pStyle
            );
            $row->addCell($widthCol_3, $cellRowSpan)->addText($criteria->qualification, $fontStyleName, $pStyle);
            foreach ($form->qualifications as $k => $qualification) {
                $indicators = array_merge(
                    $qualification->numericIndicators ?? [],
                    $qualification->notNumericIndicators ?? [],
                    $qualification->containingIndicators ?? []
                );


                $row->addCell($widthCol_4, $cellRowSpan)->addText($qualification->name, $fontStyleName, $pStyle);
                $row->addCell($widthCol_5, $cellRowSpan)->addText($qualification->criteria, $fontStyleName, $pStyle);

                foreach ($indicators as $j => $indicator) {
                    $col_6 = $row->addCell($widthCol_6, $cellRowSpan);
                    $col_7 = $row->addCell($widthCol_7, $cellRowSpan);
                    $col_8 = $row->addCell($widthCol_8, $cellRowSpan);
                    $this->addIndicator($indicator, $line, $col_6, $col_7, $col_8, $fontStyleName, $pStyle);

                    if ($j < count($indicators) - 1) {
                        $row = $table->addRow($height);
                        $row->addCell($widthCol_1, $cellRowContinue);
                        $row->addCell($widthCol_2, $cellRowContinue);
                        $row->addCell($widthCol_3, $cellRowContinue);
                        $row->addCell($widthCol_4, $cellRowContinue);
                        $row->addCell($widthCol_5, $cellRowContinue);
                    }
                }
                if ($k < count($form->qualifications) - 1) {
                    $row = $table->addRow($height);
                    $row->addCell($widthCol_1, $cellRowContinue);
                    $row->addCell($widthCol_2, $cellRowContinue);
                    $row->addCell($widthCol_3, $cellRowContinue);
                }
            }
        }
    }

    private function addProvisions(BaseForm $form, PhpWord $phpWord)
    {
        $section = $phpWord->addSection(['orientation' => 'landscape']);
        $fontTitleStyle = ['name' => 'Times New Roman', 'bold' => true, 'size' => 14];
        $fontFirstRowStyle = ['name' => 'Times New Roman', 'bold' => true, 'size' => 10];
        $fontStyle = ['name' => 'Times New Roman', 'size' => 10];
        $pTitleStyle = ['alignment' => Jc::CENTER];
        $pStyle = ['alignment' => Jc::START];

        $section->addText(
            'III. Отдельные положения о применении отдельных критериев оценки, показателей оценки и показателей ' .
            'оценки, детализирующих показатели оценки, предусмотренных разделом II настоящего документа.',
            $fontTitleStyle,
            $pTitleStyle
        );
        $section->addTextBreak();
        $height = 500;
        $widthCol_1 = 500;
        $widthCol_2 = 5200;
        $widthCol_3 = 8800;

        $tableStyle = ['borderSize' => 1, 'alignment' => JcTable::CENTER,];
        $tableCellStyle = ['valign' => 'top'];
        $table = $section->addTable($tableStyle);

        $row = $table->addRow($height);
        $row->addCell($widthCol_1, $tableCellStyle)->addText('№', $fontFirstRowStyle, $pTitleStyle);
        $row->addCell($widthCol_2, $tableCellStyle)->addText(
            'Наименование критерия оценки, показателя оценки, ' .
            'показателя оценки, детализирующего показатель оценки, при применении которого устанавливается положение, предусмотренное графой 3',
            $fontFirstRowStyle,
            $pTitleStyle
        );
        $row->addCell($widthCol_3, $tableCellStyle)->addText(
            'Положения о применении критерия оценки, показателя оценки, показателя оценки, ' .
            'детализирующего показатель оценки',
            $fontFirstRowStyle,
            $pTitleStyle
        );

        $row = $table->addRow($height);
        $row->addCell($widthCol_1, $tableCellStyle)->addText('1', $fontStyle, $pStyle);
        $row->addCell($widthCol_2, $tableCellStyle)->addText(
            'Общая информация',
            $fontStyle, $pStyle
        );
        $purchaseSubject = $form->parameters->purchaseSubject;
        $purchaseSubjectId = $form->parameters->purchaseSubjectId;

        $data = $row->addCell($widthCol_3, $tableCellStyle);

        if ($purchaseSubjectId !== null){
            $data->addText(
                "Значимость критерия оценки определяется с учетом Положения и предельных величин значимости критериев" .
                " оценки в соответствии с позицией № {$purchaseSubjectId} «{$purchaseSubject}» Приложения №2 Положения.",
                $fontStyle, $pStyle
            );
        }else{
            $data->addText(
                "Значимость критерия оценки определяется с учетом Положения и предельных величин значимости критериев" .
                " оценки в соответствии с позицией «{$purchaseSubject}» Приложения №2 Положения.",
                $fontStyle, $pStyle
            );
        }

        $data->addText(
            "Оценка заявки (части заявки) по критерию оценки определяется путем суммирования среднего количества баллов," .
            " присвоенных всеми принимавшими участие в ее рассмотрении и оценке членами комиссии по осуществлению " .
            "закупок по каждому показателю оценки, умноженного на значимость соответствующего показателя оценки. При " .
            "этом среднее количество баллов определяется путем суммирования количества баллов, присвоенных каждым членом " .
            "комиссии по осуществлению закупок, и последующего деления на количество таких членов.",
            $fontStyle, $pStyle
        );
        $data->addText(
            "Оценка заявки (части заявки) по показателю оценки определяется путем суммирования среднего количества ".
            "баллов, присвоенных всеми принимавшими участие в ее рассмотрении и оценке членами комиссии по осуществлению".
            " закупок по каждому детализирующему показателю, умноженного на значимость соответствующего детализирующего ".
            "показателя. При этом среднее количество баллов определяется путем суммирования количества баллов, присвоенных".
            " каждым членом комиссии по осуществлению закупок, и последующего деления на количество таких членов.",
            $fontStyle, $pStyle
        );
        $criteria = $form->parameters->criteria;
        $i = 1;
        if ($criteria->contractPrice) {
            $row = $table->addRow($height);
            $row->addCell($widthCol_1, $tableCellStyle)->addText((string)++$i, $fontStyle, $pStyle);
            $cell_2 = $row->addCell($widthCol_2, $tableCellStyle);
            $cell_2->addText(
                self::NAME_EVALUATION_CRITERION_TEXT,
                $fontFirstRowStyle,
                $pStyle
            );
            $cell_2->addTextBreak();
            $cell_2->addText(
                'Цена контракта, сумма цен единиц товара, работы, услуги',
                $fontStyle,
                $pStyle
            );
            $cell_3 = $row->addCell($widthCol_3, $tableCellStyle);
            $cell_3->addText(
                'Оценка заявок по критерию оценки «цена контракта, сумма цен единиц товара, работы, услуги» ' .
                'осуществляется в соответствии со следующими требованиями:',
                $fontStyle,
                $pStyle
            );
            $cell_3->addTextBreak();
            $cell_3->addText(
                'а) заявкам, содержащим наилучшее ценовое предложение, а также предложение, равное такому '
                . 'наилучшему ценовому предложению, присваивается 100 баллов;',
                $fontStyle,
                $pStyle
            );

            $cell_3->addText(
                'б) значение Цл при применении формулы, предусмотренной подпунктом «а» пункта 10 Положения (пп. «а» п. 2 Раздел II критерия «Цена контракта, сумма цен единиц товара, работы, услуги» настоящего Порядка рассмотрения и оценки заявок на участие в конкурсе), и значения Цл и Цi при применении формулы, предусмотренной подпунктом «б» пункта 10 Положения (пп. «б» п. 2 Раздел II критерия «Цена контракта, сумма цен единиц товара, работы, услуги» настоящего Порядка рассмотрения и оценки заявок на участие в конкурсе), указываются без знака «минус»;',
                $fontStyle,
                $pStyle
            );
            $cell_3->addText(
                'в) показатели оценки по критерию оценки «цена контракта, сумма цен единиц товара, работы, услуги» не применяются. ',
                $fontStyle,
                $pStyle
            );
        }
        if ($criteria->expense) {
            $row = $table->addRow($height);
            $row->addCell($widthCol_1, $tableCellStyle)->addText((string)++$i, $fontStyle, $pStyle);
            $cell_2 = $row->addCell($widthCol_2, $tableCellStyle);
            $cell_2->addText(
                self::NAME_EVALUATION_CRITERION_TEXT,
                $fontFirstRowStyle,
                $pStyle
            );
            $cell_2->addText(
                'Расходы',
                $fontStyle,
                $pStyle
            );

            $cell_3 = $row->addCell($widthCol_3, $tableCellStyle);
            $cell_3->addText(
                'Критерий оценки «расходы» применяется в целях определения наименьшего значения, не предусмотренных '
                . 'условиями контакта расходов, которые возникнут у заказчика после приемки закупаемых товаров, работ. '
                . 'При применении критерия оценки "расходы":',
                $fontStyle,
                $pStyle
            );
            $cell_3->addTextBreak();
            $cell_3->addText(
                'а) применяются исключительно количественные значения;',
                $fontStyle,
                $pStyle
            );

            $cell_3->addText(
                'б) размер предложения участника закупки по критерию оценки «расходы» не может быть равным нулю или ниже нуля;',
                $fontStyle,
                $pStyle
            );

            foreach ($form->expenses as $k => $expense) {
                ++$k;
                $cell_2->addTextBreak();
                $cell_2->addText(
                    self::EVALUATION_INDICATOR_TEXT . ' ' . $k,
                    $fontFirstRowStyle,
                    $pStyle
                );
                $cell_2->addText(
                    $expense->name . ';',
                    $fontStyle,
                    $pStyle
                );
                $cell_3->addTextBreak();
                $cell_3->addText(
                    "Порядок применения показателя оценки $k: ",
                    $fontFirstRowStyle,
                    $pStyle
                );
                $cell_3->addText(
                    $expense->name . ';',
                    $fontStyle,
                    $pStyle
                );

                $cell_3->addText(
                    "Единица измерения: {$expense->unit->name};",
                    $fontStyle,
                    $pStyle
                );
                $additionalInformation = $expense->additionalInformation ?: 'отсутствуют';
                $cell_3->addText(
                    "Перечень свойств объекта закупки, подлежащих оценке: {$additionalInformation};",
                    $fontStyle,
                    $pStyle
                );
            }
        }
        if ($criteria->characteristic) {
            foreach ($form->characteristics as $characteristic) {
                $row = $table->addRow($height);
                $row->addCell($widthCol_1, $tableCellStyle)->addText((string)++$i, $fontStyle, $pStyle);
                $cell_2 = $row->addCell($widthCol_2, $tableCellStyle);
                $cell_2->addText(
                    self::NAME_EVALUATION_CRITERION_TEXT,
                    $fontFirstRowStyle,
                    $pStyle
                );
                $cell_2->addText(
                    'Характеристики объекта закупки',
                    $fontStyle,
                    $pStyle
                );
                $cell_3 = $row->addCell($widthCol_3, $tableCellStyle);
                $indicators = array_merge(
                    $characteristic->numericIndicators,
                    $characteristic->notNumericIndicators,
                    $characteristic->containingIndicators
                );
                $cell_2->addText(
                    self::EVALUATION_INDICATOR_TEXT,
                    $fontFirstRowStyle,
                    $pStyle
                );
                $cell_2->addText(
                    $characteristic->name,
                    $fontStyle,
                    $pStyle
                );
                foreach ($indicators as $k => $indicator) {
                    $cell_2->addTextBreak();
                    ++$k;
                    $cell_2->addText(
                        "Детализирующий показатель {$k} ",
                        $fontFirstRowStyle,
                        $pStyle
                    );
                    $cell_2->addText(
                        $indicator->name,
                        $fontStyle,
                        $pStyle
                    );

                    $cell_3->addText(
                        "Порядок применения детализирующего показателя оценки $k: ",
                        $fontFirstRowStyle,
                        $pStyle
                    );
                    $cell_3->addText(
                        $indicator->name . ';',
                        $fontStyle,
                        $pStyle
                    );

                    if (isset($indicator->unit, $indicator->unit->name)) {
                        $cell_3->addText(
                            "Единица измерения: {$indicator->unit->name};",
                            $fontStyle,
                            $pStyle
                        );
                    }

                    if (isset($indicator->minValue) || isset($indicator->maxValue)) {


                        $cell_3->addTextBreak();
                        $cell_3->addText(
                            'Если установлены предусмотренные пунктом 20 Положения предельное максимальное '
                            . 'и (или) предельное минимальное значение характеристики объекта закупки (или квалификации '
                            . 'участника) и в предложении участника закупки содержится значение характеристики объекта '
                            . 'закупки (или квалификации участника), которое выше и (или) ниже такого предельного '
                            . 'значения соответственно, баллы по детализирующему показателю в соответствии с пунктом '
                            . '20 Положения присваиваются в размере, предусмотренном для соответствующего предельного '
                            . 'значения детализирующего показателя.',
                            $fontStyle,
                            $pStyle
                        );
                    }

                    if (isset($indicator->propertiesList)) {
                        $cell_3->addTextBreak();
                        $cell_3->addText(
                            'Перечень свойств объекта закупки, подлежащих оценке: ',
                            $fontStyle,
                            $pStyle
                        );

                        $cell_3->addText(
                            $indicator->propertiesList . ';',
                            $fontStyle,
                            $pStyle
                        );
                    }


                    if (isset($indicator->isContaining)) {
                        $isContaining = $indicator->isContaining ? self::YES : self::NO;
                        $cell_3->addTextBreak();
                        $cell_3->addText(
                            'Если в случае, указанном в пункте 22 Положения, предусматривается оценка наличия или ' .
                            'отсутствия характеристики объекта закупки, шкала оценки должна предусматривать присвоение: '
                            ,
                            $fontStyle,
                            $pStyle
                        );

                        $cell_3->addText(
                            $isContaining,
                            $fontStyle,
                            $pStyle
                        );
                    }

                    if (isset($indicator->additionalInformation)) {
                        $cell_3->addTextBreak();
                        $additionalInformation = $indicator->additionalInformation ?: 'отсутствуют';
                        $cell_3->addText(
                            "Перечень свойств объекта закупки, подлежащих оценке: {$additionalInformation};",
                            $fontStyle,
                            $pStyle
                        );
                        $cell_3->addTextBreak();
                    }
                }
            }
        }
        if ($criteria->qualification) {
            foreach ($form->qualifications as $qualification) {
                $row = $table->addRow($height);
                $row->addCell($widthCol_1, $tableCellStyle)->addText((string)++$i, $fontStyle, $pStyle);
                $cell_2 = $row->addCell($widthCol_2, $tableCellStyle);
                $cell_2->addText(
                    self::NAME_EVALUATION_CRITERION_TEXT,
                    $fontFirstRowStyle,
                    $pStyle
                );
                $cell_2->addText(
                    'Квалификация участников закупки',
                    $fontStyle,
                    $pStyle
                );
                $cell_3 = $row->addCell($widthCol_3, $tableCellStyle);
                $indicators = array_merge(
                    $qualification->numericIndicators,
                    $qualification->notNumericIndicators,
                    $qualification->containingIndicators
                );
                $cell_2->addText(
                    self::EVALUATION_INDICATOR_TEXT,
                    $fontFirstRowStyle,
                    $pStyle
                );
                $cell_2->addText(
                    $qualification->name,
                    $fontStyle,
                    $pStyle
                );
                foreach ($indicators as $k => $indicator) {
                    $cell_2->addTextBreak();
                    ++$k;
                    $cell_2->addText(
                        "Детализирующий показатель {$k} ",
                        $fontFirstRowStyle,
                        $pStyle
                    );
                    $cell_2->addText(
                        $indicator->name,
                        $fontStyle,
                        $pStyle
                    );

                    $cell_3->addText(
                        "Порядок применения детализирующего показателя оценки $k: ",
                        $fontFirstRowStyle,
                        $pStyle
                    );
                    $cell_3->addText(
                        $indicator->name . ';',
                        $fontStyle,
                        $pStyle
                    );

                    if ($indicator->parentId === 'specialists') {
                        $cell_3->addText(
                            'Перечень специалистов и иных работников, необходимых для поставки товара, выполнения '
                            . 'работ, оказания услуг, являющихся объектом закупки, и их квалификация:',
                            $fontStyle,
                            $pStyle
                        );
                    }

                    if (isset($indicator->unit) && isset($indicator->unit->name)) {
                        $cell_3->addText(
                            "Единица измерения: {$indicator->unit->name};",
                            $fontStyle,
                            $pStyle
                        );
                    }

                    if (isset($indicator->contractSubject)) {
                        $cell_3->addText(
                            "Предмет договора: {$indicator->contractSubject};",
                            $fontStyle,
                            $pStyle
                        );
                    }
                    if (isset($indicator->okved2, $indicator->okved2->name)) {
                        $cell_3->addText(
                            "Область наличия у участников закупки деловой репутации: {$indicator->okved2->name} - {$indicator->okved2->code};",
                            $fontStyle,
                            $pStyle
                        );
                    }

                    if (isset($indicator->minValue) || isset($indicator->maxValue)) {

                        $cell_3->addTextBreak();
                        $cell_3->addText(
                            'Если установлены предельное максимальное и (или) предельное минимальное значение '
                            . 'характеристики объекта закупки и в предложении участника закупки содержится значение '
                            . 'характеристики объекта закупки, которое выше и (или) ниже такого предельного значения '
                            . 'соответственно, баллы по детализирующему показателю в соответствии с пунктом 20 Положения '
                            . 'присваиваются в размере, предусмотренном для соответствующего предельного значения '
                            . 'характеристики объекта закупки.',
                            $fontStyle,
                            $pStyle
                        );
                    }

                    if (isset($indicator->propertiesList)) {
                        $cell_3->addTextBreak();
                        $cell_3->addText(
                            "Перечень свойств объекта закупки, подлежащих оценке: {$indicator->propertiesList};",
                            $fontStyle,
                            $pStyle
                        );
                    }

                    if (isset($indicator->meaning)) {
                        $cell_3->addTextBreak();
                        $cell_3->addText(
                            'Значения количества баллов:',
                            $fontStyle,
                            $pStyle
                        );


                        $cell_3->addText(
                            $indicator->meaning . ';',
                            $fontStyle,
                            $pStyle
                        );
                    }

                    if (isset($indicator->isContaining)) {
                        $isContaining = $indicator->isContaining ? self::YES : self::NO;
                        $cell_3->addTextBreak();
                        $cell_3->addText(
                            'Если в случае, указанном в пункте 22 Положения, предусматривается оценка наличия или ' .
                            'отсутствия характеристики объекта закупки, шкала оценки должна предусматривать присвоение: ',
                            $fontStyle,
                            $pStyle
                        );
                        $cell_3->addText(
                            $isContaining,
                            $fontStyle,
                            $pStyle
                        );
                    }


                    if (isset($indicator->documents) && $indicator->parentId === 'ownership') {
                        if ($indicator->documents->confirming) {
                            $title = 'Перечень документов, подтверждающих наличие оборудования и других материальных ресурсов, '
                                . 'предусмотренных перечнем, установленным в соответствии с подпунктом "а" настоящего пункта: ';
                            $this->addDocuments(
                                $indicator->documents->confirming,
                                $title,
                                $cell_3,
                                $fontStyle,
                                $pStyle
                            );
                        }

                        $cell_3->addTextBreak();
                        $cell_3->addText(
                            'К оценке принимаются документы только в случае их представления в заявке в полном объеме ' .
                            'и со всеми приложениями. При проведении открытого конкурса в электронной форме или закрытого ' .
                            'конкурса в электронной форме такие документы направляются в форме электронных документов или ' .
                            'в форме электронных образов бумажных документов. При проведении закрытого конкурса ' .
                            'направляются документы или заверенные участником закупки их копии.',
                            $fontStyle,
                            $pStyle
                        );
                    }

                    if (isset($indicator->documents) && $indicator->parentId === 'experience') {
                        if (!empty($indicator->documents->confirming)) {
                            $title = 'Перечень документов, подтверждающих наличие у участника закупки опыта поставки товара, ' .
                                'выполнения работы, оказания услуги, связанного с предметом контракта, в том числе исполненный ' .
                                'договор (договоры), акт (акты) приемки поставленного товара, выполненных работ, оказанных ' .
                                'услуг, составленные при исполнении такого договора (договоров):';
                            $this->addDocuments(
                                $indicator->documents->confirming,
                                $title,
                                $cell_3,
                                $fontStyle,
                                $pStyle
                            );
                        }

                        if (isset($indicator->documents->isExecutedContractOnly) && true === $indicator->documents->isExecutedContractOnly) {
                            $cell_3->addTextBreak();
                            $cell_3->addText(
                                'Установлено положение о принятии к оценке исключительно исполненного договора ' .
                                '(договоров), при исполнении которого поставщиком (подрядчиком, исполнителем) исполнены ' .
                                'требования об уплате неустоек (штрафов, пеней) (в случае начисления неустоек).',
                                $fontStyle,
                                $pStyle
                            );
                        }

                        $cell_3->addTextBreak();
                        $cell_3->addText(
                            'Последний акт, составленный при исполнении договора, должен быть подписан не ранее' .
                            ' чем за 5 лет до даты окончания срока подачи заявок. К оценке принимаются исполненные' .
                            ' участником закупки с учетом правопреемства (в случае наличия в заявке подтверждающего' .
                            ' документа) гражданско-правовые договоры, в том числе заключенные и исполненные в соответствии с Законом №44-ФЗ.',
                            $fontStyle,
                            $pStyle
                        );

                        $cell_3->addText(
                            'К оценке принимаются документы, в случае их представления в заявке в полном' .
                            ' объеме и со всеми приложениями. При проведении открытого конкурса в электронной' .
                            ' форме или закрытого конкурса в электронной форме такие документы направляются' .
                            ' в форме электронных документов или в форме электронных образов бумажных документов.' .
                            ' При проведении закрытого конкурса направляются документы или заверенные участником закупки их копии.',
                            $fontStyle,
                            $pStyle
                        );
                    }


                    if (isset($indicator->documents) && $indicator->parentId === 'reputation') {
                        if ($indicator->documents->confirming) {
                            $title = 'Связанные с предметом контракта виды деятельности в соответствии с Общероссийским ' .
                                'классификатором видов экономической деятельности, в отношении которых участнику закупки ' .
                                'присвоен индекс деловой репутации:';
                            $this->addDocuments(
                                $indicator->documents->confirming,
                                $title,
                                $cell_3,
                                $fontStyle,
                                $pStyle
                            );
                        }
                        if ($indicator->documents->activities) {
                            $title = 'Документ, предусмотренный соответствующим национальным стандартом в области оценки ' .
                                'деловой репутации субъектов предпринимательской деятельности и подтверждающий присвоение' .
                                'участнику закупки значения индекса деловой репутации:';
                            $this->addDocuments(
                                $indicator->documents->activities,
                                $title,
                                $cell_3,
                                $fontStyle,
                                $pStyle
                            );
                        }

                        $cell_3->addTextBreak();
                        $cell_3->addText(
                            'Осуществляется оценка количественного значения индекса деловой репутации участников ' .
                            'закупки в соответствии с национальным стандартом в области оценки деловой репутации ' .
                            'субъектов предпринимательской деятельности.',
                            $fontStyle,
                            $pStyle
                        );
                    }

                    if (isset($indicator->documents) && $indicator->parentId === 'specialists' && $indicator->documents->availability) {
                        $title = 'Перечень следующих документов, подтверждающих наличие специалистов и иных работников,'
                            . 'их квалификацию:';
                        $this->addDocuments($indicator->documents->availability, $title, $cell_3, $fontStyle, $pStyle);
                    }

                    if (isset($indicator->additionalInformation)) {
                        $cell_3->addTextBreak();
                        $additionalInformation = $indicator->additionalInformation ?: 'отсутствуют';
                        $cell_3->addText(
                            "Перечень свойств объекта закупки, подлежащих оценке: {$additionalInformation};",
                            $fontStyle,
                            $pStyle
                        );
                    }
                    $cell_3->addTextBreak();
                }
            }
        }
    }

    private function getLitera($indicator)
    {
        if (true === $indicator->isMaximumValueBest) {
            if ($indicator->minValue && $indicator->maxValue) {
                return 'з';
            }

            if ($indicator->minValue) {
                return 'ж';
            }

            if ($indicator->maxValue) {
                return 'е';
            }

            return 'б';
        } else {
            if ($indicator->minValue && $indicator->maxValue) {
                return 'д';
            }

            if ($indicator->minValue) {
                return 'в';
            }

            if ($indicator->maxValue) {
                return 'г';
            }

            return 'а';
        }
    }

    /**
     * Функция строит word элементы по полученному массиву
     * @param array $texts
     * @param $col
     * @param $fontStyleName
     * @param $pStyle
     * @return void
     */
    private function wordBuilder(array $texts, $col, $fontStyleName, $pStyle): void
    {

        foreach ($texts as $text) {
            if (isset($text['text'])) {
                $col->addText($text['text'], $fontStyleName, $pStyle);
                $col->addTextBreak();
            }
            if (isset($text['image'])) {
                $img = $text['image'];
                $col->addImage($img['src'],
                    [
                        'width' => $img['width'],
                        'height' => $img['height'],
                        'align' => $img['align']
                    ]
                );
            }
        }
    }


    /**
     * Функция возвращает массив с текстом в зависмомти от необходимого положения закона
     * @param string $litera
     * @param int $line
     * @param $indicator
     * @return array
     */
    private function getContentPosition(string $litera, int $line, $indicator): array
    {

        $min = $indicator->minValue ?? null;
        $max = $indicator->maxValue ?? null;
        return $this->position($line, $litera, $min, $max);
    }

    private function addIndicator($indicator, $line, $col_6, $col_7, $col_8, $fontStyleName, $pStyle)
    {
        $col_6->addText($indicator->name, $fontStyleName, $pStyle);
        $col_7->addText($indicator->criteria, $fontStyleName, $pStyle);
        if (isset($indicator->isMaximumValueBest)) {
            $litera = $this->getLitera($indicator);
            $text = $this->getContentPosition($litera, $line, $indicator);
            $this->wordBuilder($text, $col_8, $fontStyleName, $pStyle);
        }

        if (isset($indicator->meaning)) {
            $col_8->addText(
                "Детализирующий показатель установлен в соответствии с п. 22 Положения, которым предусмотрено, что в " .
                "случае отсутствия функциональной зависимости между значением характеристики объекта закупки, " .
                "определенной количественным значением, и значением количества присваиваемых баллов, а также в случае, " .
                "если характеристика не определяется количественным значением, значение количества баллов по " .
                "детализирующему показателю присваивается заявке, подлежащей в соответствии с Федеральным законом " .
                "оценке по критерию оценки «характеристики объекта закупки», по шкале оценки. При этом документом, " .
                "предусмотренным приложением № 1 к настоящему Положению, устанавливаются значения количества баллов, " .
                "присваиваемые за предлагаемое (предлагаемые) участником закупки количественное значение (значения) " .
                "характеристики объекта закупки или предлагаемое участником закупки свойство (свойства) объекта закупки.",
                $fontStyleName,
                $pStyle
            );
            $col_8->addTextBreak();
            $col_8->addText("Шкала оценки:",
                $fontStyleName,
                $pStyle
            );
            $col_8->addText(
                $indicator->meaning,
                $fontStyleName,
                $pStyle
            );
        }

        if (isset($indicator->isContaining)) {

            $col_8->addText(
                "Детализирующий показатель установлен в соответствии с п. 23 Положения, которым предусматривается " .
                "оценка наличия или отсутствие характеристики объекта закупки:",
                $fontStyleName,
                $pStyle
            );
            $isContaining = $indicator->isContaining ? self::YES : self::NO;
            $col_8->addText(
                'Порядок оценки предложений:',
                $fontStyleName,
                $pStyle
            );
            $col_8->addText(
                $isContaining,
                $fontStyleName,
                $pStyle
            );
        }
    }

    private function addDocuments($documents, string $title, $cell, array $fontStyle, array $pStyle)
    {
        $documentsArray = is_string($documents) ? explode(
            ';',
            $documents
        ) : $documents;
        $cell->addTextBreak();
        $cell->addText(
            $title,
            $fontStyle,
            $pStyle
        );
        $cell->addTextBreak();

        if (is_array($documentsArray)) {
            foreach ($documentsArray as $item) {
                $cell->addText(
                    $item . ';',
                    $fontStyle,
                    $pStyle
                );
            }
        } else {
            $cell->addText(
                $documentsArray . ';',
                $fontStyle,
                $pStyle
            );
        }
    }
}