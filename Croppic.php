<?php

namespace gudezi\croppic;

/**
 * @author Gustavo Dezi
 * @link   <gudezi@gmail.com>
 */

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\base\InvalidConfigException;

/**
 * 
 * Widget para Croppic - Plugin jQuery para cropping
 *
 * @see http://www.croppic.net/
 * @link https://github.com/sconsult/croppic
 *
 * USO:
 *
 * use gudezi\croppic\Croppic;
 *
 * <?= Croppic::widget([
 *    'options' => [
 *       'class' => 'croppic',
 *    ],
 *    'pluginOptions' => [
 *       'uploadUrl' => $model->urlUpload,
 *       'cropUrl' => $model->urlCrop,
 *       'modal' => false,
 *       'doubleZoomControls' => false,
 *       'enableMousescroll' => true,
 *       'loaderHtml' => '<div class="loader bubblingG">
 *          <span id="bubblingG_1"></span>
 *          <span id="bubblingG_2"></span>
 *          <span id="bubblingG_3"></span>
 *       </div> ',
 *    ],
 * ]) ?>
 */
class Croppic extends Widget
{
    /**
     * HTML atributos de etiqueta div.
     *
     * @var array
     */
    public $options = [];
    /**
     * Opciones de plug-js Croppic, todas las opciones posibles
     * Ver la página web oficial - "http://www.croppic.net/".
     *
     * @var array
     */
    public $pluginOptions = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Sino se establece 'id' widget.
        if (!isset($this->options['id'])) {
            // Utilice el ID autogenerado.
            $this->options['id'] = $this->getId();
        }
        // Asignar el 'id' widget.
        $this->id = $this->options['id'];

        // Si la opción 'uploadURL' está vacía.
        if (!isset($this->pluginOptions['uploadUrl']) || empty($this->pluginOptions['uploadUrl'])) {
            throw new InvalidConfigException('Parámetro "uploadURL" no puede estar vacío');
        }
        // Si el parámetro 'cropUrl' está vacía.
        if (!isset($this->pluginOptions['cropUrl']) || empty($this->pluginOptions['cropUrl'])) {
            throw new InvalidConfigException('Parámetro "cropUrl" no puede estar vacío');
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        echo Html::tag('div', '', $this->options);

        $this->registerClientScript();
    }

    /**
     * Registra css y js en una página.
     */
    public function registerClientScript()
    {
        $view = $this->getView();
        CroppicAsset::register($view);

        $pluginOptions = Json::encode($this->pluginOptions);
        $js = "var {$this->id} = new Croppic('{$this->id}', {$pluginOptions});";

        $view->registerJs($js);
    }
}
