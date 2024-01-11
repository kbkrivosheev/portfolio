<?php

namespace app\models\Okpd2\Command\GetOkpd2Info;


use yii\base\Model;

class Code extends Model
{
    public ?string $okpd2 = null;
    public ?string $ktru = null;

    public function rules(): array
    {
        return [
            [['okpd2', 'ktru'], 'string'],
        ];
    }


}