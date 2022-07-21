<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property string $username
 * @property string $id_token
 * @property string $email
 * @property string $google_id
 * @property string $created_at
 * @property int $status 1=вкл, 2=выкл
 */
class SocialAuth extends \yii\db\ActiveRecord
{
    const SCENARIO_GOOGLE = 'google';
    /**
     * {@inheritdoc}
     */

    public $id_token;

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
            [['id_token'], 'required', 'on' => self::SCENARIO_GOOGLE],
            [['username'], 'string', 'max' => 50],
            [['email'], 'string', 'max' => 50],
            [['google_id'], 'string', 'max' => 100],
            [['email'], 'email'],
            [['google_id'], 'unique'],
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
