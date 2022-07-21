<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "event_participation".
 *
 * @property int $id ID записи
 * @property int $event_id ID события
 * @property int $parent_id ID организатора
 * @property int $participant_id ID участника
 * @property string|null $videoFile Видео файл участника
 * @property string|null $previewFile Превьюв видео файла
 * @property float|null $result_data_participant Результат участника
 * @property int $status 1=заявка на участие, 2=заявка принята, 3=заявка отклонена
 * @property int $createdAt Дата создания
 *
 * @property Events $event
 * @property Users $participant
 * @property Users $parent
 */
class EventParticipation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_participation';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['event_id', 'parent_id', 'participant_id', 'createdAt'], 'required'],
            [['event_id', 'parent_id', 'participant_id', 'status', 'createdAt'], 'integer'],
            [['result_data_participant'], 'number'],
            [['videoFile', 'previewFile'], 'string', 'max' => 100],
            [['event_id'], 'exist', 'skipOnError' => true, 'targetClass' => Events::className(), 'targetAttribute' => ['event_id' => 'id']],
            [['participant_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['participant_id' => 'id']],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['parent_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'event_id' => Yii::t('app', 'Event ID'),
            'parent_id' => Yii::t('app', 'Parent ID'),
            'participant_id' => Yii::t('app', 'Participant ID'),
            'videoFile' => Yii::t('app', 'Video File'),
            'previewFile' => Yii::t('app', 'Preview File'),
            'result_data_participant' => Yii::t('app', 'Result Data Participant'),
            'status' => Yii::t('app', 'Status'),
            'createdAt' => Yii::t('app', 'Created At'),
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
     * Gets query for [[Participant]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParticipant()
    {
        return $this->hasOne(Users::className(), ['id' => 'participant_id']);
    }

    /**
     * Gets query for [[Parent]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(Users::className(), ['id' => 'parent_id']);
    }
}
