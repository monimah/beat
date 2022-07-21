<?php

namespace common\components;

use yii\rest\Controller;
use yii\web\Response;

class PublicApiController extends Controller
{
    public $enableCsrfValidation = false;
    public $platform = 'mobile';

    public function actions()
    {
        $actions = parent::actions();
        $actions['options'] = [
            'class' => 'yii\rest\OptionsAction',
            'collectionOptions' => ['GET', 'POST', 'PUT', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'POST', 'PUT', 'PATCH', 'HEAD', 'OPTIONS'],
        ];
        return $actions;
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator'] = [
            'class' => 'yii\filters\ContentNegotiator',
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ]
        ];
        $behaviors['corsFilter'] = MyCors::loadCors();

        $this->platform = \Yii::$app->request->headers->get('X-Platform');

        return $behaviors;

    }


}