<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "super_tags_fields".
 *
 * @property int $id ID
 * @property int $super_tag_id
 * @property string $title заголовок
 * @property string $keyboardType тип клавиатуры
 * @property int $minLenght мин. символ
 * @property int $maxLenght макс. символ
 * @property int $status статус: 1=вкл, 2=выкл
 *
 * @property EventsFields[] $eventsFields
 * @property Events[] $events
 * @property SuperTags $superTag
 */
class SuperTagsFields extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'super_tags_fields';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['super_tag_id', 'title', 'keyboardType', 'minLenght', 'maxLenght'], 'required'],
            [['super_tag_id', 'minLenght', 'maxLenght', 'status'], 'integer'],
            [['keyboardType'], 'string'],
            [['title'], 'string', 'max' => 50],
            [['super_tag_id'], 'exist', 'skipOnError' => true, 'targetClass' => SuperTags::className(), 'targetAttribute' => ['super_tag_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'super_tag_id' => Yii::t('app', 'Super Tag ID'),
            'title' => Yii::t('app', 'Title'),
            'keyboardType' => Yii::t('app', 'Keyboard Type'),
            'minLenght' => Yii::t('app', 'Min Lenght'),
            'maxLenght' => Yii::t('app', 'Max Lenght'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    /**
     * Gets query for [[EventsFields]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventsFields()
    {
        return $this->hasMany(EventsFields::className(), ['field_id' => 'id']);
    }

    /**
     * Gets query for [[Events]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEvents()
    {
        return $this->hasMany(Events::className(), ['id' => 'event_id'])->viaTable('events_fields', ['field_id' => 'id']);
    }

    /**
     * Gets query for [[SuperTag]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSuperTag()
    {
        return $this->hasOne(SuperTags::className(), ['id' => 'super_tag_id']);
    }
}
