<?php

declare(strict_types=1);

namespace app\Features\PurposePurchase\Export\Excel\Forms;

use yii\base\Model;

class Unit extends Model
{
    public ?int $id=null;
    public ?string $code= null;
    public ?string $name= null;


    public function rules(): array
    {
        return [
            [['id'], 'integer'],
            [['name' ], 'string'],
            [['code'], 'string', 'max'=>4],
        ];
    }

}