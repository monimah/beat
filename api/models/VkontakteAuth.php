<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property string $username
 * @property string $access_token
 * @property string $user_id
 * @property string $email
 * @property string $vk_id
 * @property string $created_at
 * @property int $status 1=вкл, 2=выкл
 */
class VkontakteAuth extends \yii\db\ActiveRecord
{
    const SCENARIO_VKONTAKTE = 'vkontakte';
    /**
     * {@inheritdoc}
     */

    public $access_token;
    public $user_id;

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
            [['username'], 'required'],
            [['access_token', 'user_id'], 'required', 'on' => self::SCENARIO_VKONTAKTE],
            [['username'], 'string', 'max' => 50],
            [['email'], 'string', 'max' => 50],
            [['vk_id'], 'string', 'max' => 100],
            [['email'], 'email'],
            [['vk_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'username' => Yii::t('app', 'Username'),
        ];
    }
}
