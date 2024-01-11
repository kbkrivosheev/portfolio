<?php

namespace app\models\Organization\WriteModel;

use app\models\Organization\Objects\Entities\Organizations as Model;


class Organizations
{
    public function findByFullNameHash(string $fullNameHash)
    {
        return Model::find()->where(['full_name_hash' => $fullNameHash])->one();
    }

}