<?php

namespace common\models;

use common\components\MakeDir;
use common\components\MyImagick;
use yii\base\Model;
use yii\web\UploadedFile;

class UploadForm extends Model
{
    /**
     * @var UploadedFile[]
     */
    public $imageFile;
    public $imageFiles;
    public $imageFileMobile;
    public $video_file;

    public function rules()
    {
        return [
            [['video_file'], 'file', 'skipOnEmpty' => true, 'extensions' => 'mp4, h264, h265', 'maxSize' => 1073741824],
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, svg, gif', 'maxSize' => 31457280],
            [['imageFiles'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, svg, gif', 'maxSize' => 31457280, 'maxFiles' => 12],];
    }

    public function saveImage($folder, $filename, $old_file = null, $width = 512, $height = 512, $crop = false)
    {
        MakeDir::mkDir($folder);
        if ($this->imageFile->getExtension() !== 'svg') {
            $filename = $folder . '/' . $filename;
            $this->saveWithImagick($this->imageFile->tempName, $filename, $width, $height, $crop);
        } else {
            $cdn = \Yii::getAlias('@cdn');
            $this->imageFile->saveAs($cdn . '/' . $folder . '/' . $filename);
        }
        if ($old_file) {
            MakeDir::remove($folder . '/' . $old_file);
        }

    }

    public function saveImages($folder, $width = 1000, $height = 1000, $crop = false)
    {
        MakeDir::mkDir($folder);
        $images = [];
        foreach ($this->imageFiles as $imageFile) {

            $filename = $this->fileFromTmpName($imageFile);

            $images[] = ['img' => $filename];

            if ($imageFile->getExtension() !== 'svg') {
                $filename = $folder . '/' . $filename;
                $this->saveWithImagick($imageFile->tempName, $filename, $width, $height, $crop);
            } else {
                $cdn = \Yii::getAlias('@cdn');
                $imageFile->saveAs($cdn . '/' . $folder . '/' . $filename);
            }
        }

        return $images;
    }

    function saveWithImagick($tempName, $filePath, $width, $height, $crop = false)
    {
        $im = new MyImagick($tempName);
        $im->resize($width, $height, $crop);
        $im->save($filePath);
    }

    public function fileName()
    {
        return md5(time() . $this->imageFile->tempName) . '.' . $this->imageFile->getExtension();
    }

    public function fileFromTmpName($imageFile)
    {
        return md5(time() . $imageFile->tempName) . '.' . $imageFile->getExtension();
    }
}