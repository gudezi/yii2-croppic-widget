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

или добавьте

```json
"gudezi/yii2-croppic-widget": "*"
```

en la sección `require` de su archivo composer.json.

## el uso de

Una vez que haya instalado la extensión, puede usarlo en su código:

```php
use gudezi\croppic\Croppic;

<?= Croppic::widget([
    'options' => [
        'class' => 'croppic',
    ],
    'pluginOptions' => [
        'uploadUrl' => $model->urlUpload,
        'cropUrl' => $model->urlCrop,
        'modal' => false,
        'doubleZoomControls' => false,
        'enableMousescroll' => true,
        'loaderHtml' => '<div class="loader bubblingG">
            <span id="bubblingG_1"></span>
            <span id="bubblingG_2"></span>
            <span id="bubblingG_3"></span>
        </div> ',
    ]
]) ?>
```

## Sube una imagen y recortar

```php
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
        /**
         * Descargar imagen
         */
        'upload' => [
            'class' => 'gudezi\croppic\actions\UploadAction',
            // Ruta de acceso absoluta a la carpeta en la que está almacenada la imagen (temporalmente).
            'tempPath' => '@frontend/web/img/temp',
            // La dirección URL de la carpeta en la que está almacenada la imagen (temporalmente).
            'tempUrl' => 'img/temp/',
            // Condiciones de verificación de la imagen.
            'validatorOptions' => [
                'checkExtensionByMimeType' => true,
                'extensions' => 'jpeg, jpg, png',
                'maxSize' => 3000000,
                'tooBig' => 'Ha seleccionado una imagen demasiado grande (máx. 3 MB)',
            ],
        ],
        /**
         * recortar la imagen
         */
        'crop' => [
            'class' => 'gudezi\croppic\actions\CropAction',
            // Ruta de acceso absoluta a la carpeta en la que desea guardar la imagen.
            'path' => '@frontend/web/img/user/avatar',
            // La dirección URL de la carpeta en la que desea guardar la imagen.
            'url' => 'img/user/avatar/',
        ],
    ];
}
```

### Características adicionales

Para utilizar funciones adicionales, necesita pasar el objeto action class del ** ** Modelo:

```php
public function beforeAction($action)
{
    if ($action->id === 'upload' || $action->id === 'crop') {
        if ($action->hasProperty('model')) {
            $action->model = $this->findModel(Yii::$app->request->get('id'));
        }
    }

    if (!parent::beforeAction($action)) {
        return false;
    }

    return true;
}
```

#### Compruebe acceso de los usuarios a las páginas utilizando RBAC ####

Las operaciones de transferencia **Autorización** y **Permiso** de RBAC:

```php
public function actions()
{
    return [
        /**
         * Descargar imagen
         */
        'upload' => [
            'class' => 'gudezi\croppic\actions\UploadAction',
            'tempPath' => '@frontend/web/img/temp',
            'tempUrl' => 'img/temp/',
            'validatorOptions' => [
                'checkExtensionByMimeType' => true,
                'extensions' => 'jpeg, jpg, png',
                'maxSize' => 3000000,
                'tooBig' => 'Ha seleccionado una imagen demasiado grande (máx. 3 MB)',
            ],
            // permiso RBAC
            'permissionRBAC' => 'updateProfile',
            // parámetro RBAC
            'parameterRBAC' => 'profile',
        ],
        /**
         * recortar la imagen
         */
        'crop' => [
            'class' => 'gudezi\croppic\actions\CropAction',
            'path' => '@frontend/web/img/user/avatar',
            'url' => 'img/user/avatar/',
            // permiso RBAC
            'permissionRBAC' => 'updateProfile',
            // parámetro RBAC
            'parameterRBAC' => 'profile',
        ],
    ];
}
```

Como se comprobará: `Yii::$app->user->can('updateProfile', ['profile' => $this->model])`.

#### Guardar nombre de ruta o una imagen en la base de datos ####

```php
public function actions()
{
    return [
        /**
         * recortar la imagen
         */
        'crop' => [
            'class' => 'gudezi\croppic\actions\CropAction',
            'path' => '@frontend/web/img/user/avatar',
            'url' => 'img/user/avatar/',
            'modelAttribute' => 'avatar', // <--- ejemplo №1
            'modelScenario' => 'saveAvatar', // <--- ejemplo №2
            'modelAttributeSavePath' => false, // <--- ejemplo №3
        ],
    ];
}
```

pasar a la acción **crop**:
 - **Nombre del modelo del atributo que se utilizará para guardar (** Ejemplo №1 **).
 - **Escenario modelo utilizado para comprobar los datos de entrada (** Ejemplo №2 **).
 - Si desea guardar sólo el nombre de la imagen para el parámetro ** modelAttributeSavePath
    ** Introducir el valor en false ** (** Ejemplo №3 **).
