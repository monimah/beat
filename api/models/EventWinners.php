<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "event_winners".
 *
 * @property int $id ID записи
 * @property int $event_id ID события
 * @property int $winner_user_id ID победителя
 * @property int $loser_user_id ID проигравшего
 * @property int $event_type 1=public, 2=private
 *
 * @property Events $event
 * @property Users $winnerUser
 * @property Users $loserUser
 */
class EventWinners extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_winners';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['event_id', 'winner_user_id', 'loser_user_id', 'event_type'], 'required'],
            [['event_id', 'winner_user_id', 'loser_user_id', 'event_type'], 'integer'],
            [['event_id'], 'exist', 'skipOnError' => true, 'targetClass' => Events::className(), 'targetAttribute' => ['event_id' => 'id']],
            [['winner_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['winner_user_id' => 'id']],
            [['loser_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['loser_user_id' => 'id']],
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
            'winner_user_id' => Yii::t('app', 'Winner User ID'),
            'loser_user_id' => Yii::t('app', 'Loser User ID'),
            'event_type' => Yii::t('app', 'Event Type'),
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
     * Gets query for [[WinnerUser]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWinnerUser()
    {
        return $this->hasOne(Users::className(), ['id' => 'winner_user_id']);
    }

    /**
     * Gets query for [[LoserUser]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLoserUser()
    {
        return $this->hasOne(Users::className(), ['id' => 'loser_user_id']);
    }
}
