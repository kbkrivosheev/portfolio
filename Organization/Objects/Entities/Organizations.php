<?php

namespace app\models\Organization\Objects\Entities;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "organizations".
 *
 * @property int $id
 * @property string $full_name Полное наименование
 * @property string $short_name Сокращенное наименование
 * @property string $inn ИНН
 * @property string $kpp КПП
 * @property string $ogrn ОГРН
 * @property string $oktmo ОКТМО
 * @property string $address ОКТМО
 * @property string $phone Номера телефонов
 * @property string $region Регион
 * @property int $status Статус
 * @property string $fax Номер факса
 * @property string $email Email
 * @property string $website Адрес сайта
 * @property string role Полномочия организации
 *
 */
class Organizations extends ActiveRecord
{
    const STATUS_NOTACTIVE = 0;
    const STATUS_ACTIVE = 1;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'organizations';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['full_name'], 'required'],
            [['full_name', 'address'], 'string'],
            [['status'], 'integer'],
            [['inn', 'kpp', 'ogrn', 'oktmo', 'phone', 'region', 'fax', 'email'], 'string', 'max' => 64],
            [['role', 'website'], 'string', 'max' => 255],
            [['full_name_hash'], 'string', 'max' => 32],
            [['short_name'], 'string', 'max' => 512],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'full_name' => 'Полное наименование',
            'inn' => 'ИНН',
            'kpp' => 'КПП',
            'ogrn' => 'ОГРН',
            'oktmo' => 'ОКТМО',
            'short_name' => 'Сокращенное наименование',
            'address' => 'Адрес',
            'phone' => 'Телефон',
            'fax' => 'Факс',
            'email' => 'Email',
            'website' => 'Адрес сайта',
            'role' => 'Полномочия',
            'region' => 'Регион',
            'full_name_hash' => 'Hash названия',
            'status' => 'Статус',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_NOTACTIVE => 'Ликвидированное',
            self::STATUS_ACTIVE => 'Действующее',
        ];
    }

}
