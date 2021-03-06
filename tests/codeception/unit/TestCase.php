<?php

namespace tests\codeception\unit;

/**
 * @author Gustavo Dezi
 * @link   <gudezi@gmail.com>
 */

use Yii;
use org\bovigo\vfs\vfsStream;

/**
 * @inheritdoc
 */
class TestCase extends \yii\codeception\TestCase
{
    const ROOT_DIR = 'root';
    const IMG_DIR = 'img';
    const SAVE_TEMP_DIR = 'temp';
    const SAVE_CROPPED_DIR = 'cropped';

    public $appConfig = '@tests/codeception/config/unit.php';

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $root = vfsStream::setup(self::ROOT_DIR, null, [
            self::IMG_DIR => [
                self::SAVE_TEMP_DIR => [],
                self::SAVE_CROPPED_DIR => [],
            ],
        ]);

        $this->createVirtualJpegImage(
            vfsStream::url(self::ROOT_DIR . '/' . self::IMG_DIR . '/img.jpeg')
        );

        Yii::setAlias('webroot', vfsStream::url(self::ROOT_DIR));
    }

    /**
     * Se crea una imagen JPEG virtual.
     *
     * @method createVirtualJpegImage
     * @param  string                 $path ruta de la imagen en el sistema de archivos virtual
     */
    protected function createVirtualJpegImage($path)
    {
        ob_start();
        $image = imagecreate(100, 100);
        $color = imagecolorallocate($image, 0, 0, 255);
        imagecolorallocate($image, 255, 255, 255);
        imagestring($image, 1, 5, 5, 'Test image', $color);
        imagejpeg($image);
        $imageRawData = ob_get_contents();
        ob_end_clean();
        file_put_contents($path, $imageRawData);
    }
}
