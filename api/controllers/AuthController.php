<?php

namespace api\controllers;

use app\models\Auth;
use app\models\EventRating;
use app\models\FacebookAuth;
use app\models\VkontakteAuth;
use app\models\SocialAuth;
use app\models\TplCountries;
use app\models\Users;
use app\models\UsersConfirmCode;
use common\components\JWTChecker;
use common\components\PublicApiController;
use common\components\SMSC;
use Firebase\JWT\JWT;
use Yii;

class AuthController extends PublicApiController
{
    private $passwordKey = 'wvjR8NTaL3hGbuvW';

    public function actionCheck()
    {
        $model = new Auth();

        $model->scenario = Auth::SCENARIO_CHECK;

        $model->load(Yii::$app->request->post(), '');

        if ($model->validate()) {
            $user = $model->getUser();
            if ($user) {
                return ['route' => 'login'];

            } else {
                $usersConfirmCode = UsersConfirmCode::findOne(['phone' => $model->phone]);

                if ($usersConfirmCode) {
                    if ($usersConfirmCode->block_date_to < time()) {
                        $usersConfirmCode->confirm_code = (string)$this->randomConfirmCode();
                        $usersConfirmCode->incorrect_code_count = 0;
                        $usersConfirmCode->block_date_to = time() + 300;
                        if (!$usersConfirmCode->save()) {
                            return $usersConfirmCode;
                        } else {
                            $this->sendSms($usersConfirmCode->phone, $usersConfirmCode->confirm_code);
                        }
                    }
                } else {
                    $usersConfirmCode = new UsersConfirmCode();
                    $usersConfirmCode->phone = $model->phone;
                    $usersConfirmCode->confirm_code = (string)$this->randomConfirmCode();
                    $usersConfirmCode->created_at = time();
                    $usersConfirmCode->incorrect_code_count = 0;
                    $usersConfirmCode->block_date_to = time() + 300;
                    if (!$usersConfirmCode->save()) {
                        return $usersConfirmCode;
                    } else {
                        $this->sendSms($usersConfirmCode->phone, $usersConfirmCode->confirm_code);
                    }
                }
                return ['route' => 'register'];
            }
        } else {
            return $model;
        }
    }

    public function actionConfirmCode()
    {
        $model = new Auth();

        $model->scenario = Auth::SCENARIO_CONFIRM_CODE;

        $model->load(Yii::$app->request->post(), '');

        if ($model->validate()) {
            $usersConfirmCode = UsersConfirmCode::findOne(['phone' => $model->phone]);
            if ($usersConfirmCode) {
                $try_limit = 4;
                if ($usersConfirmCode->incorrect_code_count <= $try_limit) {
                    if ($usersConfirmCode->confirm_code === $model->confirm_code) {

                        if ($usersConfirmCode->block_date_to > time()) {
                            Yii::$app->response->statusCode = 204;
                        } else {
                            throw new \yii\web\ForbiddenHttpException(Yii::t('app', 'Confirmation code is outdated. Please try again later'));
                        }

                    } else {
                        $usersConfirmCode->incorrect_code_count++;
                        $usersConfirmCode->block_date_to = time() + 300;
                        $usersConfirmCode->save();
                        throw new \yii\web\UnprocessableEntityHttpException(Yii::t('app', 'Incorrect confirm code, ' . ($try_limit + 1 - $usersConfirmCode->incorrect_code_count) . ' attempts left'));
                    }
                } else {
                    throw new \yii\web\ForbiddenHttpException(Yii::t('app', 'Confirmation code is outdated. Please try again later'));
                }

            } else {
                throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Not found'));
            }
        } else {
            return $model;
        }
    }

    public function actionRegister()
    {
        $model = new Auth();

        $model->scenario = Auth::SCENARIO_REGISTER;

        if ($model->load(Yii::$app->request->post(), '')) {
            if ($model->validate()) {
                $this->actionConfirmCode();
                $user = new Users();
                $country = TplCountries::findOne(['country_code' => $model->country_code]);
                if ($country) {
                    $user->phone = $model->phone;
                    $user->country_id = $country->id;
                    $user->password = hash_hmac("sha256", $model->password, $this->passwordKey, false);
                    $user->username = $model->username;
                    $user->device_type = $model->device_type;
                    $user->fcm_id = $model->fcm_id;
                    $user->created_at = time();
                    if ($user->save()) {
                        Yii::$app->response->statusCode = 200;
                        $this->deletePhoneFromConfirmCode($user->phone);

                        $user_rating_store = new EventRating();
                        $user_rating_store->user_id = $user->id;
                        $user_rating_store->count = 0;
                        $user_rating_store->save();

                        unset($user->password);
                    }
                    return $user;

                } else {
                    throw new \yii\web\ForbiddenHttpException(Yii::t('app', 'Country code incorrect'));
                }
            }
        } else {
            throw new  \yii\web\ForbiddenHttpException();
        }

        return $model;
    }

    public function actionRestorePassword()
    {
        $model = new Auth();
        $model->scenario = Auth::SCENARIO_CHECK;

        if ($model->load(Yii::$app->request->post(), '')) {
            $user = $model->getUser();
            if (!$user) {
                throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Account does not exists'));
            }

            $usersConfirmCode = UsersConfirmCode::findOne(['phone' => $model->phone]);

            if ($usersConfirmCode) {
                if ($usersConfirmCode->block_date_to < time()) {
                    $usersConfirmCode->confirm_code = (string)$this->randomConfirmCode();
                    $usersConfirmCode->incorrect_code_count = 0;
                    $usersConfirmCode->block_date_to = time() + 300;
                    if (!$usersConfirmCode->save()) {
                        return $usersConfirmCode;
                    } else {
                        $this->sendSms($usersConfirmCode->phone, $usersConfirmCode->confirm_code);
                        Yii::$app->response->statusCode = 204;
                        return;
                    }
                }
            } else {
                $usersConfirmCode = new UsersConfirmCode();
                $usersConfirmCode->phone = $model->phone;
                $usersConfirmCode->confirm_code = (string)$this->randomConfirmCode();
                $usersConfirmCode->created_at = time();
                $usersConfirmCode->incorrect_code_count = 0;
                $usersConfirmCode->block_date_to = time() + 300;
                if (!$usersConfirmCode->save()) {
                    return $usersConfirmCode;
                } else {
                    $this->sendSms($usersConfirmCode->phone, $usersConfirmCode->confirm_code);
                    Yii::$app->response->statusCode = 204;
                    return;
                }
            }

        } else {
            throw new  \yii\web\ForbiddenHttpException();
        }
    }

    public function actionNewPassword()
    {
        $model = new Auth();
        $model->scenario = Auth::SCENARIO_RESTORE_PASSWORD;

        if ($model->load(Yii::$app->request->post(), '')) {
            if ($model->validate()) {
                $this->actionConfirmCode();
                $user = Users::findOne(['phone' => $model->phone]);
                if (!$user) {
                    throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Account does not exists'));
                }

                $user->password = hash_hmac("sha256", $model->password, $this->passwordKey, false);
                if ($user->save()) {
                    Yii::$app->response->statusCode = 204;
                    $this->deletePhoneFromConfirmCode($user->phone);
                    return;
                }
                return $user;
            }else{
                return $model;
            }
        } else {
            throw new  \yii\web\ForbiddenHttpException();
        }
    }

    private function generateToken($user)
    {
        $payload = array(
            "iss" => JWTChecker::ISS,
            "aud" => JWTChecker::AUD,
            "exp" => time() + (86400 * 30 * 12),
            "id" => $user['id'],
            "iat" => time(),
            "nbf" => time()
        );

        $jwt = JWT::encode($payload, JWTChecker::FRONTEND_TOKEN);
        $user['token'] = $jwt;
        Yii::$app->response->getHeaders()->set('X-Access-Token', $jwt);
        unset($user['password']);
        unset($user['google_id']);
        unset($user['facebook_app_id']);
        unset($user['vk_id']);
        unset($user['status']);
        return $user;
    }

    function randomConfirmCode()
    {
        return random_int(1000, 9999);
    }

    function sendSms($phone, $confirmCode)
    {
        // Вызываем класс по работе с СМС
        $sendSMS = new SMSC();

        try {

            // Передаем параметры для отправки СМС
            $sendSMS->send_sms($phone, "Confirm number: " . (int)$confirmCode, '1');

        } catch (Exception $e) {

            throw new \yii\web\ForbiddenHttpException('Сообщение не доставлено! Попробуйте позднее');

        }
    }

    function deletePhoneFromConfirmCode($phone)
    {
        $confirmCodeModel = UsersConfirmCode::findOne(['phone' => $phone]);
        if ($confirmCodeModel) {
            $confirmCodeModel->delete();
        }
    }

    public function actionLogin()
    {
        $model = new Auth();

        $model->scenario = Auth::SCENARIO_LOGIN;

        if ($model->load(Yii::$app->request->post(), '')) {
            if ($model->validate()) {
                $user = $model->getUser();
                $model->password = hash_hmac("sha256", $model->password, $this->passwordKey, false);
                if (!$user) {
                    throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Account does not exists'));
                }
                if ($model->login()) {
                    if ($user['status'] === 2)
                        throw new \yii\web\ForbiddenHttpException(Yii::t('app', 'Account is off'));
                    if ($user['status'] === 3)
                        throw new \yii\web\ForbiddenHttpException(Yii::t('app', 'Account is blocked'));

                    $get_user_data = Users::findOne($user['id']);
                    $get_user_data->device_type = $model->device_type;
                    $get_user_data->fcm_id = $model->fcm_id;

                    if ($get_user_data->save()) {
                        return $this->generateToken($user);
                    }

                } else {
                    $model->addError('username', Yii::t('app', 'Username or password incorrect'));
                    Yii::$app->response->statusCode = 403;
                }
            }
        } else {
            throw new  \yii\web\ForbiddenHttpException();
        }

        return $model;
    }

    public function actionGoogle()
    {
        $id_token = Yii::$app->request->post('id_token');

        $model = new SocialAuth();

        $model->scenario = SocialAuth::SCENARIO_GOOGLE;
        $model->load(Yii::$app->request->post(), '');

        if ($model->validate()) {

            $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=';

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url . $id_token);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);
            $output = json_decode($output);

            if (isset($output->error)) {
                throw new  \yii\web\UnauthorizedHttpException($output->error);

            }

            $user = Users::find()
                ->where(['google_id' => (string)$output->sub])
                ->asArray()->one();

            if ($user) {
                return $this->generateToken($user);
            } else {

                $user = new Users();

                $user->username = $model->username;
                $user->email = $output->email;
                $user->google_id = (string)$output->sub;
                $user->first_name = $output->name;
                $user->created_at = time();
                $user->status = 1;
                $user->fcm_id = Yii::$app->request->post('fcm_id');
                $user->device_type = Yii::$app->request->post('device_type');
                $user->password = hash_hmac("sha256", time() . $output->sub, $this->passwordKey, false);
                $picture = $output->picture;

                if (strlen($picture) > 0) {
                    $imgExtention = $this->imageExtentionFromUrl($picture);
                    $user->photo = md5(time() . $output->sub) . '.' . $imgExtention;
                }

                if ($user->save()) {
                    $user_rating_store = new EventRating();
                    $user_rating_store->user_id = $user->id;
                    $user_rating_store->count = 0;
                    $user_rating_store->save();

                    if (strlen($picture) > 0) {
                        $picture = file_get_contents($picture);
                        $cdn = \Yii::getAlias('@cdn');
                        $img = $cdn . '/users/' . $user->photo;
                        file_put_contents($img, $picture);
                    }

                    $user = Users::find()
                        ->where(['google_id' => (string)$output->sub])
                        ->asArray()->one();

                    return $this->generateToken($user);
                } else {
                    return $user;
                }
            }

            return $output;

        } else {
            return $model;
        }
        /*$client = new \Google_Client();
        $client->setAuthConfig('../web/google_auth.json');
        $client->setAccessType('offline');
        $client->addScope('profile');
        $client->addScope('email');
        $client->setIncludeGrantedScopes(true);   // incremental auth

        $code = Yii::$app->request->get('code');
        if (isset($code)) {
            $token = $client->fetchAccessTokenWithAuthCode($code);
            if (isset($token['access_token'])) {

                $client->setAccessToken($token['access_token']);

                $google_oauth = new \Google\Service\Oauth2($client);

                $google_account_info = $google_oauth->userinfo->get();

                $picture = $google_account_info->picture;
                if (strlen($picture) > 0) {
                    $imgExtention = $this->imageExtentionFromUrl($picture);

                    $picture = file_get_contents($picture);

                    $cdn = \Yii::getAlias('@cdn');

                    $img = $cdn . '/google/' . time() . '.' . $imgExtention;

                    file_put_contents($img, $picture);
                }

                return [
                    'token' => $token,
                    'userInfo' => $google_account_info
                ];

                echo "<h1>$google_account_info->email</h1>";
                echo "<h1>$google_account_info->name</h1>";
                echo "<img src='$google_account_info->picture' width='200'/>";
//            return [
//                'email'=>$google_account_info->email,
//                'name'=>$google_account_info->name,
//                'picture'=>$google_account_info->picture,
//            ];
            } else {
                echo "<a href='" . $client->createAuthUrl() . "'>Google Login</a>";

            }
        } else {
            echo "<a href='" . $client->createAuthUrl() . "'>Google Login</a>";
        }*/


    }

    public function actionGoogleCallback()
    {
        $id_token = Yii::$app->request->post('id_token');

        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url . $id_token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output);

        if (isset($output->error)) {
            throw new  \yii\web\UnauthorizedHttpException($output->error);
        }

        $user = Users::find()
            ->where(['google_id' => (string)$output->sub])
            ->asArray()->one();

        if ($user) {
            return $this->generateToken($user);
        } else {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Not found'));
        }
    }

    public function actionFacebook()
    {
        $access_token = Yii::$app->request->post('access_token');

        $model = new FacebookAuth();

        $model->scenario = FacebookAuth::SCENARIO_FACEBOOK;
        $model->load(Yii::$app->request->post(), '');

        if ($model->validate()) {

            $url = 'https://graph.facebook.com/me?fields=id,name,email,picture&access_token=';

            $ch = curl_init();
            if ($ch) {
                curl_setopt($ch, CURLOPT_URL, $url . $access_token);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $output = curl_exec($ch);
                curl_close($ch);
                $output = json_decode($output);
                if ($output) {
                    $user = Users::find()
                        ->select(['id'])
                        ->where(['facebook_app_id' => (string)$output->id])->asArray()->one();

                    if ($user) {
                        return $this->generateToken($user);
                    } else {
                        $user = new Users();

                        $user->username = $model->username;
                        $user->email = $output->email;
                        $user->facebook_app_id = (string)$output->id;
                        $user->first_name = $output->name;
                        $user->created_at = time();
                        $user->status = 1;
                        $user->fcm_id = Yii::$app->request->post('fcm_id');
                        $user->device_type = Yii::$app->request->post('device_type');
                        $user->password = hash_hmac("sha256", time() . $output->id, $this->passwordKey, false);
                        $picture = $output->picture->data->url;

                        if (strlen($picture) > 0) {
                            $imgExtention = $this->imageExtentionFromUrl($picture);
                            $user->photo = md5(time() . $output->id) . '.' . $imgExtention;
                        }

                        if ($user->save()) {
                            $user_rating_store = new EventRating();
                            $user_rating_store->user_id = $user->id;
                            $user_rating_store->count = 0;
                            $user_rating_store->save();

                            if (strlen($picture) > 0) {
                                $picture = file_get_contents($picture);
                                $cdn = \Yii::getAlias('@cdn');
                                $img = $cdn . '/users/' . $user->photo;
                                file_put_contents($img, $picture);
                            }

                            $user = Users::find()
                                ->where(['facebook_app_id' => (string)$output->id])
                                ->asArray()->one();

                            return $this->generateToken($user);
                        } else {
                            return $user;
                        }
                    }

                }
            }
        } else {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Not found'));
        }
    }

    public function actionFacebookCallback()
    {
        $access_token = Yii::$app->request->post('access_token');

        if (!empty($access_token)) {
            $url = 'https://graph.facebook.com/me?fields=id,name,email&access_token=';

            $ch = curl_init();
            if ($ch) {
                curl_setopt($ch, CURLOPT_URL, $url . $access_token);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $output = curl_exec($ch);
                curl_close($ch);
                $output = json_decode($output);


                if ($output) {
                    $user = Users::find()
                        ->select(['id'])
                        ->where(['facebook_app_id' => (string)$output->id])
                        ->asArray()
                        ->one();

                    if ($user) {
                        return $this->generateToken($user);
                    } else {
                        throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Not found'));
                    }
                }
            }
        } else {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Not found'));
        }
    }

    public function actionVkontakte()
    {

        $access_token = Yii::$app->request->post('access_token');
        $user_id = Yii::$app->request->post('user_id');
        $username = Yii::$app->request->post('username');

        $model = new VkontakteAuth();

        $model->scenario = VkontakteAuth::SCENARIO_VKONTAKTE;
        $model->load(Yii::$app->request->post(), '');

//        $client_id = '7865550';
//        $client_secret = 'U6YVOpNogZN0JZ4EUNp9';
//        $url = 'https://oauth.vk.com/access_token?client_id='.$client_id.'&client_secret='.$client_secret.'&redirect_uri=https://beatme.online&code='.$code;
//
//        $ch = curl_init();
//
//        curl_setopt($ch, CURLOPT_URL, $url );
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        $output = curl_exec($ch);
//        curl_close($ch);
//        $output = json_decode($output);

//        if (isset($output->error)) {
//            throw new  \yii\web\UnauthorizedHttpException(json_encode($output));
//        }

//        $access_token = $output->access_token;
//        $user_id = $output->user_id;

        if ($model->validate()) {
            $url = 'https://api.vk.com/method/users.get?user_id=' . $user_id . '&v=5.131&access_token=' . $access_token . '&fields=sex,photo_max';

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);
            $output = json_decode($output);

            if (isset($output->error)) {
                throw new  \yii\web\UnauthorizedHttpException(json_encode($output));
            }

            $user = Users::find()
                ->where(['vk_id' => (string)$user_id])
                ->asArray()->one();

            if ($user) {
                return $this->generateToken($user);
            } else {

                $user = new Users();

                $user->username = $username;
//                $user->email = $output->email;
                $user->vk_id = (string)$user_id;
                $user->first_name = $output->response[0]->first_name;
                $user->created_at = time();
                $user->status = 1;
                $user->fcm_id = Yii::$app->request->post('fcm_id');
                $user->device_type = Yii::$app->request->post('device_type');
                $user->password = hash_hmac("sha256", time() . $user_id, $this->passwordKey, false);
                $picture = $output->response[0]->photo_max;

                if (strlen($picture) > 0) {
                    $imgExtention = $this->imageExtentionFromUrl($picture);
                    $user->photo = md5(time() . $output->response[0]->photo_max) . '.' . $imgExtention;
                }

                if ($user->save()) {
                    $user_rating_store = new EventRating();
                    $user_rating_store->user_id = $user->id;
                    $user_rating_store->count = 0;
                    $user_rating_store->save();

                    if (strlen($picture) > 0) {
                        $picture = file_get_contents($picture);
                        $cdn = \Yii::getAlias('@cdn');
                        $img = $cdn . '/users/' . $user->photo;
                        file_put_contents($img, $picture);
                    }

                    $user = Users::find()
                        ->where(['vk_id' => (string)$user->vk_id])
                        ->asArray()->one();

                    return $this->generateToken($user);
                } else {
                    return $user;
                }
            }

        } else {
            return $model;
        }
    }

    public function actionVkontakteCallback()
    {
        //https://oauth.vk.com/authorize?client_id=7865550&display=mobile&redirect_uri=https://beatme.online&scope=offline&response_type=token&v=5.131&state=123456

        if (!Yii::$app->request->post('code')) {
            throw new  \yii\web\UnauthorizedHttpException("No params");
        }
//        $access_token = Yii::$app->request->get('access_token');
        $code = Yii::$app->request->post('code');

        $client_id = '7865550';
        $client_secret = 'U6YVOpNogZN0JZ4EUNp9';

        $url = 'https://oauth.vk.com/access_token?client_id=' . $client_id . '&client_secret=' . $client_secret . '&redirect_uri=https://beatme.online&code=' . $code;
//        return $url;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output);

        if (isset($output->error)) {
            throw new  \yii\web\UnauthorizedHttpException($output->error);
        }

        $user = Users::find()
            ->where(['vk_id' => $output->user_id])
            ->asArray()->one();

        if ($user) {
            return $this->generateToken($user);
        } else {
            //throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Not found'));
            return [
                "access_token" => $output->access_token,
                "user_id" => $output->user_id,
            ];
        }
    }

    function imageExtentionFromUrl($imagePath)
    {
        $mimes = array(
            IMAGETYPE_GIF => "gif",
            IMAGETYPE_JPEG => "jpg",
            IMAGETYPE_PNG => "png",
            IMAGETYPE_SWF => "swf",
            IMAGETYPE_PSD => "psd",
            IMAGETYPE_BMP => "bmp",
            IMAGETYPE_TIFF_II => "tiff",
            IMAGETYPE_TIFF_MM => "tiff",
            IMAGETYPE_JPC => "jpc",
            IMAGETYPE_JP2 => "jp2",
            IMAGETYPE_JPX => "jpx",
            IMAGETYPE_JB2 => "jb2",
            IMAGETYPE_SWC => "swc",
            IMAGETYPE_IFF => "iff",
            IMAGETYPE_WBMP => "wbmp",
            IMAGETYPE_XBM => "xbm",
            IMAGETYPE_ICO => "ico");

        if (($image_type = exif_imagetype($imagePath))
            && (array_key_exists($image_type, $mimes))) {
            return $mimes[$image_type];
        } else {
            echo false;
        }
    }

}
