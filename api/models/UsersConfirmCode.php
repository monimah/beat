<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "users_confirm_code".
 *
 * @property int $phone телефон
 * @property string $confirm_code код подтверждения
 * @property int $created_at дата создания
 * @property int $incorrect_code_count кол-во неправильного кода
 * @property int $block_date_to заблокировать до
 */
class UsersConfirmCode extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'users_confirm_code';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phone', 'confirm_code', 'created_at'], 'required'],
            [['phone', 'created_at', 'incorrect_code_count', 'block_date_to'], 'integer'],
            [['confirm_code'], 'string', 'max' => 100],
            [['phone'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'phone' => Yii::t('app', 'Phone'),
            'confirm_code' => Yii::t('app', 'Confirm Code'),
            'created_at' => Yii::t('app', 'Created At'),
            'incorrect_code_count' => Yii::t('app', 'Incorrect Code Count'),
            'block_date_to' => Yii::t('app', 'Block Date To'),
        ];
    }
}
