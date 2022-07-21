<?php

namespace api\controllers;

use app\models\EventParticipation;
use common\components\ApiFrontend;

class NotificationsController extends ApiFrontend
{
    public function actionIndex()
    {
        $user_id = $this->token->id;

        $user_participations = EventParticipation::find()
            ->select(['id','event_id','participant_id', 'status'])
            ->with(['participant'=>function($query){
                $query->select(['id', 'username','photo']);
            }, 'event' => function($query){
                $query->select(['id', 'title']);
            }])
            ->where(['parent_id' => $user_id])
            ->orderBy(['id'=>SORT_DESC])
            ->asArray()
            ->all();

        return $user_participations;
    }
}
