<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tpl_countries".
 *
 * @property int $id
 * @property int $phone_code
 * @property string $country_code
 * @property string $country_name
 * @property string|null $alpha_3
 * @property string|null $continent_code
 * @property string|null $continent_name
 * @property int $status статус: 1=вкл, 2=выкл
 *
 * @property TplCities[] $tplCities
 * @property Users[] $users
 */
class TplCountries extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tpl_countries';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phone_code', 'country_code', 'country_name'], 'required'],
            [['phone_code', 'status'], 'integer'],
            [['country_code', 'continent_code'], 'string', 'max' => 2],
            [['country_name'], 'string', 'max' => 80],
            [['alpha_3'], 'string', 'max' => 3],
            [['continent_name'], 'string', 'max' => 30],
            [['country_code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'phone_code' => Yii::t('app', 'Phone Code'),
            'country_code' => Yii::t('app', 'Country Code'),
            'country_name' => Yii::t('app', 'Country Name'),
            'alpha_3' => Yii::t('app', 'Alpha 3'),
            'continent_code' => Yii::t('app', 'Continent Code'),
            'continent_name' => Yii::t('app', 'Continent Name'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    /**
     * Gets query for [[TplCities]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTplCities()
    {
        return $this->hasMany(TplCities::className(), ['country_id' => 'id']);
    }

    /**
     * Gets query for [[Users]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(Users::className(), ['country_id' => 'id']);
    }
}
