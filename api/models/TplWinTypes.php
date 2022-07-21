<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tpl_win_types".
 *
 * @property int $id ID
 * @property string $title заголовок
 * @property int $sort_position позиция
 * @property int $status статус: 1=вкл, 2=выкл
 */
class TplWinTypes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tpl_win_types';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['sort_position', 'status'], 'integer'],
            [['title'], 'string', 'max' => 45],
            [['title'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'title' => Yii::t('app', 'Title'),
            'sort_position' => Yii::t('app', 'Sort Position'),
            'status' => Yii::t('app', 'Status'),
        ];
    }
}
