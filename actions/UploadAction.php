<?php

namespace gudezi\croppic\actions;

/**
 * @author Gustavo Dezi
 * @link   <gudezi@gmail.com>
 */

use Yii;
use yii\base\Action;
use yii\helpers\Json;
use yii\web\UploadedFile;
use yii\base\DynamicModel;
use yii\helpers\FileHelper;
use yii\base\InvalidCallException;
use yii\web\ForbiddenHttpException;
use yii\base\InvalidConfigException;

/**
 * Acción de clase para cargar las imágenes.
 *
 * el uso de:
 *
 * public function behaviors()
 * {
 *     return [
 *         'verbs' => [
 *             'class' => VerbFilter::className(),
 *             'actions' => [
 *                 'upload' => ['post'],
 *             ],
 *         ],
 *     ];
 * }
 *
 * public function actions()
 * {
 *     return [
 *         'upload' => [
 *             'class' => 'gudezi\croppic\actions\UploadAction',
 *             'tempPath' => '@frontend/web/img/temp',
 *             'tempUrl' => 'img/temp/',
 *             'validatorOptions' => [
 *                 'checkExtensionByMimeType' => true,
 *                 'extensions' => 'jpeg, jpg, png',
 *                 'maxSize' => 3000000,
 *                 'tooBig' => 'Ha seleccionado una imagen demasiado grande (máx. 3 MB)',
 *             ]
 *             'permissionRBAC' => 'updateProfile',
 *             'parameterRBAC' => 'profile'
 *         ],
 *     ];
 * }
 *
 * public function beforeAction($action)
 * {
 *     if ($action->id === 'upload' || $action->id === 'crop') {
 *         if ($action->hasProperty('model')) {
 *             $action->model = $this->findModel(Yii::$app->request->get('id'));
 *         }
 *     }
 *
 *     if (!parent::beforeAction($action)) {
 *         return false;
 *     }
 *
 *     return true;
 * }
 */
class UploadAction extends Action
{
    /**
     * La ruta absoluta al directorio en el que se descarga la imagen.
     *
     * @var string
     */
    public $tempPath;
    /**
     * URL que indica la ruta de acceso al directorio en el que se descarga la imagen.
     *
     * @var string
     */
    public $tempUrl;
    /**
     *Especifica si se debe generar un nombre único para la imagen cargada.
     *
     * @var boolean
     */
    public $uniqueName = true;
    /**
     * Reglas para el control de la imagen cargada.
     *
     * @var array
     */
    public $validatorOptions = [];
    /**
     * Un ejemplo que se utiliza para verificar el acceso a la página.
     *
     * @var string
     */
    public $model;
    /**
     * La autorización de RBAC para los controles de acceso, por ejemplo 'updateProfile'.
     *
     * Ejemplo:
     * Yii::$app->user->can('updateProfile', ['profile' => $this->model])
     *
     * @var string
     */
    public $permissionRBAC;
    /**
     * RBAC opción para verificar el acceso, por ejemplo 'profile'.
     *
     * Ejemplo:
     * Yii::$app->user->can('updateProfile', ['profile' => $this->model])
     *
     * @var string
     */
    public $parameterRBAC;

    /**
     * La ruta y el nombre de la imagen guardada.
     *
     * @var string
     */
    private $savedImage;

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Si el atributo 'temppath' está vacío.
        if ($this->tempPath === null) {
            throw new InvalidConfigException(
                'Atributo "temppath" no puede estar vacío.'
            );
        }
        $this->tempPath = rtrim(Yii::getAlias($this->tempPath), '/') . '/';

        // Si el atributo 'tempUrl' está vacío.
        if ($this->tempUrl === null) {
            throw new InvalidConfigException(
                'Atributo "tempUrl" no puede estar vacío.'
            );
        }
        $this->tempUrl = rtrim($this->tempUrl, '/') . '/';

        // Si el directorio no existe o no puede crearlo.
        if (!FileHelper::createDirectory($this->tempPath)) {
            throw new InvalidCallException(
                'El directorio especificado en el atributo "tempPath" no existe o no se puede crear.'
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        // Si se llena el atributo 'model'.
        if ($this->model !== null) {
            // Compruebe que es la instancia de clase "yii\base\Model".
            if (!($this->model instanceof \yii\base\Model)) {
                throw new InvalidConfigException(
                    'El atributo "model" no es una instancia de una clase "yii\base\Model".'
                );
            }
            // Si los atributos 'permissionRBAC' y 'parameterRBAC' estan llenos.
            if ($this->permissionRBAC !== null && $this->parameterRBAC !== null) {
                // Compruebe el acceso de los usuarios a la página.
                if (!Yii::$app->user->can($this->permissionRBAC, [$this->parameterRBAC => $this->model])) {
                    throw new ForbiddenHttpException(
                        'Usted no tiene acceso a esta página.'
                    );
                }
            }
        }

        // Obtener la foto.
        $image = UploadedFile::getInstanceByName('img');

        // Crear una instancia de la clase DynamicModel,
        // Definir los atributos de review.
        $model = new DynamicModel(compact('image'));
        $model->addRule('image', 'required')
            ->addRule('image', 'image', $this->validatorOptions)
            ->validate();

        // Si no hay errores de validación, y
        // Imagen guardada correctamente.
        if (!$model->hasErrors() && $this->saveTempImage($model->image)) {
            // Obtener la altura y anchura de la imagen.
            list($width, $height) = getimagesize(
                $this->tempPath . Yii::$app->getSession()->get('tempImage')
            );
            // Formar una respuesta satisfactoria.
            $response = [
                'status' => 'success',
                'url' => $this->savedImage,
                'width' => $width,
                'height' => $height,
            ];

            goto success;
        }

        $response = [
            'status' => 'error',
            'message' => $model->getFirstError('image') !== null ?
                $model->getFirstError('image') :
                'No se pudo cargar la imagen.',
        ];

        success:

        // Devuelve una cadena JSON.
        return Json::encode($response);
    }

    /**
     * Guarda una imagen a una carpeta en la sesión del usuario y escribe el nombre de la imagen.
     *
     * @method saveTempImage
     * @param  UploadedFile  $image una copia del archivo descargado
     * @return bool                 true si la imagen se ha guardado correctamente
     */
    private function saveTempImage($image)
    {
        // Si desea generar
        // un nombre único para la imagen.
        if ($this->uniqueName && $image->extension) {
            $image->name = uniqid('i-' . time()) . '.' . $image->extension;
        }

        // Si hay una entrada en la sesión del usuario.
        if (Yii::$app->getSession()->get('tempImage')) {
            // Retire el disco y si hay una imagen.
            $this->removeTempImage();
        }
        // Almacenar la sesión del usuario
        // el nombre de la imagen.
        Yii::$app->getSession()->set('tempImage', $image->name);

        if (!$image->saveAs($this->tempPath . $image->name)) {
            return false;
        }

        $this->savedImage = $this->tempUrl . $image->name;

        return true;
    }

    /**
     * Elimina una imagen de una carpeta y la escritura de la sesión del usuario.
     *
     * @method removeTempImage
     */
    private function removeTempImage()
    {
        $path = $this->tempPath . Yii::$app->getSession()->get('tempImage');
        // Si la imagen está ahí.
        if (is_file($path)) {
            // Para borrar la imagen.
            unlink($path);
        }
        // Eliminar la entrada de la sesión del usuario.
        Yii::$app->getSession()->remove('tempImage');
    }
}
