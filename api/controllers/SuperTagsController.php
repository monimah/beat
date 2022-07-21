<?php

namespace api\controllers;

use app\models\SuperTags;
use app\models\SuperTagsFields;
use common\components\PublicApiController;

class SuperTagsController extends PublicApiController
{
    public function actionIndex()
    {
        $q = \Yii::$app->request->post('q');

        if (!isset($q))
            throw new \yii\web\MethodNotAllowedHttpException();


        if (strlen(trim($q)) > 0) {
            $model = SuperTags::find()->andWhere(new \yii\db\Expression('title LIKE :param', [':param' => '%' . $q . '%']))
                ->limit(10)
                ->all();
        } else {
            $model = SuperTags::find()
                ->orderBy(['id' => SORT_DESC])
                ->all();
        }

        return $model;
    }

    public function actionFields()
    {
        $id = \Yii::$app->request->post('id');

        if (!isset($id))
            throw new \yii\web\MethodNotAllowedHttpException();

        $model = SuperTagsFields::find()
            ->select(['id', 'title', 'keyboardType', 'minLenght', 'maxLenght'])
            ->where(['super_tag_id' => (int)$id, 'status' => 1])->all();

        return $model;
    }
}