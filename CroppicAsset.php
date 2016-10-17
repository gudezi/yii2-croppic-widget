<?php

namespace gudezi\croppic;

/**
 * @author Gustavo Dezi
 * @link   <gudezi@gmail.com>
 */

use yii\web\AssetBundle;

/**
 * Class Resource Kit widget Croppic.
 */
class CroppicAsset extends AssetBundle
{
    public $sourcePath = '@gudezi/croppic/assets';
    public $depends = [
        'yii\web\JqueryAsset',
    ];

    /**
     * Registros de los archivos CSS y JS.
     *
     * @method registerAssetFiles
     * @param \yii\web\View $view Ver qué archivos
     *                            Debe estar registrado
     */
    public function registerAssetFiles($view)
    {
        $this->css[] = 'croppic' . (!YII_ENV_DEV ? '.min' : '') . '.css';
        $this->js[] = 'croppic' . (!YII_ENV_DEV ? '.min' : '') . '.js';
        $this->js[] = 'jquery.mousewheel.min.js';

        parent::registerAssetFiles($view);
    }
}
