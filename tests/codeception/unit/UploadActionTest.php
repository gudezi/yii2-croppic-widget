<?php

namespace tests\codeception\unit;

/**
 * @author Gustavo Dezi
 * @link   <gudezi@gmail.com>
 */

use Yii;
use yii\helpers\Json;
use Codeception\Specify;
use yii\helpers\FileHelper;
use org\bovigo\vfs\vfsStream;

class UploadActionTest extends TestCase
{
    use Specify;

    protected function _after()
    {
        unset($_SERVER['REQUEST_METHOD']);
        unset($_FILES);
    }

    /**
     * @expectedException        yii\base\InvalidConfigException
     * @expectedExceptionMessage Atributo "temppath" no puede estar vacío
     */
    public function testEmptyPath()
    {
        Yii::$app->runAction('test/upload-empty-path');
    }

    /**
     * @expectedException        yii\base\InvalidConfigException
     * @expectedExceptionMessage Atributo "tempUrl" no puede estar vacío
     */
    public function testEmptyUrl()
    {
        Yii::$app->runAction('test/upload-empty-url');
    }

    /**
     * @expectedException        yii\base\InvalidConfigException
     * @expectedExceptionMessage El "modelo" atributo no es una instancia 
     * de la clase "yii\base\Model"
     */
    public function testModelInstanceofClass()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->runAction('test/upload-model-instanceof-class');
    }

    public function testUploadImage()
    {
        $path = vfsStream::url(parent::ROOT_DIR . '/' . parent::IMG_DIR . '/img.jpeg');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_FILES = [
            'img' => [
                'name' => 'img.jpeg',
                'type' => FileHelper::getMimeType($path),
                'size' => filesize($path),
                'tmp_name' => $path,
                'error' => UPLOAD_ERR_OK
            ]
        ];

        $this->specify('Error no se carga la imagen (extensión de archivo no válido)', function () {
            $json = [
                'status' => 'error',
                'message' => 'Permite la descarga de sólo archivos con las siguientes extensiones: png.',
            ];

            expect('No se puede guardar la imagen', Yii::$app->runAction('test/upload-error'))
                ->equals(Json::encode($json));
        });

        $this->specify('Carga de imágenes exitosa', function () {
            $json = [
                'status' => 'success',
                'url' => '/img/temp/img.jpeg',
                'width' => 100,
                'height' => 100
            ];

            expect('La imagen se ha guardado correctamente', Yii::$app->runAction('test/upload'))
                ->equals(Json::encode($json));
        });
    }
}
