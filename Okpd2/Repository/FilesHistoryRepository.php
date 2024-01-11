<?php

declare(strict_types=1);

namespace app\models\Okpd2\Repository;

use app\models\Okpd2\Objects\Entities\OkpdFilesHistory as Model;
use DomainException;

class FilesHistoryRepository
{

    /**
     * @return mixed|string
     */
    public function getLastName()
    {
        return Model::find()->select('name')->orderBy(['id' => SORT_DESC])->scalar();
    }

    /**
     * @param $fileName
     * @return bool
     */
    public function checkFileExistence($fileName): bool
    {
        return (bool)Model::find()->select('id')
            ->where(['name' => $fileName])
            ->scalar();
    }


    public function findUploaded()
    {
        return Model::find()->where(['status' => Model::STATUS_UPLOADED])->orderBy(['id' => SORT_ASC])->one();
    }

    public function save(Model $filesHistory)
    {
        if ($filesHistory->save(false) === false) {
            throw new DomainException('Saving error.');
        }
    }
}