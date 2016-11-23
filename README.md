# Croppic widget Yii2 Framework

**Croppic** - Este plugin jQuery para el Recorte.

 - **Github** - https://github.com/sconsult/croppic
 - **sitio web oficial** - http://www.croppic.net/

## Ajuste

Es aconsejable instalar la extensión a través de [composer](http://getcomposer.org/download/).

Sólo tiene que ejecutar el comando en la consola:

```bash
php composer.phar require --prefer-dist gudezi/yii2-croppic-widget "*"
```

o agregar

```json
"gudezi/yii2-croppic-widget": "*"
```

en la sección `require` de su archivo composer.json.

## el uso de

Una vez que haya instalado la extensión, puede usarlo en su código:

En el formulario de la vista agregar:

```php
    use gudezi\croppic\Croppic;

    $options = [
        'class' => 'croppic',
        'pathroot' => 'yiiBaseAdvanced/backend/web',
    ];
    $pluginOptions= [
        'uploadUrl' => '../upload-crop/upload',
        'cropUrl' => '../upload-crop/crop',
        'modal' => false,
        'doubleZoomControls' => false,
        'enableMousescroll' => true,
        'loaderHtml' => '<div class="loader bubblingG">
            <span id="bubblingG_1"></span>
            <span id="bubblingG_2"></span>
            <span id="bubblingG_3"></span>
        </div> ',
    ];
    echo $form->field($model, 'urlUpload')->widget(Croppic::className(),
        ['options' => $options,'pluginOptions' => $pluginOptions]); 
```

## Crear un controlador para subir y recortar una imagen 

```php
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use gudezi\croppic\actions\CropAction;
use gudezi\croppic\actions\UploadAction;

class UploadCropController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'upload' => ['post'],
                    'crop' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'upload' => [
                'class' => 'gudezi\croppic\actions\UploadAction',
                'tempPath' => '@backend/web/img/temp',
                'tempUrl' => '../img/temp/',
                'validatorOptions' => [
                    'checkExtensionByMimeType' => true,
                    'extensions' => 'jpeg, jpg, png',
                    'maxSize' => 3000000,
                    'tooBig' => 'Ha seleccionado una imagen demasiado grande (máx. 3 MB)',
                ],
            ],
            'crop' => [
                'class' => 'gudezi\croppic\actions\CropAction',
                'path' => '@backend/web/img/user/avatar',
                'url' => '../img/user/avatar/',
                'modelAttribute' => 'urlUpload',
            ],
        ];
    }
}
```

Y para usar los datos del modelo 

En el formulario de la vista agregar:

```php
    use gudezi\croppic\Croppic;

    $options = [
        'class' => 'croppic',
        'pathroot' => 'yiiBaseAdvanced/backend/web',
    ];
    $pluginOptions= [
        'uploadUrl' => '../upload-crop/upload?id='.$model->id,
        'cropUrl' => '../upload-crop/crop?id='.$model->id,
        'modal' => false,
        'doubleZoomControls' => false,
        'enableMousescroll' => true,
        'loaderHtml' => '<div class="loader bubblingG">
            <span id="bubblingG_1"></span>
            <span id="bubblingG_2"></span>
            <span id="bubblingG_3"></span>
        </div> ',
    ];
    echo $form->field($model, 'urlUpload')->widget(Croppic::className(),
        ['options' => $options,'pluginOptions' => $pluginOptions]); 
```

## Crear un controlador para subir y recortar una imagen 

```php
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use gudezi\croppic\actions\CropAction;
use gudezi\croppic\actions\UploadAction;

use backend\models\Fotos;

class UploadCropController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'upload' => ['post','get'],
                    'crop' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        $id = Yii::$app->request->get('id');
        if($id>0)
            $model = $this->findModel($id);
        else
            $model = new Fotos();
        
        return [
            'upload' => [
                'class' => 'gudezi\croppic\actions\UploadAction',
                'tempPath' => '@backend/web/img/temp',
                'tempUrl' => '../img/temp/',
                'modelAttribute' => 'urlUpload',
                'model' => $model,
                'validatorOptions' => [
                    'checkExtensionByMimeType' => true,
                    'extensions' => 'jpeg, jpg, png',
                    'maxSize' => 3000000,
                    'tooBig' => 'Ha seleccionado una imagen demasiado grande (máx. 3 MB)',
                ],
            ],
            'crop' => [
                'class' => 'gudezi\croppic\actions\CropAction',
                'path' => '@backend/web/img/user/avatar',
                'url' => '../img/user/avatar/',
                'modelAttribute' => 'urlUpload',
                'model' => $model,
            ],
        ];
    }
    
    protected function findModel($id)
    {
        if (($model = Fotos::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }    
}
```

