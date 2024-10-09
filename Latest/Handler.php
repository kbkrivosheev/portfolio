<?php

namespace app\Features\Parsers\RoszdravnadzorRegistry\MedicalEquipment;

use app\components\ExcelReader;
use app\models\MedicalEquipments;
use DomainException;
use Yii;
use yii\db\Connection;
use yii\db\Exception;

final class Handler
{
    private Connection $connection;

    public function __construct()
    {
        $this->connection = Yii::$app->db;
    }

    public function handle(string $file): void
    {
        $excel = new ExcelReader($file, 'A', 'Q', 1);
        if (empty($data = $excel->getData())) {
            throw new DomainException('Нет данных');
        }
        if (!$transaction = $this->connection->beginTransaction()) {
            throw new DomainException('Сбой транзакции');
        }
        if (array_shift($data) !== $this->getTitles()) {
            throw new DomainException('Не верный порядок колонок в файле');
        }
        $model = new MedicalEquipments();
        try {
            $this->connection->createCommand()->truncateTable($model::tableName())->execute();
            foreach (array_chunk($data, 1000) as $item) {
                $this->connection->createCommand()->batchInsert(
                    $model::tableName(),
                    array_keys($model->getAttributes(null, ['id'])),
                    $item
                )->execute();
            }
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            throw new DomainException($e->getMessage());
        }
    }

    private function getTitles(): array
    {
        return [
            'Уникальный номер реестровой записи',
            'Регистрационный номер медицинского изделия',
            'Дата государственной регистрации медицинского изделия',
            'Срок действия регистрационного удостоверения',
            'Наименование медицинского изделия',
            'Наименование организации - уполномоченного представителя производителя (изготовителя) медицинского изделия',
            'Место нахождения организации - уполномоченного представителя производителя (изготовителя) медицинского изделия',
            'Юридический адрес организации - уполномоченного представителя производителя (изготовителя) медицинского изделия',
            'Наименование организации - производителя медицинского изделия или организации - изготовителя медицинского изделия',
            'Место нахождения организации - производителя медицинского изделия или организации - изготовителя медицинского изделия',
            'Юридический адрес организации - производителя медицинского изделия или организации - изготовителя медицинского изделия',
            'ОКП/ОКПД2',
            'Класс потенциального риска применения медицинского изделия в соответствии с номенклатурной классификацией медицинских изделий, утверждаемой Министерством здравоохранения Российской Федерации',
            'Назначение медицинского изделия, установленное производителем',
            'Вид медицинского изделия в соответствии с номенклатурной классификацией медицинских изделий, утверждаемой Министерством здравоохранения Российской Федерации',
            'Адрес места производства или изготовления медицинского изделия',
            'Сведения о взаимозаменяемых медицинских изделиях'
        ];
    }

}