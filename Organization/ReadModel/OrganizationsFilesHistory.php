<?php
declare(strict_types=1);

namespace app\models\Organization\ReadModel;
use app\models\Organization\Objects\Entities\OrganizationsFilesHistory as Model;

class OrganizationsFilesHistory
{
    /**
     * @return mixed|string
     */
    public function getLastName()
    {
        return  Model::find()->select('name')->orderBy(['id' => SORT_DESC])->scalar();
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

}