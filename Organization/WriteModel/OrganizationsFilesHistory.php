<?php

namespace app\models\Organization\WriteModel;

use app\models\Organization\Objects\Entities\OrganizationsFilesHistory as Model;
use DomainException;

class OrganizationsFilesHistory
{

    /**
     * @param $fileName
     */
    public function saveInDb($fileName): void
    {
        $fileInDb = new Model();
        $fileInDb->name = $fileName;
        $fileInDb->date = date('Y-m-d H:i:s');
        $fileInDb->status = Model::STATUS_UPLOADED;
        $fileInDb->save();
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