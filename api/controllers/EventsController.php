<?php

namespace api\controllers;

use app\models\EventParticipation;
use app\models\EventRating;
use app\models\Events;
use app\models\EventsFields;
use app\models\EventTags;
use app\models\EventUserVotes;
use app\models\EventWinners;
use app\models\SuperTags;
use app\models\Tags;
use common\components\PublicApiController;
use yii\data\ActiveDataProvider;

class EventsController extends PublicApiController
{
    public $serializer = [
        'class' => 'common\components\MySerializer',
        'collectionEnvelope' => 'data',
    ];

    public function actionIndex($q = null)
    {
        $model = Events::find()
            ->select(['id', 'user_id', 'title', 'winType', 'privacy', 'gender', 'super_tag_id', 'rival_min_age', 'rival_max_age', 'description', 'videoFile', 'previewFile'])
            ->with(['user' => function ($query) {
                $query->select(['id', 'username', 'photo', 'first_name', 'about_me']);
            }])
            ->where(['events.status' => 1])
            ->andWhere(['events.in_voting' => 0]);

        if ($q !== null) {
            $q = (string)$q;
            $q = stripslashes($q);
            $tag = Tags::find()
                ->select(['id'])
                ->andWhere(new \yii\db\Expression('title LIKE :param', [':param' => '%' . $q . '%']))
                ->limit(50)
                ->asArray()
                ->all();
            if ($tag) {

                $tagsIds = [];
                foreach ($tag as $item) {
                    $tagsIds[] = $item['id'];
                }

                $model->innerJoin('event_tags et', 'et.event_id=events.id');
                $model->andWhere(['in', 'et.tag_id', $tagsIds]);

            } else {
                $superTag = SuperTags::find()->andWhere(new \yii\db\Expression('title LIKE :param', [':param' => '%' . $q . '%']))->one();
                if ($superTag) {
                    $model->andWhere(['super_tag_id' => $superTag->id]);
                } else {
                    return [
                        'data' => [],
                        'pagination' => (object)[]
                    ];
                }
            }
        }

        $activeData = new ActiveDataProvider([
            'query' => $model->asArray(),
            'pagination' => [
                'pageSize' => 10,
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ],
            ]
        ]);

        return $activeData;
    }

    public function actionCloseEvent()
    {
        // Присваеваем текущее время в переменную
        $current_time = time();

        // Собираем массив из событий где дедлайн голосования подошла к концу (то есть выделенное время - 24 часа)
        $check_data = Events::find()
            ->where(['privacy' => 1])
            ->andWhere(['status' => 1])
            ->andWhere(['<', 'voting_deadline', $current_time])
            ->all();

        // Если в массив попали события
        if ($check_data) {
            // Запускаем луп
            foreach ($check_data as $data) {
                // Получаем кол-во голосов по данному событию
                $check_votes_count = EventUserVotes::find()
                    ->where(['event_id' => $data->id])
                    ->count();

                // Голосов больше нуля то расчитываем
                if ($check_votes_count > 0) {
                    // Находим участника в событии
                    $check_participant = EventParticipation::find()
                        ->where(['event_id' => $data->id, 'parent_id' => $data->user_id, 'status' => 2])
                        ->one();

                    // Получаем кол-во голосов организатора события
                    $parent_votes_count = EventUserVotes::find()
                        ->where(['event_id' => $data->id, 'participant_id' => $data->user_id])
                        ->count();

                    // Получаем кол-во голосов участника события
                    $participant_votes_count = EventUserVotes::find()
                        ->where(['event_id' => $data->id, 'participant_id' => $check_participant->participant_id])
                        ->count();

                    // Сравнение голосов "Организатор" больше чем "Участник"
                    if ($parent_votes_count > $participant_votes_count) {
                        // Создаем экземпляр модели и сохраняем данные
                        $winner_store = new EventWinners();
                        $winner_store->event_id = $data->id;
                        $winner_store->winner_user_id = $data->user_id;
                        $winner_store->loser_user_id = $check_participant->participant_id;
                        $winner_store->event_type = $data->privacy;
                        $winner_store->save();

                        // Получаем огранизатора с таблицы рейтингов
                        $check_user_rating = EventRating::find()
                            ->where(['user_id' => $data->user_id])
                            ->one();

                        // Проверяем если огранизатор есть в таблице то присваиваем к его рейтингу + 1 за победу
                        if ($check_user_rating) {
                            $update_rating_data = EventRating::findOne($check_user_rating->id);
                            $update_rating_data->count = +1;
                            $update_rating_data->save();
                        } // Иначе добавляем огранизатора в таблицу рейтинга и добавляем + 1 за победу
                        else {
                            $user_rating_store = new EventRating();
                            $user_rating_store->user_id = $data->user_id;
                            $user_rating_store->count = +1;
                            $user_rating_store->save();
                        }

                        // Меняем статус события как закончен и сохраняем
                        $data->status = 2;
                        $data->save();

                        return true;
                    } // Сравнение голосов "Участник" больше чем "Организатор"
                    elseif ($participant_votes_count > $parent_votes_count) {
                        // Создаем экземпляр модели и сохраняем данные
                        $winner_store = new EventWinners();
                        $winner_store->event_id = $data->id;
                        $winner_store->winner_user_id = $check_participant->participant_id;
                        $winner_store->loser_user_id = $data->user_id;
                        $winner_store->event_type = $data->privacy;
                        $winner_store->save();

                        // Получаем участника с таблицы рейтингов
                        $check_user_rating = EventRating::find()
                            ->where(['user_id' => $check_participant->participant_id])
                            ->one();

                        // Проверяем если участник есть в таблице то присваиваем к его рейтингу + 1 за победу
                        if ($check_user_rating) {
                            $update_rating_data = EventRating::findOne($check_user_rating->id);
                            $update_rating_data->count = +1;
                            $update_rating_data->save();
                        } // Иначе добавляем участника в таблицу рейтинга и добавляем + 1 за победу
                        else {
                            $user_rating_store = new EventRating();
                            $user_rating_store->user_id = $check_participant->participant_id;
                            $user_rating_store->count = +1;
                            $user_rating_store->save();
                        }

                        // Меняем статус события как закончен и сохраняем
                        $data->status = 2;
                        $data->save();

                        return true;
                    } // Сравнение голосов "Организатор" равно "Участник"
                    elseif ($parent_votes_count === $participant_votes_count) {
                        // То присваиваем ещё 12 часов к голосованию и сохраняем
                        // TODO - необходимо добавить дополнительное поле, которое будет говорить о том, что голосование было продлено
                        $data->voting_deadline = $data->voting_deadline + (12 * 3600);
                        $data->save();

                        return true;
                    }
                } // Иначе присваиваем ещё 12 часов к голосованию и сохраняем
                else {
                    // TODO - необходимо указать, что голосов не было и вреся на голосование было продлено
                    $data->voting_deadline = $data->voting_deadline + (12 * 3600);
                    $data->save();

                    return true;
                }
                // Провераем тип события на Privacy = 2 (приватный)
//                elseif ($data->privacy === 2){
//                    // Находим участника в событии
//                    $check_participant = EventParticipation::find()
//                        ->where(['event_id' => $data->id, 'parent_id' => $data->user_id, 'status' => 2])
//                        ->one();
//
//                    // Присваиваем супертег ID в переменную
//                    $is_supertag = $data->super_tag_id;
//                    // Проверяем создавалась ли событие по супертегу
//                    if($is_supertag){
//                        // Получаем вариант расчёта супертега
//                        $get_supertag = SuperTags::find()
//                            ->where(['id' => $is_supertag])
//                            ->one();
//
//                        // Если win_type = 1 то расчитываем по "у кого цифра больше"
//                        if($get_supertag->win_type === 1){
//                            // Сравнение результат "Организатор" больше чем "Участник"
//                            if($data->result_data > $check_participant->result_data_participant){
//                                // Создаем экземпляр модели и сохраняем данные
//                                $winner_store = new EventWinners();
//                                $winner_store->event_id = $data->id;
//                                $winner_store->winner_user_id = $data->user_id;
//                                $winner_store->loser_user_id = $check_participant->participant_id;
//                                $winner_store->event_type = $data->privacy;
//                                $winner_store->save();
//
//                                // Получаем огранизатора с таблицы рейтингов
//                                $check_user_rating = EventRating::find()
//                                    ->where(['user_id' => $data->user_id])
//                                    ->one();
//
//                                // Проверяем если огранизатор есть в таблице то присваиваем к его рейтингу + 1 за победу
//                                if($check_user_rating){
//                                    $update_rating_data = EventRating::findOne($check_user_rating->id);
//                                    $update_rating_data->count = + 1;
//                                    $update_rating_data->save();
//                                }
//                                // Иначе добавляем огранизатора в таблицу рейтинга и добавляем + 1 за победу
//                                else{
//                                    $user_rating_store = new EventRating();
//                                    $user_rating_store->user_id = $data->user_id;
//                                    $user_rating_store->count = + 1;
//                                    $user_rating_store->save();
//                                }
//
//                                // Меняем статус события как закончен и сохраняем
//                                $data->status = 2;
//                                $data->save();
//                            }
//
//                            // Сравнение результат "Участник" больше чем "Организатор"
//                            elseif($check_participant->result_data_participant > $data->result_data){
//                                // Создаем экземпляр модели и сохраняем данные
//                                $winner_store = new EventWinners();
//                                $winner_store->event_id = $data->id;
//                                $winner_store->winner_user_id = $check_participant->participant_id;
//                                $winner_store->loser_user_id = $data->user_id;
//                                $winner_store->event_type = $data->privacy;
//                                $winner_store->save();
//
//                                // Получаем участника с таблицы рейтингов
//                                $check_user_rating = EventRating::find()
//                                    ->where(['user_id' => $check_participant->participant_id])
//                                    ->one();
//
//                                // Проверяем если участник есть в таблице то присваиваем к его рейтингу + 1 за победу
//                                if($check_user_rating){
//                                    $update_rating_data = EventRating::findOne($check_user_rating->id);
//                                    $update_rating_data->count = + 1;
//                                    $update_rating_data->save();
//                                }
//
//                                // Иначе добавляем участника в таблицу рейтинга и добавляем + 1 за победу
//                                else{
//                                    $user_rating_store = new EventRating();
//                                    $user_rating_store->user_id = $check_participant->participant_id;
//                                    $user_rating_store->count = + 1;
//                                    $user_rating_store->save();
//                                }
//
//                                // Меняем статус события как закончен и сохраняем
//                                $data->status = 2;
//                                $data->save();
//                            }
//
//                            // Сравнение результат "Организатор" равно "Участник"
//                            elseif ($data->result_data === $check_participant->result_data_participant){
//                                // То присваиваем ещё 24 часа к голосованию и сохраняем
//                                $data->voting_deadline = $data->voting_deadline + (12 * 3600);
//                                $data->save();
//                            }
//                        }
//
//                        // Если win_type = 2 то расчитываем по "у кого цифра меньше"
//                        elseif ($get_supertag->win_type === 2){
//                            if($data->result_data < $check_participant->result_data_participant){
//                                $winner_store = new EventWinners();
//                                $winner_store->event_id = $data->id;
//                                $winner_store->user_id = $data->user_id;
//                                $winner_store->event_type = $data->privacy;
//                                $winner_store->save();
//
//                                $check_user_rating = EventRating::find()
//                                    ->where(['user_id' => $data->user_id])
//                                    ->one();
//                                if($check_user_rating){
//                                    $update_rating_data = EventRating::findOne($check_user_rating->id);
//                                    $update_rating_data->count = + 1;
//                                    $update_rating_data->save();
//                                }else{
//                                    $user_rating_store = new EventRating();
//                                    $user_rating_store->user_id = $data->user_id;
//                                    $user_rating_store->count = + 1;
//                                    $user_rating_store->save();
//                                }
//
//                                $data->status = 2;
//                                $data->save();
//                            }
//                            elseif($check_participant->result_data_participant < $data->result_data){
//                                $winner_store = new EventWinners();
//                                $winner_store->event_id = $data->id;
//                                $winner_store->user_id = $check_participant->participant_id;
//                                $winner_store->event_type = $data->privacy;
//                                $winner_store->save();
//
//                                $check_user_rating = EventRating::find()
//                                    ->where(['user_id' => $check_participant->participant_id])
//                                    ->one();
//                                if($check_user_rating){
//                                    $update_rating_data = EventRating::findOne($check_user_rating->id);
//                                    $update_rating_data->count = + 1;
//                                    $update_rating_data->save();
//                                }else{
//                                    $user_rating_store = new EventRating();
//                                    $user_rating_store->user_id = $check_participant->participant_id;
//                                    $user_rating_store->count = + 1;
//                                    $user_rating_store->save();
//                                }
//
//                                $data->status = 2;
//                                $data->save();
//
//                                return $data;
//                            }
//                            elseif ($data->result_data === $check_participant->result_data_participant){
//                                $data->voting_deadline = $data->voting_deadline + (12 * 3600);
//                                $data->save();
//                            }
//                        }
//                    }
//                }
            }
        } // Иначе возвращаем пустой массив
        else {
            return [];
        }
    }

    public function actionGetRatingData()
    {
        $top_three = EventRating::find()
            ->select(['id', 'user_id', 'count'])
            ->with([
                'user' => function ($query) {
                    $query->select(['id', 'username', 'photo']);
                }])
            ->asArray()
            ->orderBy(['count' => SORT_DESC])
            ->limit(3)
            ->all();

        $others = EventRating::find()
            ->select(['id', 'user_id', 'count'])
            ->with([
                'user' => function ($query) {
                    $query->select(['id', 'username', 'photo']);
                }])
            ->asArray()
            ->offset(3)
            ->orderBy(['count' => SORT_DESC])
            ->limit(97)
            ->all();

        return [
            'top_three' => $top_three,
            'others' => $others,
        ];
    }

    public function actionDeleteEventData()
    {
        $current_time = time();

        $delete_data = Events::find()
            ->where(['status' => 1])
            ->andWhere(['in_voting' => 0])
            ->andWhere(['<', 'deadline', $current_time])
            ->all();

        $cdn = \Yii::getAlias('@cdn');

        foreach ($delete_data as $item) {
            $this->deleteDir($cdn . '/events_video/' . $item->id);

            $item->delete();
        }

        \Yii::$app->response->statusCode = 204;

        return;
    }

    private function deleteDir($dirPath)
    {
        if (!is_dir($dirPath)) {

        } else {
            if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
                $dirPath .= '/';
            }
            $files = glob($dirPath . '*', GLOB_MARK);
            foreach ($files as $file) {
                if (is_dir($file)) {
                    self::deleteDir($file);
                } else {
                    unlink($file);
                }
            }
            rmdir($dirPath);
        }
    }

    public function actionVotes($q = null)
    {
        $model = Events::find()
            ->select(['id', 'user_id', 'title', 'winType', 'privacy', 'gender', 'rival_min_age', 'rival_max_age', 'description', 'videoFile', 'previewFile'])
            ->with(['user' => function ($query) {
                $query->select(['id', 'username', 'photo', 'first_name', 'about_me']);
            }])
            ->where(['events.status' => 1])
            ->andWhere(['events.in_voting' => 1])
            ->andWhere(['events.privacy' => 1]);

        if ($q !== null) {
            $q = (string)$q;
            $q = stripslashes($q);
            $tag = Tags::find()
                ->select(['id'])
                ->andWhere(new \yii\db\Expression('title LIKE :param', [':param' => '%' . $q . '%']))
                ->limit(50)
                ->asArray()
                ->all();
            if ($tag) {

                $tagsIds = [];
                foreach ($tag as $item) {
                    $tagsIds[] = $item['id'];
                }

                $model->innerJoin('event_tags et', 'et.event_id=events.id');
                $model->andWhere(['in', 'et.tag_id', $tagsIds]);

            } else {
                $superTag = SuperTags::find()->andWhere(new \yii\db\Expression('title LIKE :param', [':param' => '%' . $q . '%']))->one();
                if ($superTag) {
                    $model->andWhere(['super_tag_id' => $superTag->id]);
                } else {
                    return [
                        'data' => [],
                        'pagination' => (object)[]
                    ];
                }
            }
        }

        $activeData = new ActiveDataProvider([
            'query' => $model->asArray(),
            'pagination' => [
                'pageSize' => 10,
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ],
            ]
        ]);

        return $activeData;
    }

    public function actionArchived($q = null)
    {
        $model = Events::find()
            ->select(['id', 'user_id', 'title', 'winType', 'privacy', 'gender', 'rival_min_age', 'rival_max_age', 'description', 'videoFile', 'previewFile', 'super_tag_id'])
            ->with(['user' => function ($query) {
                $query->select(['id', 'username', 'photo', 'first_name', 'about_me']);
            }])
            ->where(['events.status' => 2]);

        if ($q !== null) {
            $q = (string)$q;
            $q = stripslashes($q);
            $tag = Tags::find()
                ->select(['id'])
                ->andWhere(new \yii\db\Expression('title LIKE :param', [':param' => '%' . $q . '%']))
                ->limit(50)
                ->asArray()
                ->all();
            if ($tag) {

                $tagsIds = [];
                foreach ($tag as $item) {
                    $tagsIds[] = $item['id'];
                }

                $model->innerJoin('event_tags et', 'et.event_id=events.id');
                $model->andWhere(['in', 'et.tag_id', $tagsIds]);

            } else {
                $superTag = SuperTags::find()->andWhere(new \yii\db\Expression('title LIKE :param', [':param' => '%' . $q . '%']))->one();
                if ($superTag) {
                    $model->andWhere(['super_tag_id' => $superTag->id]);
                } else {
                    return [
                        'data' => [],
                        'pagination' => (object)[]
                    ];
                }
            }
        }

        $activeData = new ActiveDataProvider([
            'query' => $model->asArray(),
            'pagination' => [
                'pageSize' => 10,
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ],
            ]
        ]);

        return $activeData;
    }

    public function actionVotingView($id)
    {
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
        ];
    }

    public function actionArchivedView($id)
    {
        $check_event_privicy = Events::find()->where(['id' => $id])->one();

        if ($check_event_privicy->privacy === 1) {
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

            $model = Events::find()
                ->with([
                    'superTag' => function ($query) {
                        $query->select(['title']);
                    },
                ])
                ->where(['id' => $id])
                ->andWhere(['in_voting' => 1])
                ->andWhere(['status' => 2])
                ->asArray()
                ->one();

            $event_winner_data = EventWinners::find()->where(['event_id' => $id])->one();

            if ($event_winner_data) {
                $parent_votes = EventUserVotes::find()
                    ->where(['event_id' => $id, 'participant_id' => $event['parent_id']])->count();

                if ($event_winner_data->winner_user_id === (int)$event['parent_id']) {
                    $winner_parent = true;
                } else {
                    $winner_parent = false;
                }

                $participant_votes = EventUserVotes::find()
                    ->where(['event_id' => $id, 'participant_id' => $event['participant_id']])->count();

                if ($event_winner_data->winner_user_id === (int)$event['participant_id']) {
                    $winner_participant = true;
                } else {
                    $winner_participant = false;
                }

                $eventArchivedData = [
                    'parent' => [
                        'id' => $event['parent_id'],
                        'username' => $event['parent']['username'],
                        'photo' => $event['parent']['photo'],
                        'videoFile' => $event['event']['videoFile'],
                        'previewFile' => $event['event']['previewFile'],
                        'voteCount' => (int)$parent_votes,
                        'winner' => $winner_parent
                    ],
                    'participant' => [
                        'id' => $event['participant_id'],
                        'username' => $event['participant']['username'],
                        'photo' => $event['participant']['photo'],
                        'videoFile' => $event['videoFile'],
                        'previewFile' => $event['previewFile'],
                        'voteCount' => (int)$participant_votes,
                        'winner' => $winner_participant
                    ],
                ];

                return [
                    'users_data' => $eventArchivedData,
                    'event_data' => $model,
                ];
            }
        }
        elseif ($check_event_privicy->privacy === 2) {
            $event = EventParticipation::find()
                ->select(['event_id', 'parent_id', 'participant_id', 'videoFile', 'previewFile', 'result_data_participant'])
                ->with([
                    'parent' => function ($query) {
                        $query->select(['username', 'photo']);
                    },
                    'participant' => function ($query) {
                        $query->select(['username', 'photo']);
                    },
                    'event' => function ($query) {
                        $query->select(['title', 'videoFile', 'previewFile', 'result_data']);
                    },
                ])
                ->where(['event_id' => $id, 'status' => 2])
                ->asArray()
                ->one();

            $model = Events::find()
                ->where(['id' => $id])
                ->andWhere(['status' => 2])
                ->asArray()
                ->one();

            $superTag = SuperTags::findOne($model['super_tag_id']);
            if ($superTag) {
                $model['superTag'] = $superTag->title;
                $model['superTagFields'] = [];

                $eventFields = [];
                $eventFieldsModel = EventsFields::findAll(['event_id' => $model['id']]);
                if ($eventFieldsModel) {
                    foreach ($eventFieldsModel as $item) {
                        $eventFields[] = [
                            'title' => $item->field->title,
                            'value' => $item->value,
                        ];
                    }
                    if (count($eventFields) > 0) {
                        $model['superTagFields'] = $eventFields;
                    }
                }
            }


            $event_winner_data = EventWinners::find()->where(['event_id' => $id])->one();

            if ($event_winner_data) {

                if ($event_winner_data->winner_user_id === (int)$event['parent_id']) {
                    $winner_parent = true;
                } else {
                    $winner_parent = false;
                }

                if ($event_winner_data->winner_user_id === (int)$event['participant_id']) {
                    $winner_participant = true;
                } else {
                    $winner_participant = false;
                }

                $eventArchivedData = [
                    'parent' => [
                        'id' => $event['parent_id'],
                        'username' => $event['parent']['username'],
                        'photo' => $event['parent']['photo'],
                        'videoFile' => $event['event']['videoFile'],
                        'previewFile' => $event['event']['previewFile'],
                        'resultData' => $event['event']['result_data'],
                        'winner' => $winner_parent
                    ],
                    'participant' => [
                        'id' => $event['participant_id'],
                        'username' => $event['participant']['username'],
                        'photo' => $event['participant']['photo'],
                        'videoFile' => $event['videoFile'],
                        'previewFile' => $event['previewFile'],
                        'resultData' => $event['result_data_participant'],
                        'winner' => $winner_participant
                    ],
                ];

                return [
                    'users_data' => $eventArchivedData,
                    'event_data' => $model,
                ];
            }
        }

        return [];
    }

    public
    function actionView($id)
    {
        $model = $this->findModel($id);

        if ($model['super_tag_id']) {
            $superTag = SuperTags::findOne($model['super_tag_id']);
            if ($superTag) {
                $model['superTag'] = $superTag->title;
                $model['superTagFields'] = [];

                $eventFields = [];
                $eventFieldsModel = EventsFields::findAll(['event_id' => $model['id']]);
                if ($eventFieldsModel) {
                    foreach ($eventFieldsModel as $item) {
                        $eventFields[] = [
                            'title' => $item->field->title,
                            'value' => $item->value,
                        ];
                    }
                    if (count($eventFields) > 0) {
                        $model['superTagFields'] = $eventFields;
                    }
                }
            }
        }

        $eventTags = EventTags::find()
            ->where(['event_id' => $model['id']])->all();
        if ($eventTags) {
            foreach ($eventTags as $item) {
                $model['tags'][] = $item->tag->title;
            }
        }

        return $model;
    }

    protected
    function findModel($id)
    {
        $model = Events::find()
            ->where(['id' => (int)$id])
            ->with([
                'user' => function ($query) {
                    $query->select(['username', 'photo', 'first_name', 'about_me']);
                },
            ])->asArray()->one();

        if (($model) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}