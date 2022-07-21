<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "admin_users".
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $image
 * @property string|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property int $status 1=Вкл, 2=Выкл
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class AdminUsers extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'admin_users';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'email', 'password'], 'required'],
            [['email_verified_at', 'created_at', 'updated_at'], 'safe'],
            [['status'], 'integer'],
            [['name', 'email', 'image', 'password'], 'string', 'max' => 255],
            [['remember_token'], 'string', 'max' => 100],
            [['email'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'email' => Yii::t('app', 'Email'),
            'image' => Yii::t('app', 'Image'),
            'email_verified_at' => Yii::t('app', 'Email Verified At'),
            'password' => Yii::t('app', 'Password'),
            'remember_token' => Yii::t('app', 'Remember Token'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
