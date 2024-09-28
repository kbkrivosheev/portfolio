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
        $excel = new ExcelReader($file, 'A', 'Q', 2);
        if (empty($data = $excel->getData())) {
            throw new DomainException('Нет данных');
        }
        if (!$transaction = $this->connection->beginTransaction()) {
            throw new DomainException('Сбой транзакции');
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

}