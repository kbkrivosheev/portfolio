<?php
declare(strict_types=1);

namespace app\models\Okpd2\Objects\Entities;

/**
 * This is the model class for table "okpd_files_history".
 *
 * @property int $id
 * @property string $name Название
 * @property string $date Дата
 * @property int $status Статус
 */
class OkpdFilesHistory extends \yii\db\ActiveRecord
{
    public const STATUS_UPLOADED = 0;
    public const STATUS_SUCCESS = 1;
    public const STATUS_ERROR = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'okpd_files_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['date'], 'safe'],
            [['status'], 'integer'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'date' => 'Дата',
            'status' => 'Статус',
        ];
    }

    public function changeStatusSuccess(): self
    {
        $this->status = self::STATUS_SUCCESS;
        return $this;
    }


    public function changeStatusError(): self
    {
        $this->status = self::STATUS_ERROR;
        return $this;
    }
}
