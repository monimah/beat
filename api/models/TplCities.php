<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tpl_cities".
 *
 * @property int $id ID
 * @property int $country_id ID страны
 * @property string $city_name название города
 * @property int $status статус: 1=вкл, 2=выкл
 *
 * @property Events[] $events
 * @property TplCountries $country
 */
class TplCities extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tpl_cities';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['country_id', 'city_name'], 'required'],
            [['country_id', 'status'], 'integer'],
            [['city_name'], 'string', 'max' => 50],
            [['country_id', 'city_name'], 'unique', 'targetAttribute' => ['country_id', 'city_name']],
            [['country_id'], 'exist', 'skipOnError' => true, 'targetClass' => TplCountries::className(), 'targetAttribute' => ['country_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'country_id' => Yii::t('app', 'Country ID'),
            'city_name' => Yii::t('app', 'City Name'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    /**
     * Gets query for [[Events]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEvents()
    {
        return $this->hasMany(Events::className(), ['city_id' => 'id']);
    }

    /**
     * Gets query for [[Country]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCountry()
    {
        return $this->hasOne(TplCountries::className(), ['id' => 'country_id']);
    }
}
