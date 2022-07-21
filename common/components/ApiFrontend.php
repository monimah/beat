<?php

namespace common\components;

use yii\filters\AccessControl;
use yii\rest\Controller;
use yii\web\Response;

class ApiFrontend extends Controller
{
    public $enableCsrfValidation = false;
    public $token = false;
    public $platform = 'mobile';

    public function actions()
    {
        $actions = parent::actions();

        $actions['options'] = [
            'class' => 'yii\rest\OptionsAction',
            'collectionOptions' => ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'],
        ];
        return $actions;
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['corsFilter'] = MyCors::loadCors();
        $behaviors['contentNegotiator'] = [
            'class' => 'yii\filters\ContentNegotiator',
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ]
        ];

        $allow = false;

        $this->platform = \Yii::$app->request->headers->get('X-Platform');

        $this->token = JWTChecker::checkFrontendToken();
        if ($this->token) {
            $allow = true;
        }

        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'rules' => [['allow' => $allow]],
            'denyCallback' => function () {
                throw new  \yii\web\UnauthorizedHttpException('Неавторизованный');
            }
        ];

        return $behaviors;
    }
}