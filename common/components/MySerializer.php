<?php

namespace common\components;

use yii\rest\Serializer;

class MySerializer extends Serializer
{
    public function serialize($data)
    {
        $res = parent::serialize($data);
        if(!is_array($res)) return $res;

        if (isset($res['_meta'])) {
            $res['pagination'] = $res['_meta'];
            unset($res['_meta']);
            unset($res['_links']);
            return array_merge($res);
        }else{
            return $res;
        }
    }
}