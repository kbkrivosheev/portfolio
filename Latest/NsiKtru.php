<?php

namespace app\models;

use app\models\Okpd2\Objects\Entities\Okpd2Catalog;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "nsi_ktru".
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $unit
 * @property string $description
 * @property string $okpd2_code
 * @property string $okpd2_name
 * @property string $classifiers_code
 * @property string $classifiers_name
 * @property string $classifiers_description
 * @property string $application_date_start
 * @property string $application_date_end
 * @property string $inclusion_date
 * @property string $cancel_date
 * @property string $publish_date
 * @property string $parent_code
 * @property null|array $characteristics
 * @property int $is_template
 * @property bool $no_new_features
 * @property int $version
 * @property int $actual
 * @property null|Units $units
 * @property Okpd2Catalog[] $okpd
 */
class NsiKtru extends ActiveRecord
{
    public const ACTUAL = 1;
    public const NOT_ACTUAL = 0;
    public const NOT_TEMPLATE = 0;

    public int $children_count = 0;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'nsi_ktru';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {

        return [
            [['name', 'classifiers_description', 'description', 'parent_code'], 'string'],
            [['version', 'actual', 'is_template', 'no_new_features'], 'integer'],
            [['application_date_start', 'characteristics', 'publish_date', 'application_date_end', 'cancel_date', 'inclusion_date'], 'safe'],
            [['code', 'unit', 'okpd2_code', 'classifiers_code', 'classifiers_name'], 'string', 'max' => 255],
            [['okpd2_name'], 'string', 'max' => 512],
            [['parent_code'], 'default', 'value' => null],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'name' => 'Name',
            'unit' => 'Unit',
            'version' => 'Version',
            'actual' => 'Actual',
            'okpd2_code' => 'OKPD2 Code',
            'okpd2_name' => 'OKPD2 Name',
            'application_date_start' => 'Application date start',
            'classifiers_code' => 'Classifiers code',
            'classifiers_name' => 'Classifiers name',
            'classifiers_description' => 'Classifiers description',
            'description' => 'Description',
            'characteristics' => 'Characteristics',
            'is_template' => 'Is template',
            'no_new_features' => 'No new features',

        ];
    }

    /**
     * @return ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getOkpd(): ActiveQuery
    {
        return $this->hasMany(Okpd2Catalog::class, ['id' => 'okpd2_id'])
            ->viaTable('okpd2_to_ktru', ['ktru_id' => 'id']);
    }
    public function getUnits(): ActiveQuery
    {
        return $this->hasOne(Units::class, ['full_name' => 'unit']);
    }
    public function getChildren(): ActiveQuery
    {
        return $this->hasMany(self::class, ['parent_code' => 'code'])->alias('children');
    }

    public function getMedicalEquipments(): ActiveQuery
    {
        return $this->hasMany(MedicalEquipments::class, ['nkmi' => 'classifiers_code']);
    }
}
