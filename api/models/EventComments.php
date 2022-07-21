<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "event_comments".
 *
 * @property int $id ID записи
 * @property int $event_id ID события
 * @property int $user_id ID пользователя
 * @property string $message Комментарий
 * @property int $createdAt Дата создания
 *
 * @property Events $event
 * @property Users $user
 * @property EventCommentsSecondLevel[] $eventCommentsSecondLevels
 */
class EventComments extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_comments';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['event_id', 'user_id', 'message', 'createdAt'], 'required'],
            [['event_id', 'user_id', 'createdAt'], 'integer'],
            [['message'], 'string', 'max' => 255],
            [['event_id'], 'exist', 'skipOnError' => true, 'targetClass' => Events::className(), 'targetAttribute' => ['event_id' => 'id']],
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
            'user_id' => Yii::t('app', 'User ID'),
            'message' => Yii::t('app', 'Message'),
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
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::className(), ['id' => 'user_id']);
    }

    /**
     * Gets query for [[EventCommentsSecondLevels]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventCommentsSecondLevels()
    {
        return $this->hasMany(EventCommentsSecondLevel::className(), ['comment_id' => 'id']);
    }
}
