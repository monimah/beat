<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "events_fields".
 *
 * @property int $event_id ID состезания
 * @property int $field_id ID поля супертега
 * @property string $value значение
 *
 * @property Events $event
 * @property SuperTagsFields $field
 */
class EventsFields extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'events_fields';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['event_id', 'field_id', 'value'], 'required'],
            [['event_id', 'field_id'], 'integer'],
            [['value'], 'string', 'max' => 100],
            [['event_id', 'field_id'], 'unique', 'targetAttribute' => ['event_id', 'field_id']],
            [['event_id'], 'exist', 'skipOnError' => true, 'targetClass' => Events::className(), 'targetAttribute' => ['event_id' => 'id']],
            [['field_id'], 'exist', 'skipOnError' => true, 'targetClass' => SuperTagsFields::className(), 'targetAttribute' => ['field_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'event_id' => Yii::t('app', 'Event ID'),
            'field_id' => Yii::t('app', 'Field ID'),
            'value' => Yii::t('app', 'Value'),
        ];
    }

    /**
     * Gets query for [[Event]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEvent()
    {
        return $this->hasOne(Events::className(), ['id' => 'event_id']);
    }

    /**
     * Gets query for [[Field]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getField()
    {
        return $this->hasOne(SuperTagsFields::className(), ['id' => 'field_id']);
    }
}
