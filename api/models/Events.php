<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "events".
 *
 * @property int $id ID
 * @property int|null $super_tag_id ID супертега
 * @property int $user_id ID пользователя
 * @property int $winType 1=рейтинг, 2=баллы
 * @property int|null $city_id ID города
 * @property string $title заголовок
 * @property int $privacy 1=публичный, 2=приватный
 * @property float|null $result_data Результат пользователя
 * @property int $gender пол: 1=муж. 2=жен.
 * @property int|null $rival_min_age возраст соперника мин.
 * @property int|null $rival_max_age возраст соперника макс.
 * @property string|null $description описание
 * @property int $status 1=автивный,2=завершен
 * @property int $in_voting В голосовании
 * @property int $created_at дата создания
 * @property int $deadline Дедлайн события
 * @property int|null $voting_deadline Дедлайн голосования
 * @property string|null $videoFile видео файл
 * @property string|null $previewFile Превьюв видео файла
 * @property string|null $city Город
 *
 * @property EventComments[] $eventComments
 * @property EventParticipation[] $eventParticipations
 * @property EventTags[] $eventTags
 * @property Tags[] $tags
 * @property EventUserVotes[] $eventUserVotes
 * @property EventWinners[] $eventWinners
 * @property SuperTags $superTag
 * @property Users $user
 * @property TplCities $city0
 * @property EventsFields[] $eventsFields
 * @property SuperTagsFields[] $fields
 */
class Events extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'events';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['super_tag_id', 'user_id', 'winType', 'city_id', 'privacy', 'gender', 'rival_min_age', 'rival_max_age', 'status', 'in_voting', 'created_at', 'deadline', 'voting_deadline'], 'integer'],
            [['user_id', 'winType', 'title', 'created_at', 'deadline'], 'required'],
            [['result_data'], 'number'],
            [['title', 'videoFile', 'previewFile'], 'string', 'max' => 100],
            [['description'], 'string', 'max' => 255],
            [['city'], 'string', 'max' => 55],
            [['super_tag_id'], 'exist', 'skipOnError' => true, 'targetClass' => SuperTags::className(), 'targetAttribute' => ['super_tag_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['city_id'], 'exist', 'skipOnError' => true, 'targetClass' => TplCities::className(), 'targetAttribute' => ['city_id' => 'id']],
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
            'user_id' => Yii::t('app', 'User ID'),
            'winType' => Yii::t('app', 'Win Type'),
            'city_id' => Yii::t('app', 'City ID'),
            'title' => Yii::t('app', 'Title'),
            'privacy' => Yii::t('app', 'Privacy'),
            'result_data' => Yii::t('app', 'Result Data'),
            'gender' => Yii::t('app', 'Gender'),
            'rival_min_age' => Yii::t('app', 'Rival Min Age'),
            'rival_max_age' => Yii::t('app', 'Rival Max Age'),
            'description' => Yii::t('app', 'Description'),
            'status' => Yii::t('app', 'Status'),
            'in_voting' => Yii::t('app', 'In Voting'),
            'created_at' => Yii::t('app', 'Created At'),
            'deadline' => Yii::t('app', 'Deadline'),
            'voting_deadline' => Yii::t('app', 'Voting Deadline'),
            'videoFile' => Yii::t('app', 'Video File'),
            'previewFile' => Yii::t('app', 'Preview File'),
            'city' => Yii::t('app', 'City'),
        ];
    }

    /**
     * Gets query for [[EventComments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventComments()
    {
        return $this->hasMany(EventComments::className(), ['event_id' => 'id']);
    }

    /**
     * Gets query for [[EventParticipations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventParticipations()
    {
        return $this->hasMany(EventParticipation::className(), ['event_id' => 'id']);
    }

    /**
     * Gets query for [[EventTags]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventTags()
    {
        return $this->hasMany(EventTags::className(), ['event_id' => 'id']);
    }

    /**
     * Gets query for [[Tags]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(Tags::className(), ['id' => 'tag_id'])->viaTable('event_tags', ['event_id' => 'id']);
    }

    /**
     * Gets query for [[EventUserVotes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventUserVotes()
    {
        return $this->hasMany(EventUserVotes::className(), ['event_id' => 'id']);
    }

    /**
     * Gets query for [[EventWinners]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventWinners()
    {
        return $this->hasMany(EventWinners::className(), ['event_id' => 'id']);
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
     * Gets query for [[City0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCity0()
    {
        return $this->hasOne(TplCities::className(), ['id' => 'city_id']);
    }

    /**
     * Gets query for [[EventsFields]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEventsFields()
    {
        return $this->hasMany(EventsFields::className(), ['event_id' => 'id']);
    }

    /**
     * Gets query for [[Fields]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFields()
    {
        return $this->hasMany(SuperTagsFields::className(), ['id' => 'field_id'])->viaTable('events_fields', ['event_id' => 'id']);
    }
}
