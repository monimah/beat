<?php

namespace common\components;

use \Firebase\JWT\JWT;
use Yii;

class JWTChecker
{
    CONST FRONTEND_TOKEN = 'kG9PM6jDphbvTmgJXCt4rq3Z';
    CONST BACKNED_TOKEN = 'Mm32ktB4DqhGcJ9ruNZxezLQ';
    CONST ISS = 'http://beatme.online';
    CONST AUD = 'http://beatme.online';

    public static function checkFrontendToken($expired = 86400 * 30)
    {
        return self::frontendToken($expired, self::FRONTEND_TOKEN);
    }

    public static function checkBackendToken($expired = 86400 * 30)
    {
        return self::backendToken($expired, self::BACKNED_TOKEN);
    }

    private static function frontendToken($expired, $secretKey)
    {
        try {
            $jwt = Yii::$app->request->headers->get('Authorization');
            $token = JWT::decode($jwt, $secretKey, array('HS256'));
            $payload = array(
                "iss" => self::ISS,
                "aud" => self::AUD,
                "id" => $token->id,
                "exp" => time() + $expired,
                "iat" => time(),
                "nbf" => time()
            );
            $jwt = JWT::encode($payload, $secretKey);
            Yii::$app->response->getHeaders()->set('X-Access-Token', $jwt);
            return $token;
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }

    private static function backendToken($expired, $secretKey)
    {
        try {
            $jwt = Yii::$app->request->headers->get('Authorization');
            $token = JWT::decode($jwt, $secretKey, array('HS256'));
            $payload = array(
                "iss" => self::ISS,
                "aud" => self::AUD,
                "id" => $token->id,
                "role_id" => $token->role_id,
                "exp" => time() + $expired,
                "iat" => time(),
                "nbf" => time()
            );
            $jwt = JWT::encode($payload, $secretKey);
            Yii::$app->response->getHeaders()->set('X-Access-Token', $jwt);
            return $token;
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }

}