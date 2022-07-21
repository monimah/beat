<?php

namespace api\controllers;

use app\models\TplCountries;
use common\components\PublicApiController;

class CountriesController extends PublicApiController
{

    public function actionIndex()
    {
        return TplCountries::find()
            ->select(['id', 'phone_code', 'country_code', 'country_name', 'continent_name'])
            ->where(['status' => 1])->all();
    }
}