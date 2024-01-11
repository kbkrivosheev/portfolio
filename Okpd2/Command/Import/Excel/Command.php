<?php
declare(strict_types=1);

namespace app\models\Okpd2\Command\Import\Excel;

use yii\base\Model;
use yii\web\UploadedFile;

final class Command extends Model
{
    public ?UploadedFile $file = null;

    public function rules(): array
    {
        return [
            [['file'], 'required']
        ];
    }
}