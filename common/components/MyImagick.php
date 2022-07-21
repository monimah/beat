<?php

namespace common\components;

use Imagick;

class MyImagick extends Imagick
{
    public function resize($width, $height = 0, $crop = false, $isWatermark = false, $watermarkFile = 'watermark.png', $xOffset = 80, $yOffset = 65)
    {
        $imageFormat = $this->getImageFormat();
        if ($crop === true) {

            $geo = $this->getImageGeometry();
            if (($geo['width'] / $width) < ($geo['height'] / $height)) {
                $this->cropImage($geo['width'], floor($height * $geo['width'] / $width), 0, (($geo['height'] - ($height * $geo['width'] / $width)) / 2));
            } else {
                $this->cropImage(ceil($width * $geo['height'] / $height), $geo['height'], (($geo['width'] - ($width * $geo['height'] / $height)) / 2), 0);
            }
            $this->thumbnailImage($width, $height);
        } else {
            $geo = $this->getImageGeometry();
            if ($height === 0) $height = $geo['height'];
            $this->thumbnailImage($width, $height, true);
        }
        $this->setImageFormat('jpg');
        $this->setImageResolution(72, 72);
        $this->stripImage();
        $this->setImageFormat($imageFormat);

        if ($imageFormat === 'jpg' || $imageFormat === 'jpeg') {
            $this->thumbnailImage(Imagick::COMPRESSION_JPEG);
            $this->setInterlaceScheme(Imagick::INTERLACE_JPEG);
            $this->setColorspace(Imagick::COLORSPACE_SRGB);
            $this->setSamplingFactors(array('2x2', '1x1', '1x1'));
        }
        $this->setImageCompressionQuality(95);

        if ($isWatermark) {
            $watermark = \Yii::getAlias('@cdn') . '/' . $watermarkFile;
            $watermark = new Imagick($watermark);
            $position = $this->gravity2coordinates($watermark, 'lowerRight', $xOffset, $yOffset);
            $this->compositeImage($watermark, $watermark->getImageCompose(), $position['x'], $position['y']);
        }
    }

    public function gravity2coordinates($watermark, $gravity, $xOffset = 0, $yOffset = 0)
    {
        $geo = $this->getImageGeometry();
        $waterMarkGeo = $watermark->getImageGeometry();

        switch ($gravity) {
            case 'upperLeft':
                $x = $xOffset;
                $y = $yOffset;
                break;

            case 'upperRight':
                $x = $geo['width'] - $waterMarkGeo['width'] - $xOffset;
                $y = $yOffset;
                break;

            case 'lowerRight':
                $x = $geo['width'] - $waterMarkGeo['width'] - $xOffset;
                $y = $geo['height'] - $waterMarkGeo['height'] - $yOffset;
                break;

            case 'lowerLeft':
                $x = $xOffset;
                $y = $geo['height'] - $waterMarkGeo['height'] - $yOffset;
                break;
        }
        return array(
            'x' => $x,
            'y' => $y
        );
    }


    public function save($imagePath)
    {
        $cdn = \Yii::getAlias('@cdn');
        $this->writeImage($cdn . '/' . $imagePath);
        $this->clear();
        $this->destroy();
    }

    public function remove($imagePath)
    {
        $cdn = \Yii::getAlias('@cdn');
        if (file_exists($cdn . '/' . $imagePath))
            unlink($cdn . '/' . $imagePath);
    }
}