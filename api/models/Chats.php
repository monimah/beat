<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "chats".
 *
 * @property int $id
 * @property int $sender_id
 * @property int $reciver_id
 * @property string $message
 * @property int $delivered
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property string $chat_id
 *
 * @property Users $reciver
 * @property Users $sender
 */
class Chats extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chats';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sender_id', 'reciver_id', 'message', 'chat_id'], 'required'],
            [['sender_id', 'reciver_id', 'delivered', 'created_at', 'updated_at'], 'integer'],
            [['message'], 'string'],
            [['chat_id'], 'string', 'max' => 100],
            [['reciver_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['reciver_id' => 'id']],
            [['sender_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['sender_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'sender_id' => Yii::t('app', 'Sender ID'),
            'reciver_id' => Yii::t('app', 'Reciver ID'),
            'message' => Yii::t('app', 'Message'),
            'delivered' => Yii::t('app', 'Delivered'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'chat_id' => Yii::t('app', 'Chat ID'),
        ];
    }

    /**
     * Gets query for [[Reciver]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getReciver()
    {
        return $this->hasOne(Users::className(), ['id' => 'reciver_id']);
    }

    /**
     * Gets query for [[Sender]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSender()
    {
        return $this->hasOne(Users::className(), ['id' => 'sender_id']);
    }
}
