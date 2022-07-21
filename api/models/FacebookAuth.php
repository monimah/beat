<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property string $username
 * @property string $access_token
 * @property string $email
 * @property string $facebook_app_id
 * @property string $created_at
 * @property int $status 1=вкл, 2=выкл
 */
class FacebookAuth extends \yii\db\ActiveRecord
{
    const SCENARIO_FACEBOOK = 'facebook';
    /**
     * {@inheritdoc}
     */

    public $access_token;

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
            [['access_token'], 'required', 'on' => self::SCENARIO_FACEBOOK],
            [['username'], 'string', 'max' => 50],
            [['email'], 'string', 'max' => 50],
            [['facebook_app_id'], 'string', 'max' => 100],
            [['email'], 'email'],
            [['facebook_app_id'], 'unique'],
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
