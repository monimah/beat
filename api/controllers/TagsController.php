<?php

namespace api\controllers;

use app\models\Tags;
use common\components\PublicApiController;

class TagsController extends PublicApiController
{
    public function actionIndex()
    {
        $q = \Yii::$app->request->post('q');

        if (strlen(trim($q)) > 0) {
            $model = Tags::find()->andWhere(new \yii\db\Expression('title LIKE :param', [':param' => '%' . $q . '%']))
                ->limit(10)
                ->all();
        } else {
            $model = Tags::find()
                ->limit(10)
                ->orderBy(['id' => SORT_DESC])
                ->all();
        }

        return $model;
    }
}