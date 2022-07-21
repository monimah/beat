<?php

namespace api\controllers;

use app\models\EventParticipation;
use app\models\EventRating;
use app\models\Events;
use app\models\EventUserVotes;
use app\models\EventWinners;
use app\models\Users;
use common\components\ApiFrontend;
use common\components\MakeDir;
use common\models\UploadForm;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use common\components\MobileNotifyer;

class UsersController extends ApiFrontend
{
    protected $imageFolder = 'users';

    public function actionIndex()
    {
        // Находим данные пользователя
        $model = $this->findModel();

        // Пропускаем его пароль
        unset($model->password);

        // Активные события на которых пользователь голосовал
//        $my_votes = EventUserVotes::find()
//            ->select(['id', 'user_id', 'event_id'])
//            ->with(['event' => function ($query) {
//                $query->where(['status' => 1])->andWhere(['in_voting' => 1]);
//            }])
//            ->where(['user_id' => $this->token->id])
//            ->asArray()
//            ->all();

        // Активные события на которых пользователь голосовал
        $my_votes = Events::find()
            ->alias('e')
            ->select(['e.*', 'ev.id as vote_id', 'CONCAT("1") as event_status'])
            ->innerJoin('event_user_votes ev', 'e.id=ev.event_id')
            ->where(['ev.user_id' => $this->token->id])
            ->andWhere(['e.status' => 1, 'in_voting' => 1])
            ->asArray()
            ->all();

        // Активные события, где пользователь принимает участие
        $me_participate = EventParticipation::find()
            ->select([
                'e.*'
            ])
            ->innerJoin('events e', 'e.id=event_participation.event_id')
            ->where(['participant_id' => $this->token->id, 'event_participation.status' => 2, 'e.status' => 1])
            ->asArray()
            ->all();

        // Активные события самого пользователя
        $my_events = Events::find()
            ->where(['user_id' => $this->token->id, 'status' => 1])->asArray()->all();

        // Объединяем массивы в единый массыв
        $my_participate = array_merge($me_participate, $my_events);

        // Все события пользователя которые закончились
        $event_archives = Events::find()
            ->where(['user_id' => $this->token->id, 'status' => 2])->asArray()->all();

        // Все события пользователя где он принимал участие
        $participant_archives = Events::find()
            ->select(['events.*', 'ep.participant_id'])
            ->innerJoin('event_participation ep', 'events.id=ep.event_id AND ep.participant_id=' . $this->token->id . ' AND ep.status=2')
            ->where(['events.status' => 2])
            ->asArray()->all();

        // Объединяем массивы в единый массыв
        $my_archive = array_merge($event_archives, $participant_archives);

        // Запускаем луп и присваиваем ключ и знаечение true на событие где пользователь голосует (не участвует и не события самого пользователя)
//        foreach ($my_votes as $key=> $item){
//            $my_votes[$key]['event_status'] = 1;
//        }

        // Запускаем пул на присвоение статусов к событиям
        foreach ($my_participate as $key=> $item){
            // Запрос на присвоение статуса "Поиск участника"
            $user_parent_so = EventParticipation::find()
                ->where(['event_id' => $item['id'], 'parent_id' => $item['user_id']])->one();

            // Событие, которое создавал сам пользователь в голосовании
            $user_parent_iv = EventParticipation::find()
                ->where(['event_id' => $item['id'], 'parent_id' => $item['user_id'], 'status' => 2])->one();

            // Событие в котором участвуете пользователь находиться в голосовании
            $user_participant = EventParticipation::find()
                ->where(['event_id' => $item['id'], 'participant_id' => $item['user_id'], 'status' => 2])->one();

            // Проверяем на какой из состояний находиться событие
            if(!$user_parent_so){
                $my_participate[$key]['event_status'] = 2;
            }elseif($user_parent_iv){
                $my_participate[$key]['event_status'] = 3;
            }elseif($user_participant){
                $my_participate[$key]['event_status'] = 4;
            }
        }

        // Запускаем луп и присваиваем ключ и значение true/false по результату завершившихся событий
        foreach ($my_archive as $key=> $item) {
            $event_winners = EventWinners::find()->where(['event_id' => $item['id']])->one();
            $my_archive[$key]['event_status'] = 5;
            if ($event_winners) {
                if((int)$event_winners->winner_user_id === (int)$this->token->id){
                    $my_archive[$key]['winner'] = true;
                }else {
                    $my_archive[$key]['winner'] = false;
                }
            }
        }

        $rating_data = EventRating::find()
            ->select(['id', 'user_id', 'count'])
            ->asArray()
            ->orderBy(['count' => SORT_DESC])
            ->all();

        $user_rating_place = 0;
        $counter = 0;

        foreach ($rating_data as $item){
            $counter += 1;
            if($item['user_id'] === $this->token->id){
                $user_rating_place = $counter;
            }
        }

        $get_user_rating = EventRating::find()->where(['user_id' => $this->token->id])->count();

        return [
            'user_profile' => $model,
            'user_rating_position' => $user_rating_place,
            'user_rating' => $get_user_rating,
            'my_votes' => $my_votes,
            'my_participate' => $my_participate,
            'my_archive' => $my_archive,
        ];
    }

    public function actionUserVotesSearch($q = null, $user_id = null){
        if($q !== null && !empty($q) && $user_id !== null && !empty($user_id)){
            $q = (string)$q;
            $q = stripslashes($q);
            $user_id = (int)$user_id;

            // Находим данные пользователя
            $model = Users::find()->where(['id' => $user_id])->one();

            // Пропускаем его пароль
            unset($model->password);

            // Активные события на которых пользователь голосовал
            $my_votes = Events::find()
                ->alias('e')
                ->select(['e.*', 'ev.id as vote_id', 'CONCAT("1") as event_status'])
                ->innerJoin('event_user_votes ev', 'e.id=ev.event_id')
                ->where(['ev.user_id' => $user_id])
                ->andWhere(new \yii\db\Expression('e.title LIKE :param', [':param' => '%' . $q . '%']))
                ->asArray()
                ->all();

            $rating_data = EventRating::find()
                ->select(['id', 'user_id', 'count'])
                ->asArray()
                ->orderBy(['count' => SORT_DESC])
                ->all();

            $user_rating_place = 0;
            $counter = 0;

            foreach ($rating_data as $item){
                $counter += 1;
                if($item['user_id'] === $user_id){
                    $user_rating_place = $counter;
                }
            }

            $get_user_rating = EventRating::find()->where(['user_id' => $user_id])->count();

            return [
                'user_profile' => $model,
                'user_rating_position' => $user_rating_place,
                'user_rating' => $get_user_rating,
                'my_votes' => $my_votes,
            ];
        }
        elseif($q !== null && !empty($q))
        {
            $q = (string)$q;
            $q = stripslashes($q);

            // Находим данные пользователя
            $model = $this->findModel();

            // Пропускаем его пароль
            unset($model->password);

            // Активные события на которых пользователь голосовал
            $my_votes = Events::find()
                ->alias('e')
                ->select(['e.*', 'ev.id as vote_id', 'CONCAT("1") as event_status'])
                ->innerJoin('event_user_votes ev', 'e.id=ev.event_id')
                ->where(['ev.user_id' => $this->token->id])
                ->andWhere(new \yii\db\Expression('e.title LIKE :param', [':param' => '%' . $q . '%']))
                ->asArray()
                ->all();

            $rating_data = EventRating::find()
                ->select(['id', 'user_id', 'count'])
                ->asArray()
                ->orderBy(['count' => SORT_DESC])
                ->all();

            $user_rating_place = 0;
            $counter = 0;

            foreach ($rating_data as $item){
                $counter += 1;
                if($item['user_id'] === $this->token->id){
                    $user_rating_place = $counter;
                }
            }

            $get_user_rating = EventRating::find()->where(['user_id' => $this->token->id])->count();

            return [
                'user_profile' => $model,
                'user_rating_position' => $user_rating_place,
                'user_rating' => $get_user_rating,
                'my_votes' => $my_votes,
            ];
        }
        else{
            return [];
        }
    }

    public function actionUserParticipantsSearch($q = null, $user_id = null){
        if($q !== null && !empty($q) && $user_id !== null && !empty($user_id)){
            $q = (string)$q;
            $q = stripslashes($q);
            $user_id = (int)$user_id;

            // Находим данные пользователя
            $model = Users::find()->where(['id' => $user_id])->one();

            // Пропускаем его пароль
            unset($model->password);

            $rating_data = EventRating::find()
                ->select(['id', 'user_id', 'count'])
                ->asArray()
                ->orderBy(['count' => SORT_DESC])
                ->all();

            $user_rating_place = 0;
            $counter = 0;

            foreach ($rating_data as $item){
                $counter += 1;
                if($item['user_id'] === $user_id){
                    $user_rating_place = $counter;
                }
            }

            $get_user_rating = EventRating::find()->where(['user_id' => $user_id])->count();

            // Активные события самого пользователя
            $my_events = Events::find()
                ->where(['user_id' => $user_id, 'status' => 1])
                ->andWhere(new \yii\db\Expression('title LIKE :param', [':param' => '%' . $q . '%']))
                ->asArray()
                ->all();

            // Активные события, где пользователь принимает участие
            $me_participate = Events::find()
                ->alias('e')
                ->select(['e.*', 'ep.id as participate_id'])
                ->innerJoin('event_participation ep', 'e.id=ep.event_id')
                ->where(['ep.participant_id' => $user_id, 'e.status' => 1])
                ->andWhere(new \yii\db\Expression('e.title LIKE :param', [':param' => '%' . $q . '%']))
                ->asArray()
                ->all();

            // Объединяем массивы в единый массыв
            $my_participate = array_merge($me_participate, $my_events);

            // Запускаем пул на присвоение статусов к событиям
            foreach ($my_participate as $key=> $item){
                // Запрос на присвоение статуса "Поиск участника"
                $user_parent_so = EventParticipation::find()
                    ->where(['event_id' => $item['id'], 'parent_id' => $item['user_id']])->one();

                // Событие, которое создавал сам пользователь в голосовании
                $user_parent_iv = EventParticipation::find()
                    ->where(['event_id' => $item['id'], 'parent_id' => $item['user_id'], 'status' => 2])->one();

                // Событие в котором участвуете пользователь находиться в голосовании
                $user_participant = EventParticipation::find()
                    ->where(['event_id' => $item['id'], 'participant_id' => $item['user_id'], 'status' => 2])->one();

                // Проверяем на какой из состояний находиться событие
                if(!$user_parent_so){
                    $my_participate[$key]['event_status'] = 2;
                }elseif($user_parent_iv){
                    $my_participate[$key]['event_status'] = 3;
                }elseif($user_participant){
                    $my_participate[$key]['event_status'] = 4;
                }
            }

            return [
                'user_profile' => $model,
                'user_rating_position' => $user_rating_place,
                'user_rating' => $get_user_rating,
                'my_participate' => $my_participate,
            ];


        }
        elseif($q !== null && !empty($q)) {
            $q = (string)$q;
            $q = stripslashes($q);

            // Находим данные пользователя
            $model = $this->findModel();

            // Пропускаем его пароль
            unset($model->password);

            $rating_data = EventRating::find()
                ->select(['id', 'user_id', 'count'])
                ->asArray()
                ->orderBy(['count' => SORT_DESC])
                ->all();

            $user_rating_place = 0;
            $counter = 0;

            foreach ($rating_data as $item){
                $counter += 1;
                if($item['user_id'] === $this->token->id){
                    $user_rating_place = $counter;
                }
            }

            $get_user_rating = EventRating::find()->where(['user_id' => $this->token->id])->count();

            // Активные события самого пользователя
            $my_events = Events::find()
                ->where(['user_id' => $this->token->id, 'status' => 1])
                ->andWhere(new \yii\db\Expression('title LIKE :param', [':param' => '%' . $q . '%']))
                ->asArray()
                ->all();

            // Активные события, где пользователь принимает участие
            $me_participate = Events::find()
                ->alias('e')
                ->select(['e.*', 'ep.id as participate_id'])
                ->innerJoin('event_participation ep', 'e.id=ep.event_id')
                ->where(['ep.participant_id' => $this->token->id, 'e.status' => 1])
                ->andWhere(new \yii\db\Expression('e.title LIKE :param', [':param' => '%' . $q . '%']))
                ->asArray()
                ->all();

            // Объединяем массивы в единый массыв
            $my_participate = array_merge($me_participate, $my_events);

            // Запускаем пул на присвоение статусов к событиям
            foreach ($my_participate as $key=> $item){
                // Запрос на присвоение статуса "Поиск участника"
                $user_parent_so = EventParticipation::find()
                    ->where(['event_id' => $item['id'], 'parent_id' => $item['user_id']])->one();

                // Событие, которое создавал сам пользователь в голосовании
                $user_parent_iv = EventParticipation::find()
                    ->where(['event_id' => $item['id'], 'parent_id' => $item['user_id'], 'status' => 2])->one();

                // Событие в котором участвуете пользователь находиться в голосовании
                $user_participant = EventParticipation::find()
                    ->where(['event_id' => $item['id'], 'participant_id' => $item['user_id'], 'status' => 2])->one();

                // Проверяем на какой из состояний находиться событие
                if(!$user_parent_so){
                    $my_participate[$key]['event_status'] = 2;
                }elseif($user_parent_iv){
                    $my_participate[$key]['event_status'] = 3;
                }elseif($user_participant){
                    $my_participate[$key]['event_status'] = 4;
                }
            }

            return [
                'user_profile' => $model,
                'user_rating_position' => $user_rating_place,
                'user_rating' => $get_user_rating,
                'my_participate' => $my_participate,
            ];

        }
        else{
            return [];
        }
    }

    public function actionUserArchiveSearch($q = null, $user_id = null){
        if($q !== null && !empty($q) && $user_id !== null && !empty($user_id)){
            $q = (string)$q;
            $q = stripslashes($q);
            $user_id = (int)$user_id;

            // Находим данные пользователя
            $model = Users::find()->where(['id' => $user_id])->one();

            // Пропускаем его пароль
            unset($model->password);

            $rating_data = EventRating::find()
                ->select(['id', 'user_id', 'count'])
                ->asArray()
                ->orderBy(['count' => SORT_DESC])
                ->all();

            $user_rating_place = 0;
            $counter = 0;

            foreach ($rating_data as $item) {
                $counter += 1;
                if ($item['user_id'] === $user_id) {
                    $user_rating_place = $counter;
                }
            }

            $get_user_rating = EventRating::find()->where(['user_id' => $user_id])->count();

            // Все события пользователя которые закончились
            $event_archives = Events::find()
                ->where(['user_id' => $user_id, 'status' => 2])
                ->andWhere(new \yii\db\Expression('title LIKE :param', [':param' => '%' . $q . '%']))
                ->asArray()
                ->all();

            // Все события пользователя где он принимал участие
            $participant_archives = Events::find()
                ->select(['events.*', 'ep.participant_id'])
                ->innerJoin('event_participation ep', 'events.id=ep.event_id AND ep.participant_id=' . $user_id . ' AND ep.status=2')
                ->where(['events.status' => 2])
                ->andWhere(new \yii\db\Expression('events.title LIKE :param', [':param' => '%' . $q . '%']))
                ->asArray()
                ->all();

            // Объединяем массивы в единый массыв
            $my_archive = array_merge($event_archives, $participant_archives);

            // Запускаем луп и присваиваем ключ и значение true/false по результату завершившихся событий
            foreach ($my_archive as $key=> $item) {
                $event_winners = EventWinners::find()->where(['event_id' => $item['id']])->one();
                $my_archive[$key]['event_status'] = 5;
                if ($event_winners) {
                    if((int)$event_winners->winner_user_id === (int)$user_id){
                        $my_archive[$key]['winner'] = true;
                    }else {
                        $my_archive[$key]['winner'] = false;
                    }
                }
            }

            return [
                'user_profile' => $model,
                'user_rating_position' => $user_rating_place,
                'user_rating' => $get_user_rating,
                'my_archive' => $my_archive,
            ];
        }
        elseif($q !== null && !empty($q)) {
            $q = (string)$q;
            $q = stripslashes($q);

            // Находим данные пользователя
            $model = $this->findModel();

            // Пропускаем его пароль
            unset($model->password);

            $rating_data = EventRating::find()
                ->select(['id', 'user_id', 'count'])
                ->asArray()
                ->orderBy(['count' => SORT_DESC])
                ->all();

            $user_rating_place = 0;
            $counter = 0;

            foreach ($rating_data as $item) {
                $counter += 1;
                if ($item['user_id'] === $this->token->id) {
                    $user_rating_place = $counter;
                }
            }

            $get_user_rating = EventRating::find()->where(['user_id' => $this->token->id])->count();

            // Все события пользователя которые закончились
            $event_archives = Events::find()
                ->where(['user_id' => $this->token->id, 'status' => 2])
                ->andWhere(new \yii\db\Expression('title LIKE :param', [':param' => '%' . $q . '%']))
                ->asArray()
                ->all();

            // Все события пользователя где он принимал участие
            $participant_archives = Events::find()
                ->select(['events.*', 'ep.participant_id'])
                ->innerJoin('event_participation ep', 'events.id=ep.event_id AND ep.participant_id=' . $this->token->id . ' AND ep.status=2')
                ->where(['events.status' => 2])
                ->andWhere(new \yii\db\Expression('events.title LIKE :param', [':param' => '%' . $q . '%']))
                ->asArray()
                ->all();

            // Объединяем массивы в единый массыв
            $my_archive = array_merge($event_archives, $participant_archives);

            // Запускаем луп и присваиваем ключ и значение true/false по результату завершившихся событий
            foreach ($my_archive as $key=> $item) {
                $event_winners = EventWinners::find()->where(['event_id' => $item['id']])->one();
                $my_archive[$key]['event_status'] = 5;
                if ($event_winners) {
                    if((int)$event_winners->winner_user_id === (int)$this->token->id){
                        $my_archive[$key]['winner'] = true;
                    }else {
                        $my_archive[$key]['winner'] = false;
                    }
                }
            }

            return [
                'user_profile' => $model,
                'user_rating_position' => $user_rating_place,
                'user_rating' => $get_user_rating,
                'my_archive' => $my_archive,
            ];
        }
        else{
            return [];
        }
    }

    public function actionUpdate()
    {
        $model = $this->findModel();

        $password = $model->password;
        $status = $model->status;
        $created_at = $model->created_at;

        $model->load(Yii::$app->request->post(), '');

        $model->password = $password;
        $model->status = $status;
        $model->created_at = $created_at;

        if ($model->save()) {
            unset($model->password);
        }

        return $model;
    }

    public function actionUserProfile($id)
    {
        // Находим данные пользователя
        $model = Users::find()->where(['id' => $id])->one();

        // Пропускаем его пароль
        unset($model->password);

//        // Активные события на которых пользователь голосовал
//        $my_votes = EventUserVotes::find()
//            ->select(['id', 'user_id', 'event_id'])
//            ->with(['event' => function ($query) {
//                $query->where(['status' => 1])->andWhere(['in_voting' => 1]);
//            }])
//            ->where(['user_id' => $model->id])
//            ->asArray()
//            ->all();

        // Активные события на которых пользователь голосовал
        $my_votes = Events::find()
            ->alias('e')
            ->select(['e.*', 'ev.id as vote_id', 'CONCAT("1") as event_status'])
            ->innerJoin('event_user_votes ev', 'e.id=ev.event_id')
            ->where(['ev.user_id' => $model->id])
            ->andWhere(['e.status' => 1, 'in_voting' => 1])
            ->asArray()
            ->all();

        // Активные события, где пользователь принимает участие
        $me_participate = EventParticipation::find()
            ->select([
                'e.*'
            ])
            ->innerJoin('events e', 'e.id=event_participation.event_id')
            ->where(['participant_id' => $model->id, 'event_participation.status' => 2, 'e.status' => 1])
            ->asArray()
            ->all();

        // Активные события самого пользователя
        $my_events = Events::find()
            ->where(['user_id' => $model->id, 'status' => 1])->asArray()->all();

        // Объединяем массивы в единый массыв
        $my_participate = array_merge($me_participate, $my_events);

        // Все события пользователя которые закончились
        $event_archives = Events::find()
            ->where(['user_id' => $model->id, 'status' => 2])->asArray()->all();

        // Все события пользователя где он принимал участие
        $participant_archives = Events::find()
            ->select(['events.*', 'ep.participant_id'])
            ->innerJoin('event_participation ep', 'events.id=ep.event_id AND ep.participant_id=' . $model->id . ' AND ep.status=2')
            ->where(['events.status' => 2])
            ->asArray()->all();

        // Объединяем массивы в единый массыв
        $my_archive = array_merge($event_archives, $participant_archives);

        // Запускаем луп и присваиваем ключ и знаечение true на событие где пользователь голосует (не участвует и не события самого пользователя)
//        foreach ($my_votes as $key=> $item){
//            $my_votes[$key]['event_status'] = 1;
//        }

        // Запускаем пул на присвоение статусов к событиям
        foreach ($my_participate as $key=> $item){
            // Запрос на присвоение статуса "Поиск участника"
            $user_parent_so = EventParticipation::find()
                ->where(['event_id' => $item['id'], 'parent_id' => $item['user_id']])->one();

            // Событие которое создавал сам пользователь в голосовании
            $user_parent_iv = EventParticipation::find()
                ->where(['event_id' => $item['id'], 'parent_id' => $item['user_id'], 'status' => 2])->one();

            // Событие в котором участвуете пользователь находиться в голосовании
            $user_participant = EventParticipation::find()
                ->where(['event_id' => $item['id'], 'participant_id' => $item['user_id'], 'status' => 2])->one();

            // Проверяем на какой из состояний находиться событие
            if(!$user_parent_so){
                $my_participate[$key]['event_status'] = 2;
            }elseif($user_parent_iv){
                $my_participate[$key]['event_status'] = 3;
            }elseif ($user_participant){
                $my_participate[$key]['event_status'] = 4;
            }
        }

        // Запускаем луп и присваиваем ключ и знаечение true/false по результату завершившихся событий
        foreach ($my_archive as $key=> $item) {
            $event_winners = EventWinners::find()->where(['event_id' => $item['id']])->one();
            $my_archive[$key]['event_status'] = 5;
            if ($event_winners) {
                if((int)$event_winners->winner_user_id === (int)$model->id){
                    $my_archive[$key]['winner'] = true;
                }else {
                    $my_archive[$key]['winner'] = false;
                }
            }
        }

        $rating_data = EventRating::find()
            ->select(['id', 'user_id', 'count'])
            ->asArray()
            ->orderBy(['count' => SORT_DESC])
            ->all();

        $user_rating_place = 0;
        $counter = 0;

        foreach ($rating_data as $item){
            $counter += 1;
            if($item['user_id'] == (int)$model->id){
                $user_rating_place = $counter;
            }
        }

        $get_user_rating = EventRating::find()->where(['user_id' => $model->id])->count();

        return [
            'user_profile' => $model,
            'user_rating_position' => $user_rating_place,
            'user_rating' => $get_user_rating,
            'my_votes' => $my_votes,
            'my_participate' => $my_participate,
            'my_archive' => $my_archive,
        ];
    }

    protected function findModel()
    {
        $model = Users::findOne($this->token->id);

        if (($model) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    public function actionUploadPhoto()
    {
        $model = $this->findModel();
        $imageModel = new UploadForm();
        $imageModel->imageFile = UploadedFile::getInstanceByName('imageFile');

        if ($imageModel->imageFile !== null && $imageModel->imageFile->size > 0) {
            $old_photo = $model->photo;
            if ($imageModel->validate()) {
                $model->photo = $imageModel->fileName();
                if ($model->save()) {
                    return $imageModel->saveImage($this->imageFolder, $model->photo, $old_photo);
                    return $model->photo;
                } else {
                    return $model;
                }
            } else {
                return $imageModel;
            }
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'Image file not found.'));
        }
    }

    public function actionDeletePhoto()
    {
        $model = $this->findModel();

        MakeDir::remove('users/' . $model->photo);

        $model->photo = null;
        $model->save();

        return $model->photo;
    }

    public function actionNotify()
    {
        $userModel = Users::findOne(Yii::$app->request->post('id'));
        $fcm_id = $userModel->fcm_id ? $userModel->fcm_id : "some_token";
        if ($fcm_id) {
            MobileNotifyer::sendNotifyre("Внимание", "На ваш вызов откликнулись", $fcm_id, ['currentTime' => time()]);
//            MobileNotifyer::sendTopic("Внимание","На ваш вызов откликнулись", $fcm_id);
            echo $fcm_id;
        }
    }
}