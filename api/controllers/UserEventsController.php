<?php

namespace api\controllers;

use app\models\Events;
use app\models\EventsFields;
use app\models\EventTags;
use app\models\SuperTags;
use app\models\SuperTagsFields;
use app\models\Tags;
use common\components\ApiFrontend;
use common\components\MakeDir;
use common\models\UploadForm;
use yii\db\Exception;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use Yii;

class UserEventsController extends ApiFrontend
{
    public function actionIndex()
    {
        return Events::find()
            ->where(['user_id' => $this->token->id])
            ->andWhere(['status' => 1])
            ->all();
    }

    public function actionArchived()
    {
        return Events::find()
            ->where(['user_id' => $this->token->id])
            ->andWhere(['status' => 2])
            ->all();
    }

    public function actionCreate()
    {
        $model = new Events();
        $tags = Yii::$app->request->post('tags');
        $super_tag_fields = Yii::$app->request->post('super_tag_fields');
        $model->load(Yii::$app->request->post(), '');

        $fileModel = new UploadForm();
        $fileModel->video_file = UploadedFile::getInstanceByName('video_file');

        $previewModel = new UploadForm();
        $previewModel->imageFile = UploadedFile::getInstanceByName('preview_file');

        $model->user_id = $this->token->id;
        $model->created_at = time();
        $model->deadline = time() + (48 * 3600);
        $model->winType = 1;
        if ($model->validate()) {
            if (!$fileModel->validate())
                return $fileModel;

            if (!$previewModel->validate())
                return $previewModel;


            $cdn = Yii::getAlias('@cdn');
            $model->videoFile = md5($model->user_id . time()) . '.' . $fileModel->video_file->extension;
            $model->previewFile = md5($model->user_id . time()) . '.' . $previewModel->imageFile->extension;

            if ($model->save(false)) {
                MakeDir::mkDir('events_video/' . $model->id);
                $fileModel->video_file->saveAs($cdn . '/events_video/' . $model->id . '/' . $model->videoFile);
                $previewModel->imageFile->saveAs($cdn . '/events_video/' . $model->id . '/' . $model->previewFile);
            }

            try {
                if (isset($super_tag_fields)) {
                    $super_tag_fields = (array)json_decode($super_tag_fields);

                    if (count($super_tag_fields) > 0) {
                        $eventFields = [];
                        foreach ($super_tag_fields as $field) {
                            $searchFields = SuperTagsFields::findOne(['id' => (int)$field->id]);
                            if ($searchFields) {
                                $eventFields[$searchFields->id] = [
                                    'event_id' => $model->id,
                                    'field_id' => (int)$searchFields->id,
                                    'value' => (string)$field->value,
                                ];
                            }
                        }
                        if (count($eventFields) > 0) {
                            $db = Yii::$app->db;
                            $sql = $db->queryBuilder->batchInsert(EventsFields::tableName(), ['event_id', 'field_id', 'value'], $eventFields);
                            $db->createCommand($sql . ' ON DUPLICATE KEY UPDATE value=value')->execute();
                        }
                    }
                }
            } catch (Exception $e) {

            }

            $eventTags = [];
            if ($model->super_tag_id) {
                $superTags = SuperTags::findOne($model->super_tag_id);
                if ($superTags) {
                    $tagsModel = Tags::findOne(['title' => $superTags->title]);
                    if ($tagsModel) {
                        $eventTags[] = [
                            'tag_id' => $tagsModel->id,
                            'event_id' => $model->id,
                        ];
                    } else {
                        $tagsModel = new Tags();
                        $tagsModel->title = $superTags->title;
                        if ($tagsModel->save()) {
                            $eventTags[] = [
                                'tag_id' => $tagsModel->id,
                                'event_id' => $model->id,
                            ];
                        }
                    }
                }
            }

            try {
                if (isset($tags)) {
                    $tags = (array)json_decode($tags);

                    if (count($tags) > 0) {

                        $modelTags = new Tags();
                        foreach ($tags as $tag) {
                            $searchTag = Tags::findOne(['title' => (string)$tag]);
                            if ($searchTag) {
                                $eventTags[] = [
                                    'tag_id' => $searchTag->id,
                                    'event_id' => $model->id,
                                ];
                            } else {
                                $modelTags->title = (string)$tag;
                                if ($modelTags->save()) {
                                    $eventTags[] = [
                                        'tag_id' => $modelTags->id,
                                        'event_id' => $model->id,
                                    ];
                                    $modelTags->setIsNewRecord(true);
                                    unset($modelTags->id);
                                }
                            }
                        }
                    }
                }

            } catch (Exception $e) {

            }

            if (count($eventTags) > 0) {
                $db = Yii::$app->db;
                $sql = $db->queryBuilder->batchInsert(EventTags::tableName(), ['tag_id', 'event_id'], $eventTags);
                $db->createCommand($sql . ' ON DUPLICATE KEY UPDATE tag_id=tag_id')->execute();
            }

            Yii::$app->response->statusCode = 204;
            return;
        }

        return $model;
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        return $model;
    }

    public function actionView($id)
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

    protected function findModel($id)
    {
        $model = Events::find()->where(['id' => (int)$id])->asArray()->one();

        if (($model) !== null && (int)$model['user_id'] !== $this->token->id) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}