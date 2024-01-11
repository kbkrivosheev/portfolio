<?php

namespace app\models\Okpd2\Objects\Entities;


use app\models\NsiKtru;
use app\models\Okpd2Acts;
use app\models\Okpd2Contracts;
use app\models\Okpd2ToActs;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "okpd2_catalog".
 *
 * @property int $id
 * @property string $code Код ОКПД2
 * @property string $name Название ОКПД2
 * @property string $parent_code Код ОКПД2 родителя
 * @property int $actual Актуальный
 *
 * @property Okpd2Catalog $parentCode
 * @property Okpd2Catalog[] $okpd2Catalogs
 * @property Okpd2ToActs[] $okpd2Acts
 * @property Okpd2Acts[] $acts
 * @property Okpd2Contracts[] $contracts
 * @property NsiKtru[] $ktru
 */
class Okpd2Catalog extends \yii\db\ActiveRecord
{
    public const ACTUAL = 1;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'okpd2_catalog';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'name'], 'required'],
            [['name'], 'string'],
            [['actual'], 'integer'],
            [['code', 'parent_code'], 'string', 'max' => 32],
            [['parent_code'], 'exist', 'skipOnError' => true, 'targetClass' => Okpd2Catalog::className(), 'targetAttribute' => ['parent_code' => 'code']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Код ОКПД2',
            'name' => 'Название ОКПД2',
            'parent_code' => 'Код ОКПД2 родителя',
            'actual' => 'Актуальность',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParentCode()
    {
        return $this->hasOne(Okpd2Catalog::className(), ['code' => 'parent_code']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOkpd2Catalogs()
    {
        return $this->hasMany(Okpd2Catalog::className(), ['parent_code' => 'code']);
    }

    /**
     * @return ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getActs(): ActiveQuery
    {
        return $this->hasMany(Okpd2Acts::class, ['id' => 'act_id'])->via('okpd2Acts');
    }


    /**
     * @return ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getOkpd2Acts(): ActiveQuery
    {
        return $this->hasMany(Okpd2ToActs::class, ['okpd2_id' => 'id']);
    }

    public function getContracts(): ActiveQuery
    {
        return $this->hasMany(Okpd2Contracts::class, ['id' => 'contract_id'])
            ->viaTable('okpd2_to_contracts', ['okpd2_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getKtru(): ActiveQuery
    {
        return $this->hasMany(NsiKtru::class, ['id' => 'ktru_id'])
            ->viaTable('okpd2_to_ktru', ['okpd2_id' => 'id']);
    }
}
