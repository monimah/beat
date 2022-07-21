<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property string $username
 * @property int $phone
 * @property int $country_code
 * @property int $confirm_code
 * @property string $password
 * @property string|null $device_type Тип устройства
 * @property string|null $fcm_id FCM ID
 * @property int $status 1=вкл, 2=выкл
 */
class Auth extends \yii\db\ActiveRecord
{
    const SCENARIO_CHECK = 'check';
    const SCENARIO_CONFIRM_CODE = 'confirmCode';
    const SCENARIO_REGISTER = 'register';
    const SCENARIO_LOGIN = 'login';
    const SCENARIO_RESTORE_PASSWORD = 'restorePassword';

    public $userData = false;
    public $confirm_code = '';
    public $country_code = '';

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
            [['phone'], 'required', 'on' => self::SCENARIO_CHECK],
            [['phone', 'confirm_code'], 'required', 'on' => self::SCENARIO_CONFIRM_CODE],
            [['phone', 'password', 'device_type', 'fcm_id'], 'required', 'on' => self::SCENARIO_LOGIN],
            [['phone', 'password', 'username', 'country_code', 'confirm_code', 'device_type', 'fcm_id'], 'required', 'on' => self::SCENARIO_REGISTER],
            [['phone', 'password', 'confirm_code'], 'required', 'on' => self::SCENARIO_RESTORE_PASSWORD],
            [['phone', 'confirm_code'], 'integer'],
            [['phone'], 'string', 'min' => 9],
            [['phone'], 'string', 'max' => 15],
            [['fcm_id'], 'string', 'max' => 255],
            [['device_type'], 'string'],
            [['username'], 'string', 'max' => 50],
            [['confirm_code'], 'string', 'min' => 4],
            [['confirm_code'], 'string', 'max' => 4],
            [['password'], 'string', 'min' => 8],
            [['password'], 'string', 'max' => 85],
            [['country_code'], 'string', 'min' => 2],
            [['country_code'], 'string', 'max' => 4],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'phone' => Yii::t('app', 'Phone'),
            'password' => Yii::t('app', 'Password'),
            'confirm_code' => Yii::t('app', 'Confirm code'),
            'country_code' => Yii::t('app', 'Phone code'),
            'username' => Yii::t('app', 'Username'),
        ];
    }

    public function getUser()
    {
        $this->userData = $this::find()->where(['phone' => $this->phone])->asArray()->one();
        return $this->userData;
    }

    public function login()
    {
        if ($this->userData) {
            if ($this->password !== $this->userData['password']) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }
}
