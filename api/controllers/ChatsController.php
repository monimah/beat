<?php

namespace api\controllers;

use app\models\Chats;
use app\models\UserChats;
use app\models\Users;
use common\components\ApiFrontend;
use common\components\MobileNotifyer;
use Yii;

class ChatsController extends ApiFrontend
{
    public $serializer = [
        'class' => 'common\components\MySerializer',
        'collectionEnvelope' => 'data',
    ];

    /**
     * Список чатов пользователя
     * GET /api/chats
     * Response chat array
     */
    public function actionList()
    {
        $user_id = $this->token->id;

        $model = UserChats::find()
            ->select(['id', 'creator_id', 'user_id', 'chat_id'])
            ->where(['creator_id' => $user_id])
            ->orWhere(['user_id' => $user_id])
            ->with(['creator' => function($query){
                return $query->select(['id', 'first_name', 'photo', 'username', 'fcm_id']);
            },
//            'user' => function($query){
//                return $query->select(['id', 'first_name', 'photo', 'username', 'fcm_id']);
//            }
            ])
            ->asArray()
            ->all();


        $total_count = 0;
        foreach($model as $key=>$item){
            $message = Chats::find()
                ->where(['chat_id' => $item['chat_id']])
                ->orderBy(['id' => SORT_DESC])
                ->asArray()
                ->one();
            $unseen = Chats::find()
                ->where(['chat_id' => $item['chat_id']])
                ->andWhere(['delivered' => 0])
                ->andWhere(['=', 'reciver_id', $user_id])
                ->count();
            $model[$key]['last_message'] = $message;
            $model[$key]['unseen'] = $unseen;
            $total_count += $unseen;

            if($user_id == $item['creator_id']){
                $model[$key]['user'] = Users::find()
                    ->select(['id', 'first_name', 'photo', 'username', 'fcm_id'])
                    ->where(['id'=> $item['user_id']])
                    ->asArray()
                    ->one();
            } else{
                $model[$key]['user'] = $model[$key]['creator'];
            }

        }

        /**
         *  ->with(['participant'=>function($query){
        $query->select(['id', 'username','photo']);
        }])
         */
//        $model['all_unseen'] = $total_count;

        return ['chats' => $model, 'unseen' => $total_count];
    }

    /**
     * Создание чата с пользователем
     * POST /api/chats
     * Params:
     *  - user_id
     * Response:
     *  {
            "creator_id": "4", - Создатель
            "user_id": "2", - С кем чат
            "chat_id": "ee0d43ce-eefe-4c4d-b73a-0ad049bdb85c",
            "id": 3
        }
     */
    public function actionCreateChat()
    {
        $data = (\Yii::$app->request->post());
        $user_id = $this->token->id;

        $exists = UserChats::find()
            ->orFilterWhere(['and',
                ['user_id' => $data['user_id']],
                ['creator_id' => $user_id]
            ])
            ->orFilterWhere(['and',
                ['creator_id' => $data['user_id']],
                ['user_id' => $user_id]
            ])
            ->with(['creator' => function($query){
                return $query->select(['id', 'first_name', 'photo', 'fcm_id']);
            },
                'user' => function($query){
                    return $query->select(['id', 'first_name', 'photo', 'fcm_id']);
                }])
            ->asArray()
            ->one();

        if($exists){
            return $exists;
        }

        $model = new UserChats();
        $model->creator_id = $user_id;
        $model->user_id = $data['user_id'];
        $model->chat_id = \thamtech\uuid\helpers\UuidHelper::uuid();

        if($model->validate()){
            $model->save();
        }

        return UserChats::find()
            ->where(['id' => $model['id']])
            ->andWhere(['creator_id' => $user_id])
            ->with(['creator' => function($query){
                return $query->select(['id', 'first_name', 'photo', 'fcm_id']);
            },
                'user' => function($query){
                    return $query->select(['id', 'first_name', 'photo', 'fcm_id']);
                }])
            ->asArray()
            ->one();;
    }

    /**
     * Отправка ообщений
     * POST /api/message
     * Params:
     *  - chat_id - ID чата
     *  - message - сообщение
     * Response:
     *  {
            "chat_id": "ee0d43ce-eefe-4c4d-b73a-0ad049bdb85c",
            "sender_id": "4",
            "reciver_id": 2,
            "message": "test",
            "created_at": 1633026385,
            "updated_at": 1633026385,
            "id": 10
        }
     */
    public function actionSendMessage()
    {
        $data = (\Yii::$app->request->post());
        $user_id = $this->token->id;
        $formatter = \Yii::$app->formatter;


        if(!empty($data['chat_id'])) {
            $chat = UserChats::find()
                ->where(['chat_id' => $data['chat_id']])
                ->one();

            if (!$chat) {
                throw new \yii\web\ForbiddenHttpException(Yii::t('app', 'Chat not find'));
            }
        }else{
            throw new \yii\web\ForbiddenHttpException(Yii::t('app', 'Chat not find'));
        }


        $model = new Chats;
        $model->load($data);
        $model->chat_id = $chat['chat_id'];
        $model->sender_id = $user_id;
        $model->reciver_id = ($chat['creator_id'] == $user_id)?$chat['user_id']:$chat['creator_id'];
        $model->message = $data['message'];
        $model->created_at = time();
        $model->updated_at = time();

        if($model->validate()){
            $model->save();

            $userModel = Users::findOne($model->reciver_id);
            $fcm_id = $userModel->fcm_id ? $userModel->fcm_id : "some_token";
            if ($fcm_id) {
                MobileNotifyer::sendNotifyre("Новое сообщение", $model->message, $fcm_id, ['current_time' => time()]);
            }

        }

        return $model;
    }

    /**
     * Получение списка сообщений, в обратном порядке, ограничени последние 20
     * GET /api/messages?chat_id=ee0d43ce-eefe-4c4d-b73a-0ad049bdb85c
     * Params:
     *  - chat_id - ID чата
     *  - offset - по умолчниею 0, страница с сообшениями
     *
     * Response:
     * [
            {
            "id": 10,
            "sender_id": 4,
            "reciver_id": 2,
            "message": "test",
            "delivered": 0,
            "created_at": 1633026385,
            "updated_at": 1633026385,
            "chat_id": "ee0d43ce-eefe-4c4d-b73a-0ad049bdb85c"
            },
     *  .................
     * ]
     */
    public function actionChatMessages()
    {
        $user_id = $this->token->id;
        $data = (Yii::$app->request->get());
        $offser = (!empty($data['offset']))?$data['offset']:0;
        $messages = Chats::find()
            ->where(['chat_id' => $data['chat_id']])
            ->offset($offser)
            ->limit(20)
            ->orderBy(['id' => SORT_DESC])
            ->all();

        Chats::updateAll(['delivered' => 1], ['and', ['=', 'reciver_id', $user_id], ['=', 'chat_id',  $data['chat_id']]]);
        return $messages;
    }

    /**
     * Установка статуса прочитанно сообщению
     * POST /api/delivered
     * Params:
     *  - message_id - ID сообщения
     * Response:
     * {
            "id": 10,
            "sender_id": 4,
            "reciver_id": 2,
            "message": "test",
            "delivered": 1,
            "created_at": 1633026385,
            "updated_at": 1633026979,
            "chat_id": "ee0d43ce-eefe-4c4d-b73a-0ad049bdb85c"
        }
     */
    public function actionSetStatus()
    {
        $data = (\Yii::$app->request->post());

        $model = Chats::find()
            ->where(['id' => $data['message_id']])
            ->andWhere(['delivered' => 0])
            ->one();

        if($model){
            $model->delivered = 1;
            $model->updated_at = time();
            $model->save();
        }

        return $model;
    }
}
