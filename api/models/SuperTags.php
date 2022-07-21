<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "super_tags".
 *
 * @property int $id ID
 * @property string $title заголовок
 * @property int $win_type Тип определения победы
 * @property int $status статус: 1=вкл, 2=выкл
 *
 * @property Events[] $events
 * @property SuperTagsFields[] $superTagsFields
 */
class SuperTags extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'super_tags';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['win_type', 'status'], 'integer'],
            [['title'], 'string', 'max' => 50],
            [['title'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'title' => Yii::t('app', 'Title'),
            'win_type' => Yii::t('app', 'Win Type'),
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
        return $this->hasMany(Events::className(), ['super_tag_id' => 'id']);
    }

    /**
     * Gets query for [[SuperTagsFields]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSuperTagsFields()
    {
        return $this->hasMany(SuperTagsFields::className(), ['super_tag_id' => 'id']);
    }
}
