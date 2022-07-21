<?php

namespace api\controllers;

use app\models\EventComments;
use app\models\EventCommentsSecondLevel;
use app\models\EventRating;
use app\models\EventWinners;
use app\models\SuperTags;
use common\components\MakeDir;
use Yii;
use yii\web\UploadedFile;
use app\models\EventUserVotes;
use app\models\EventParticipation;
use app\models\Events;
use app\models\Users;
use common\models\UploadForm;
use common\components\ApiFrontend;
use common\components\MobileNotifyer;

class EventParticipationController extends ApiFrontend
{
    public function actionIndex()
    {
        return 1;
    }

    public function actionCreate()
    {
        $data = (\Yii::$app->request->post());
        $model = new EventParticipation();
        $user_id = $this->token->id;

        $event = Events::findOne($data['event_id']);
        $time_now = $event['created_at'] + (48 * 3600);

        if ($event['deadline'] > $time_now) {
            throw new \yii\web\NotFoundHttpException(\Yii::t('app', 'Not found'));
        }

        if($event['user_id'] === $user_id){
            throw new \yii\web\ForbiddenHttpException('Вы не можете отправить запрос на своё событие!');
        }

        $fileModel = new UploadForm();
        $fileModel->video_file = UploadedFile::getInstanceByName('video_file');

        $previewModel = new UploadForm();
        $previewModel->imageFile = UploadedFile::getInstanceByName('preview_file');

        if (!$fileModel->validate())
            return $fileModel;

        if (!$previewModel->validate())
            return $previewModel;

        $model->event_id = $data['event_id'];
        $model->parent_id = $event['user_id'];
        $model->participant_id = $user_id;
        $cdn = Yii::getAlias('@cdn');
        $model->videoFile = md5($model->participant_id . time()) . '.' . $fileModel->video_file->extension;
        $model->previewFile = md5($model->participant_id . time()) . '.' . $previewModel->imageFile->extension;
        if($event['privacy'] === 2){
            $model->result_data_participant = $data['result_data_participant'];
        }
        $model->status = 1;
        $model->createdAt = time();

        if ($model->validate()) {
            $model->save(false);
            MakeDir::mkDir('events_video/' . $model->id);
            $fileModel->video_file->saveAs($cdn . '/events_video/' . $model->event_id . '/' . $model->videoFile);
            $previewModel->imageFile->saveAs($cdn . '/events_video/' . $model->event_id . '/' . $model->previewFile);

            if ($user_id != $event['user_id']) {
                $userModel = Users::findOne($event['user_id']);
                $fcm_id = $userModel->fcm_id ? $userModel->fcm_id : "some_token";
                if ($fcm_id) {
                    MobileNotifyer::sendNotifyre("Внимание", "На ваш вызов откликнулись", $fcm_id, ['current_time' => time(), 'event_name' => $event->title]);
                }
            }
            exit;
        }

        return $model;
    }

    public function actionOperation()
    {
        $data = (\Yii::$app->request->post());

        $model = EventParticipation::find()
            ->where(['event_id' => $data['event_id']])
            ->andWhere(['parent_id' => $data['parent_id']])
            ->andWhere(['status' => $data['status']])
            ->one();

        $event = Events::findOne($data['event_id']);

        $get_event_data = Events::find()
            ->where(['id' => $data['event_id']])
            ->one();

        if ($model) {
            $model->status = $data['user_action'];
            if ($model->validate()) {
                $model->save();
                if ($data['user_action'] === 2) {
                    if($get_event_data->privacy === 1){
                        $get_event_data->voting_deadline = time() + (24 * 3600);
                        $get_event_data->in_voting = 1;
                        $get_event_data->save();

                        $userModel = Users::findOne($model['participant_id']);
                        $fcm_id = $userModel->fcm_id ? $userModel->fcm_id : "some_token";
                        if ($fcm_id) {
                            MobileNotifyer::sendNotifyre("Внимание", "Ваш вызов принят", $fcm_id, ['current_time' => time(), 'event_name' => $event->title]);
                        }

                        return true;
                    }
                    elseif ($get_event_data->privacy === 2){
                        // Находим участника в событии
                        $check_participant = EventParticipation::find()
                            ->where(['event_id' => $get_event_data->id, 'parent_id' => $get_event_data->user_id, 'status' => 2])
                            ->one();

                        // Присваиваем супертег ID в переменную
                        $is_supertag = $get_event_data->super_tag_id;
                        // Проверяем создавалась ли событие по супертегу
                        if($is_supertag) {
                            // Получаем вариант расчёта супертега
                            $get_supertag = SuperTags::find()
                                ->where(['id' => $is_supertag])
                                ->one();

                            // Если win_type = 1 то расчитываем по "у кого цифра больше"
                            if($get_supertag->win_type === 1) {
                                // Сравнение результат "Организатор" больше чем "Участник"
                                if ($get_event_data->result_data > $check_participant->result_data_participant) {
                                    // Создаем экземпляр модели и сохраняем данные
                                    $winner_store = new EventWinners();
                                    $winner_store->event_id = $get_event_data->id;
                                    $winner_store->winner_user_id = $get_event_data->user_id;
                                    $winner_store->loser_user_id = $check_participant->participant_id;
                                    $winner_store->event_type = $get_event_data->privacy;
                                    $winner_store->save();

                                    // Получаем огранизатора с таблицы рейтингов
                                    $check_user_rating = EventRating::find()
                                        ->where(['user_id' => $get_event_data->user_id])
                                        ->one();

                                    // Проверяем если огранизатор есть в таблице то присваиваем к его рейтингу + 1 за победу
                                    if ($check_user_rating) {
                                        $update_rating_data = EventRating::findOne($check_user_rating->id);
                                        $update_rating_data->count += 1;
                                        $update_rating_data->save();
                                    } // Иначе добавляем огранизатора в таблицу рейтинга и добавляем + 1 за победу
                                    else {
                                        $user_rating_store = new EventRating();
                                        $user_rating_store->user_id = $get_event_data->user_id;
                                        $user_rating_store->count += 1;
                                        $user_rating_store->save();
                                    }

                                    // Меняем статус события как закончен и сохраняем
                                    $get_event_data->status = 2;
                                    $get_event_data->save();

                                    return true;
                                }
                                // Сравнение результат "Участник" больше чем "Организатор"
                                elseif($check_participant->result_data_participant > $get_event_data->result_data){
                                    // Создаем экземпляр модели и сохраняем данные
                                    $winner_store = new EventWinners();
                                    $winner_store->event_id = $get_event_data->id;
                                    $winner_store->winner_user_id = $check_participant->participant_id;
                                    $winner_store->loser_user_id = $get_event_data->user_id;
                                    $winner_store->event_type = $get_event_data->privacy;
                                    $winner_store->save();

                                    // Получаем участника с таблицы рейтингов
                                    $check_user_rating = EventRating::find()
                                        ->where(['user_id' => $check_participant->participant_id])
                                        ->one();

                                    // Проверяем если участник есть в таблице то присваиваем к его рейтингу + 1 за победу
                                    if($check_user_rating){
                                        $update_rating_data = EventRating::findOne($check_user_rating->id);
                                        $update_rating_data->count += 1;
                                        $update_rating_data->save();
                                    }

                                    // Иначе добавляем участника в таблицу рейтинга и добавляем + 1 за победу
                                    else{
                                        $user_rating_store = new EventRating();
                                        $user_rating_store->user_id = $check_participant->participant_id;
                                        $user_rating_store->count += 1;
                                        $user_rating_store->save();
                                    }

                                    // Меняем статус события как закончен и сохраняем
                                    $get_event_data->status = 2;
                                    $get_event_data->save();

                                    return true;
                                }
                            }
                            // Если win_type = 2 то расчитываем по "у кого цифра меньше"
                            elseif ($get_supertag->win_type === 2){
                                // Сравнение результат "Организатор" меньше чем  "Участник"
                                if((int)$get_event_data->result_data < (int)$check_participant->result_data_participant){
                                    // Создаем экземпляр модели и сохраняем данные
                                    $winner_store = new EventWinners();
                                    $winner_store->event_id = $get_event_data->id;
                                    $winner_store->winner_user_id = $get_event_data->user_id;
                                    $winner_store->loser_user_id = $check_participant->participant_id;
                                    $winner_store->event_type = $get_event_data->privacy;
                                    $winner_store->save();

                                    $check_user_rating = EventRating::find()
                                        ->where(['user_id' => $get_event_data->user_id])
                                        ->one();
                                    if($check_user_rating){
                                        $update_rating_data = EventRating::findOne($check_user_rating->id);
                                        $update_rating_data->count += 1;
                                        $update_rating_data->save();
                                    }else{
                                        $user_rating_store = new EventRating();
                                        $user_rating_store->user_id = $get_event_data->user_id;
                                        $user_rating_store->count += 1;
                                        $user_rating_store->save();
                                    }

                                    $get_event_data->status = 2;
                                    $get_event_data->save();
                                }
                                // Сравнение результат "Участник" меньше чем "Организатор"
                                elseif((int)$check_participant->result_data_participant < (int)$get_event_data->result_data){
                                    // Создаем экземпляр модели и сохраняем данные
                                    $winner_store = new EventWinners();
                                    $winner_store->event_id = $get_event_data->id;
                                    $winner_store->winner_user_id = $check_participant->participant_id;
                                    $winner_store->loser_user_id = $get_event_data->user_id;
                                    $winner_store->event_type = $get_event_data->privacy;
                                    $winner_store->save();

                                    $check_user_rating = EventRating::find()
                                        ->where(['user_id' => $check_participant->participant_id])
                                        ->one();
                                    if($check_user_rating){
                                        $update_rating_data = EventRating::findOne($check_user_rating->id);
                                        $update_rating_data->count += 1;
                                        $update_rating_data->save();
                                    }else{
                                        $user_rating_store = new EventRating();
                                        $user_rating_store->user_id = $check_participant->participant_id;
                                        $user_rating_store->count += 1;
                                        $user_rating_store->save();
                                    }

                                    $get_event_data->status = 2;
                                    $get_event_data->save();

                                    return true;
                                }
                            }
                        }

                        $userModel = Users::findOne($model['participant_id']);
                        $fcm_id = $userModel->fcm_id ? $userModel->fcm_id : "some_token";
                        if ($fcm_id) {
                            MobileNotifyer::sendNotifyre("Внимание", "Ваш вызов принят", $fcm_id, ['current_time' => time(), 'event_name' => $event->title]);
                        }
                    }
                }
                elseif ($data['user_action'] === 3) {
                    $userModel = Users::findOne($model['participant_id']);
                    $fcm_id = $userModel->fcm_id ? $userModel->fcm_id : "some_token";
                    if ($fcm_id) {
                        MobileNotifyer::sendNotifyre("Внимание", "Вашь вызов отклонен", $fcm_id, ['current_time' => time(), 'event_name' => $event->title]);
                    }
                }

                return $model;
            }
        } else {
            throw new \yii\web\NotFoundHttpException(\Yii::t('app', 'Not found'));
        }
    }

    public function actionUserComment(){
        $data = (\Yii::$app->request->post());

        $event = Events::findOne($data['event_id']);

        if ($event['status'] !== 2) {
            throw new \yii\web\ForbiddenHttpException('Вы не можете оставлять комментарий на активное событие!' );
        }

        $user_id = $this->token->id;

        $model = new EventComments();
        $model->event_id = $data['event_id'];
        $model->user_id = $user_id;
        $model->message = $data['message'];
        $model->createdAt = time();

        if ($model->validate()) {
            $model->save();

            return true;
        }
    }

    public function actionCommentToUser(){
        $data = (\Yii::$app->request->post());

        $user_comment = EventComments::findOne($data['comment_id']);

        if (!$user_comment) {
            throw new \yii\web\ForbiddenHttpException('Родительский комментарий не найден!' );
        }

        $user_id = $this->token->id;

        $model = new EventCommentsSecondLevel();
        $model->comment_id = $data['comment_id'];
        $model->user_id = $user_id;
        $model->message = $data['message'];
        $model->createdAt = time();

        if ($model->validate()) {
            $model->save();

            return true;
        }
    }

    public function actionGetEventComments($id){
        $event_comments = EventComments::find()
            ->where(['event_id' => $id])
            ->with([
                'user' => function ($query) {
                    $query->select(['id', 'username', 'photo']);
                },
                'eventCommentsSecondLevels' => function ($query){
                    $query->select(['comment_id', 'message', 'createdAt', 'user_id'])
                    ->with([
                        'user' => function ($query) {
                            $query->select(['id', 'username', 'photo']);
                        }
                    ]);
                }
            ])
            ->asArray()
            ->all();

        return $event_comments;
    }

    public function actionUserVotes()
    {
        // Присваиваем данные с запроса
        $data = (\Yii::$app->request->post());

        // Находим нужное событие по event_id
        $event = Events::findOne($data['event_id']);

        // Генерируем временные рамки (24 часа)
        $limit_time = time() - (24 * 3600);

        // Проверяем поподает ли событие в соответствующий дедлайн
        if ($event['voting_deadline'] < $limit_time) {
            throw new \yii\web\NotFoundHttpException(\Yii::t('app', 'Not found'));
        }

        // Получаем ID пользователя
        $user_id = $this->token->id;

        // Получаем запись где пользователь является организатором события
        $event_parent_data = EventParticipation::find()
            ->where(['event_id' => $data['event_id'], 'parent_id' => $user_id, 'status' => 2])
            ->one();

        // Если запись найдена то кидаем эксепшн
        if($event_parent_data){
            throw new \yii\web\ForbiddenHttpException('Вы не можете голосовать на событии где являетесь организатором!' );
        }

        // Получаем запись где пользователь принимает участие в событии
        $event_participant_data = EventParticipation::find()
            ->where(['event_id' => $data['event_id'], 'participant_id' => $user_id, 'status' => 2])
            ->one();

        // Если запись найдена то кидаем эксепшн
        if($event_participant_data){
            throw new \yii\web\ForbiddenHttpException('Вы не можете голосовать на событии где принимаете участие!' );
        }

        // Получаем голос пользователя в данном событии
        $exists_data = EventUserVotes::find()
            ->where(['event_id' => $data['event_id'], 'user_id' => $user_id])->one();

        // Если запись была найдена то кидаем эксепшн
        if($exists_data){
            throw new \yii\web\ForbiddenHttpException('Вы уже голосовали в этом событии!' );
        }

        // Если запись на была найдена то записываем голос пользователя в экземпляр модели
        $model = new EventUserVotes();
        $model->event_id = $data['event_id'];
        $model->participant_id = $data['participant_id'];
        $model->user_id = $user_id;
        $model->createdAt = time();

        // Проверяем экземпляр модели на соответсвие (валидация) и записываем в базу
        if ($model->validate()) {
            $model->save();
            return true;
        }
    }

    public function actionVoteEventView($id)
    {
        $user_id = $this->token->id;
        $user_check = EventUserVotes::find()
            ->where(['user_id' => $user_id])
            ->andWhere(['event_id' => $id])
            ->one();

        $event = EventParticipation::find()
            ->select(['event_id', 'parent_id', 'participant_id', 'videoFile', 'previewFile'])
            ->with([
                'parent' => function ($query) {
                    $query->select(['username', 'photo']);
                },
                'participant' => function ($query) {
                    $query->select(['username', 'photo']);
                },
                'event' => function ($query) {
                    $query->select(['title', 'videoFile', 'voting_deadline', 'previewFile']);
                },
            ])
            ->where(['event_id' => $id, 'status' => 2])
            ->asArray()
            ->one();

        $eventData = [
            'parent' => [
                'id' => $event['parent_id'],
                'username' => $event['parent']['username'],
                'photo' => $event['parent']['photo'],
                'videoFile' => $event['event']['videoFile'],
                'previewFile' => $event['event']['previewFile'],
            ],
            'participant' => [
                'id' => $event['participant_id'],
                'username' => $event['participant']['username'],
                'photo' => $event['participant']['photo'],
                'videoFile' => $event['videoFile'],
                'previewFile' => $event['previewFile'],
            ],
            'voting_deadline' => $event['event']['voting_deadline'],

        ];

        return [
            'vote_data' => $eventData,
            'user_vote_data' => $user_check,
        ];
    }
}
