<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "event_user_votes".
 *
 * @property int $id ID записи
 * @property int $event_id ID события
 * @property int $participant_id ID участника
 * @property int $user_id ID пользователя
 * @property int $createdAt Дата создания
 *
 * @property Events $event
 * @property Users $participant
 * @property Users $user
 */
class EventUserVotes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_user_votes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['event_id', 'participant_id', 'user_id', 'createdAt'], 'required'],
            [['event_id', 'participant_id', 'user_id', 'createdAt'], 'integer'],
            [['event_id', 'participant_id', 'user_id'], 'unique', 'targetAttribute' => ['event_id', 'participant_id', 'user_id']],
            [['event_id'], 'exist', 'skipOnError' => true, 'targetClass' => Events::className(), 'targetAttribute' => ['event_id' => 'id']],
            [['participant_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['participant_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['user_id' => 'id']],
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
            'participant_id' => Yii::t('app', 'Participant ID'),
            'user_id' => Yii::t('app', 'User ID'),
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
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::className(), ['id' => 'user_id']);
    }
}
