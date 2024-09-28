<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%medical_equipments}}`.
 */
class m240924_130801_create_medical_equipments_table extends Migration
{
    private string $tableName = '{{%medical_equipments}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable($this->tableName, [
            'id' => $this->primaryKey(),
            'registry_records_number' => $this->integer()->comment('Уникальный номер реестровой записи'),
            'registry_number' => $this->string(32)->comment('Регистрационный номер медицинского изделия'),
            'registration_date' => $this->string(10)->comment('Дата государственной регистрации медицинского изделия'),
            'date_end' => $this->string(10)->null()->comment('Срок действия регистрационного удостоверения'),
            'name' => $this->text()->comment('Наименование медицинского изделия'),
            'dealer' => $this->string(384)->null()->comment('Наименование организации - уполномоченного ' .
                ' представителя производителя (изготовителя) медицинского изделия'),
            'dealer_address' => $this->string(384)->null()->comment('Место нахождения организации - ' .
                'уполномоченного представителя производителя (изготовителя) медицинского изделия'),
            'dealer_law_address' => $this->string(384)->null()->comment('Юридический адрес организации ' .
                ' - уполномоченного представителя производителя (изготовителя) медицинского изделия'),
            'manufacturer' => $this->string(384)->null()->comment('Наименование организации - производителя ' .
                ' медицинского изделия или организации - изготовителя медицинского изделия'),
            'manufacturer_address' => $this->string(512)->null()->comment('Место нахождения организации - ' .
                'производителя (изготовителя) медицинского изделия'),
            'manufacturer_law_address' => $this->string(512)->null()->comment('Юридический адрес организации ' .
                ' - производителя (изготовителя) медицинского изделия'),
            'okpd2' => $this->string(24)->comment('ОКП/ОКПД2'),
            'risk_class' => $this->string(8)->comment('Класс потенциального риска применения ' .
                ' медицинского изделия в соответствии с номенклатурной классификацией медицинских изделий, ' .
                'утверждаемой Министерством здравоохранения Российской Федерации'),
            'purpose' => $this->string(32)->null()->comment('Назначение медицинского изделия, ' .
                'установленное производителем'),
            'nkmi' => $this->string(32)->comment('Вид медицинского изделия в соответствии с ' .
                ' номенклатурной классификацией медицинских изделий (НКМИ), утверждаемой Министерством здравоохранения '
                . 'Российской Федерации'),
            'production_address' => $this->text()->null()->comment('Адрес места производства или '.
                'изготовления медицинского изделия'),
            'interchangeable_info' => $this->string(128)->null()->comment('Сведения о взаимозаменяемых '
                .' медицинских изделиях'),
        ]);
        $this->createIndex('idx-medical_equipments-registry_number-nkmi', $this->tableName, ['registry_number', 'nkmi']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable($this->tableName);
    }
}
