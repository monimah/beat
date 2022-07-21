<?php

namespace common\components;

class MyCors
{
    static function loadCors()
    {
        return [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Origin' => ['http://beatme.online', 'https://beatme.online'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 3600,
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Access-Control-Expose-Headers' => ['X-Access-Token', 'X-Platform'],
            ],
        ];
    }
}

?>