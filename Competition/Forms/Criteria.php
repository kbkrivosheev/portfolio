<?php
declare(strict_types=1);

namespace app\models\Competition\Forms;

use yii\base\Model;

class Criteria extends Model
{
    public float $contractPrice;
    public float $expense;
    public float $characteristic;
    public float $qualification;


    public function rules(): array
    {
        return [
            [['contractPrice', 'expense', 'characteristic', 'qualification'], 'number'],
        ];
    }
}