<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property int $id ID
 * @property int|null $phone телефон
 * @property string|null $email Email
 * @property string|null $google_id Google ID
 * @property string|null $facebook_app_id Facebook ID
 * @property string|null $vk_id VK ID
 * @property string $username пользователь
 * @property string $password пароль
 * @property string|null $first_name имя
 * @property int|null $gender пол: 1=мужской, 2=женский
 * @property string|null $photo фото
 * @property string|null $about_me о себе
 * @property int|null $country_id ID страна
 * @property string|null $city город
 * @property string|null $device_type Тип устройства
 * @property string|null $fcm_id FCM ID
 * @property int $created_at дата создания
 * @property int $status статус: 1=вкл, 2=выкл,3=блок
 *
 * @property Chats[] $chats
 * @property Chats[] $chats0
 * @property EventComments[] $eventComments
 * @property EventCommentsSecondLevel[] $eventCommentsSecondLevels
 * @property EventParticipation[] $eventParticipations
 * @property EventParticipation[] $eventParticipations0
 * @property EventRating $eventRating
 * @property EventUserVotes[] $eventUserVotes
 * @property EventUserVotes[] $eventUserVotes0
 * @property EventWinners[] $eventWinners
 * @property EventWinners[] $eventWinners0
 * @property Events[] $events
 * @property UserChats[] $userChats
 * @property UserChats[] $userChats0
 * @property TplCountries $country
 */
class Users extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phone', 'gender', 'country_id', 'created_at', 'status'], 'integer'],
            [['username', 'password', 'created_at'], 'required'],
            [['device_type'], 'string'],
            [['email', 'google_id', 'facebook_app_id', 'vk_id', 'password', 'photo'], 'string', 'max' => 100],
            [['username'], 'string', 'max' => 50],
            [['first_name'], 'string', 'max' => 80],
            [['about_me', 'fcm_id'], 'string', 'max' => 255],
            [['city'], 'string', 'max' => 65],
            [['username'], 'unique'],
            [['phone'], 'unique'],
            [['google_id'], 'unique'],
            [['email'], 'unique'],
            [['facebook_app_id'], 'unique'],
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
            'phone' => Yii::t('app', 'Phone'),
            'email' => Yii::t('app', 'Email'),
            'google_id' => Yii::t('app', 'Google ID'),
            'facebook_app_id' => Yii::t('app', 'Facebook App ID'),
            'vk_id' => Yii::t('app', 'Vk ID'),
            'username' => Yii::t('app', 'Username'),
            'password' => Yii::t('app', 'Password'),
            'first_name' => Yii::t('app', 'First Name'),
            'gender' => Yii::t('app', 'Gender'),
            'photo' => Yii::t('app', 'Photo'),
            'about_me' => Yii::t('app', 'About Me'),
            'country_id' => Yii::t('app', 'Country ID'),
            'city' => Yii::t('app', 'City'),
            'device_type' => Yii::t('app', 'Device Type'),
            'fcm_id' => Yii::t('app', 'Fcm ID'),
            'created_at' => Yii::t('app', 'Created At'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    /**
     * Gets query for [[Chats]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChats()
    {
        return $this->hasMany(Chats::className(), ['reciver_id' => 'id']);
    }

    /**
     * Gets query for [[Chats0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChats0()
    {
        return $this->hasMany(Chats::className(), ['sender_id' => 'id']);
    }

    /**
     * Gets query for [[EventComments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventComments()
    {
        return $this->hasMany(EventComments::className(), ['user_id' => 'id']);
    }

    /**
     * Gets query for [[EventCommentsSecondLevels]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventCommentsSecondLevels()
    {
        return $this->hasMany(EventCommentsSecondLevel::className(), ['user_id' => 'id']);
    }

    /**
     * Gets query for [[EventParticipations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventParticipations()
    {
        return $this->hasMany(EventParticipation::className(), ['participant_id' => 'id']);
    }

    /**
     * Gets query for [[EventParticipations0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventParticipations0()
    {
        return $this->hasMany(EventParticipation::className(), ['parent_id' => 'id']);
    }

    /**
     * Gets query for [[EventRating]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventRating()
    {
        return $this->hasOne(EventRating::className(), ['user_id' => 'id']);
    }

    /**
     * Gets query for [[EventUserVotes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventUserVotes()
    {
        return $this->hasMany(EventUserVotes::className(), ['participant_id' => 'id']);
    }

    /**
     * Gets query for [[EventUserVotes0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventUserVotes0()
    {
        return $this->hasMany(EventUserVotes::className(), ['user_id' => 'id']);
    }

    /**
     * Gets query for [[EventWinners]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventWinners()
    {
        return $this->hasMany(EventWinners::className(), ['winner_user_id' => 'id']);
    }

    /**
     * Gets query for [[EventWinners0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventWinners0()
    {
        return $this->hasMany(EventWinners::className(), ['loser_user_id' => 'id']);
    }

    /**
     * Gets query for [[Events]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEvents()
    {
        return $this->hasMany(Events::className(), ['user_id' => 'id']);
    }

    /**
     * Gets query for [[UserChats]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserChats()
    {
        return $this->hasMany(UserChats::className(), ['creator_id' => 'id']);
    }

    /**
     * Gets query for [[UserChats0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserChats0()
    {
        return $this->hasMany(UserChats::className(), ['user_id' => 'id']);
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
