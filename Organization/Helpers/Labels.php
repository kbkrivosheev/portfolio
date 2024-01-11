<?php
declare(strict_types = 1);

namespace app\models\Organization\Helpers;

class Labels
{
    public static function getRoles(): array
    {
        return [
            'CU' => 'Заказчик',
            'RA' => 'Уполномоченный орган',
            'AI' => 'Уполномоченное учреждение',
            'SO' => 'Специализированная организация',
            'CO' => 'Контрольный орган',
            'SP' => 'Служба Оператора сайта',
            'FO' => 'Финансовый орган',
            'EO' => 'Оператор электронной площадки',
            'AA' => 'Орган аудита',
            'CA' => 'Орган по регулированию контрактной системы в сфере закупок',
            'NA' => 'Орган, размещающий правила нормирования',
            'DA' => 'Орган, устанавливающий требования к отдельным видам товаров, работ, услуг',
            'BA' => 'Банк',
            'TA' => 'Орган, разрабатывающий и утверждающий типовые контракты и типовые условия контрактов',
            'OA' => 'Организация, осуществляющая полномочия заказчика на осуществление закупок на основании договора (соглашения) в соответствии с частью 6 статьи 15 Закона № 44-ФЗ',
            'CIA' => 'Орган контроля соответствия информации об объемах финансового обеспечения и идентификационных кодах закупок',
            'ICB' => 'Орган внутреннего государственного (муниципального) финансового контроля',
            'NP' => 'Орган, уполномоченный на ведение реестра недобросовестных поставщиков',
            'GR' => 'Главный распорядитель бюджетных средств',
            'OV' => 'Орган государственной (исполнительной) власти',
            'CS' => 'Заказчик, осуществляющий закупки в соответствии с частью 5 статьи 15 Федерального закона № 44-ФЗ',
            'CC' => 'Заказчик, осуществляющий закупки в соответствии с Федеральным законом № 44-ФЗ, в связи с неразмещением положения о закупке в соответствии с положениями Федерального закона № 223-ФЗ',
            'AU' => 'Заказчик, осуществляющий закупку на проведение обязательного аудита;IS - Оператор информационной системы, взаимодействующей с ЕИС',
            'BT' => 'Орган, уполномоченный на ведение библиотеки типовых контрактов, типовых условий контрактов',
            'MP' => 'Орган, осуществляющий мониторинг закупок',
            'MC' => 'Организация, осуществляющая мониторинг соответствия в соответствии с Федеральным законом № 223–ФЗ',
            'AC' => 'Организация, осуществляющая оценку соответствия в соответствии с Федеральным законом № 223–ФЗ',
            'TRU' => 'Орган, уполномоченный на формирование и ведение каталога товаров, работ, услуг для обеспечения государственных и муниципальных нужд',
            'OKT' => 'Оператор каталога товаров, работ, услуг для обеспечения государственных и муниципальных нужд',
            'TC' => 'Орган местного самоуправления и (или) государственное, муниципальное бюджетное, казенное учреждение в случаях, предусмотренных частью 4 статьи 182 Жилищного кодекса Российской Федерации, осуществляющие функции технического заказчика',
            'RPO' => 'Орган, уполномоченный на ведение реестра квалифицированных подрядных организаций',
            'RD' => 'Орган, уполномоченный на ведение реестра договоров об оказании услуг и (или) выполнении работ по капитальному ремонту общего имущества в многоквартирном доме',
            'REP' => 'Орган исполнительной власти, предоставляющий информацию и документы для включения в реестр единственных поставщиков товара, производство которого создается или модернизируется и (или) осваивается на территории Российской Федерации',
            'PIK' => 'Производитель товаров в соответствии со специальным инвестиционным контрактом',
            'RO' => 'Региональный оператор, осуществляющий закупки товаров, работ, услуг в соответствии с частью 1.1 статьи 180 и частью 5 статьи 182 Жилищного кодекса РФ',
            'OTR' => 'Федеральный орган исполнительной власти, уполномоченный на формирование сведений каталога товаров, работ, услуг',
            'TKO' => 'Региональный оператор для обращения с ТБО',
            'CN' => 'Заказчик, осуществляющий закупки в соответствии с частью 4.1 статьи 15 Федерального закона № 44-ФЗ',
            'SR' => 'Преемник прав и обязанностей организации, ранее размещавшей информацию и документы в ЕИС',
            'VHI' => 'Организация, имеющая доступ к информации и документам, не размещаемым на официальном сайте ЕИС',
            'SV' => ' Орган (организация), имеющая доступ к информации о признаках и рисках нарушений',
            'MZ' => 'Орган (организация), имеющая доступ к аналитической информации'
        ];
    }

}