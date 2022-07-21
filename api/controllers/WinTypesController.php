<?php

namespace api\controllers;

use app\models\TplWinTypes;
use common\components\PublicApiController;

class WinTypesController extends PublicApiController
{
    public function actionIndex()
    {
        $model = TplWinTypes::find()
            ->select(['id', 'title'])
            ->where(['status' => 1])
            ->orderBy(['sort_position' => SORT_ASC])
            ->all();

        return $model;
    }
}