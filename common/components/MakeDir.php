<?php

namespace common\components;

class MakeDir
{

    public static function mkDir($dir)
    {

        $cdn = \Yii::getAlias('@cdn');
        if (!is_dir($cdn . '/' . $dir)) {
            mkdir($cdn . '/' . $dir, 0777, true);
        }
    }

    public static function remove($imagePath)
    {
        $cdn = \Yii::getAlias('@cdn');
        if (file_exists($cdn . '/' . $imagePath))
            unlink($cdn . '/' . $imagePath);
    }

    public static function moveFolder($currentFolder, $destinationFolder)
    {
        $cdn = \Yii::getAlias('@cdn');
        if (file_exists($cdn . '/' . $currentFolder)) {
            rename($cdn . '/' . $currentFolder, $cdn . '/' . $destinationFolder);
        }
    }

}