<?php

namespace gudezi\croppic\actions;

/**
 * @author Gustavo Dezi
 * @link   <gudezi@gmail.com>
 */

use Yii;
use yii\base\Action;
use yii\helpers\Json;
use yii\base\DynamicModel;
use yii\helpers\FileHelper;
use yii\base\InvalidCallException;
use yii\web\ForbiddenHttpException;
use yii\base\InvalidConfigException;

/**
 * Acción de clase para recortar imágenes.
 *
 * el uso de:
 *
 * public function behaviors()
 * {
 *     return [
 *         'verbs' => [
 *             'class' => VerbFilter::className(),
 *             'actions' => [
 *                 'crop' => ['post'],
 *             ],
 *         ],
 *     ];
 * }
 *
 * public function actions()
 * {
 *     return [
 *         'crop' => [
 *             'class' => 'gudezi\croppic\actions\CropAction',
 *             'path' => '@frontend/web/img/user/avatar',
 *             'url' => 'img/user/avatar/',
 *             'modelAttribute' => 'avatar',
 *             'modelScenario' => 'saveAvatar',
 *             'permissionRBAC' => 'updateProfile',
 *             'parameterRBAC' => 'profile',
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
class CropAction extends Action
{
    /**
     * La ruta absoluta al directorio en el que se descarga la imagen.
     *
     * @var string
     */
    public $path;
    /**
     * URL que indica la ruta de acceso al directorio en el que se descarga la imagen.
     *
     * @var string
     */
    public $url;
    /**
     * Un ejemplo que se utiliza para verificar el acceso a la página y guardar el nombre de la imagen o la ruta de acceso a la base  * de datos.
     *
     * @var string
     */
    public $model;
    /**
     * El modelo escenario se utiliza para comprobar los datos entrantes.
     *
     * @var string
     */
    public $modelScenario;
    /**
     * El atributo de nombre del modelo que se utilizará para
     * Guardar o nombre de la ruta de la imagen en la base de datos.
     *
     * @var string
     */
    public $modelAttribute;
    /**
     * Indica mantener una ruta completa a la base de datos
     * Imagen de "true" o sólo el nombre de la imagen "false".
     * Por defecto, contiene la ruta completa de la imagen.
     *
     * Ejemplo:
     * Tomar el valor del atributo 'url' y se añade a ella
     * el nombre de la Imagen 'img/user/avatar/img.jpeg'.
     *
     * @var boolean
     */
    public $modelAttributeSavePath = true;
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
    private $croppedImage;

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Si el atributo 'path' está vacío.
        if ($this->path === null) {
            throw new InvalidConfigException(
                'El atributo "path" no puede estar vacío.'
            );
        }
        $this->path = rtrim(Yii::getAlias($this->path), '/') . '/';

        // Si el atributo  'url' está vacío..
        if ($this->url === null) {
            throw new InvalidConfigException(
                'El atributo "url" no puede estar vacío.'
            );
        }
        $this->url = rtrim($this->url, '/') . '/';

        // Si el directorio no existe o no puede crearlo.
        if (!FileHelper::createDirectory($this->path)) {
            throw new InvalidCallException(
                'El directorio especificado en el atributo "path" no existe o no se puede crear.'
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->modelAttribute();
        $attributes = $this->getPostData();

        // Crear una instancia de la clase DynamicModel,
        // definir atributos, verificar.
        $model = DynamicModel::validateData(
            $attributes,
            [
                [['imgUrl', 'imgW', 'imgH', 'imgX1', 'imgY1', 'cropW', 'cropH', 'rotation'], 'required'],
                ['imgUrl', 'string'],
                ['imgUrl', 'filter', 'filter' => 'strip_tags'],
                [['imgW', 'imgH', 'imgX1', 'imgY1', 'cropW', 'cropH', 'rotation'], 'double']
            ]
        );

        // Si no hay errores de validación, y
        // Imagen guardada correctamente.
        if (!$model->hasErrors() && $this->cropImage($model)) {
            // Si se llenan los atributos del "model" y "modelAttribute'.
            if ($this->model !== null && $this->modelAttribute !== null) {
                $modelAttribute = $this->modelAttribute;
                // Asigna un atributo específico de la ruta o simplemente el nombre de la imagen.
                $this->model->$modelAttribute = $this->modelAttributeSavePath ?
                    $this->croppedImage :
                    Yii::$app->getSession()->get('tempImage');

                $this->model->save();
            }

            // Para eliminar una imagen de la carpeta Temp.
            $this->removeTempImage($model->imgUrl);

            // Formar una respuesta satisfactoria.
            $response = [
                'status' => 'success',
                'url' => $this->croppedImage,
            ];

            goto success;
        }

        $response = [
            'status' => 'error',
            'message' => 'No se ha podido procesar la imagen.',
        ];

        success:

        // Devuelve una cadena JSON.
        return Json::encode($response);
    }

    /**
     * Se utiliza para el método de descarga 'run'.
     *
     * @method modelAttribute
     */
    private function modelAttribute()
    {
        // Si se llena el atributo "model".
        if ($this->model !== null) {
            // Compruebe que era
            // una instancia de la clase 'yii\db\BaseActiveRecord'.
            if (!($this->model instanceof \yii\db\BaseActiveRecord)) {
                throw new InvalidConfigException(
                    'El atributo "model" no es una instancia de una clase "yii\db\BaseActiveRecord".'
                );
            }
            // Si el atributo 'modelScenario' está lleno.
            if ($this->modelScenario !== null) {
                $this->model->scenario = $this->modelScenario;
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
    }

    /**
     * Crea y devuelve una matriz con los parámetros
     * que vino a través del POST.
     *
     * @method getPostData
     * @return array       con los parámetros de matriz
     */
    private function getPostData()
    {
        $request = Yii::$app->request;

        return [
            // La ruta de la imagen descargada.
            'imgUrl' => $request->post('imgUrl'),
            // Cambiar el tamaño de la imagen.
            'imgW' => $request->post('imgW'),
            'imgH' => $request->post('imgH'),
            // Desplazamiento de imagen.
            'imgY1' => $request->post('imgY1'),
            'imgX1' => $request->post('imgX1'),
            // Las dimensiones de la superficie de recorte.
            'cropW' => $request->post('cropW'),
            'cropH' => $request->post('cropH'),
            // El ángulo de rotación de la imagen.
            'rotation' => $request->post('rotation')
        ];
    }

    /**
     * Obtiene la imagen, los procesos descargados y lo guarda en la carpeta especificada. 
     * Si es necesario, se almacena el nombre de ruta o una imagen en la base de datos.
     *
     * @method cropImage
     * @param  DynamicModel $model instancia de la clase
     * @return bool                true si la imagen se ha guardado correctamente
     */
    private function cropImage(\yii\base\DynamicModel $model)
    {
        // Compruebe que la imagen existe
        // y puede ser leída.
        if (!is_readable(Yii::getAlias('@webroot/' . $model->imgUrl))) {
            throw new InvalidCallException(
                'La imagen no existe o no se puede leer.'
            );
        }

        \yii\imagine\Image::$driver = [
            \yii\imagine\Image::DRIVER_IMAGICK,
            \yii\imagine\Image::DRIVER_GMAGICK,
            \yii\imagine\Image::DRIVER_GD2,
        ];

        $imagine = new \yii\imagine\Image;

        // Si se llenan los atributos del "model" y "modelAttribute'.
        if ($this->model !== null && $this->modelAttribute !== null) {
            // Retire la imagen anterior.
            $this->removeImage();
        }

        // Procesamos y guardar la imagen.
        $image = $imagine->getImagine()->open(
            Yii::getAlias('@webroot/' . $model->imgUrl)
        )
        ->resize(new \Imagine\Image\Box($model->imgW, $model->imgH))
        ->rotate($model->rotation)
        ->crop(
            new \Imagine\Image\Point($model->imgX1, $model->imgY1),
            new \Imagine\Image\Box($model->cropW, $model->cropH)
        )
        ->save($this->path . Yii::$app->getSession()->get('tempImage'));

        if (!$image) {
            return false;
        }

        $this->croppedImage = $this->url . Yii::$app->getSession()->get('tempImage');

        return true;
    }

    /**
     * Elimina la imagen anterior,
     * antes de guardar el nuevo.
     *
     * @method removeImage
     */
    private function removeImage()
    {
        $modelAttribute = $this->modelAttribute;
        $path = $this->modelAttributeSavePath ?
            Yii::getAlias('@webroot/' . $this->model->$modelAttribute) :
            $this->path . $this->model->$modelAttribute;

        // Si la imagen existe.
        if (is_file($path)) {
            //eliminar la imagen.
            unlink($path);
        }
    }

    /**
     * Elimina una imagen de una carpeta y por escrito de la sesión del usuario.
     *
     * @method removeTempImage
     */
    private function removeTempImage($imgUrl)
    {
        $path = Yii::getAlias('@webroot/' . $imgUrl);
        // Si la imagen existe.
        if (is_file($path)) {
            // eliminar la imagen.
            unlink($path);
        }
        // Eliminar la entrada de la sesión del usuario.
        Yii::$app->getSession()->remove('tempImage');
    }
}
