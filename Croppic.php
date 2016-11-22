<?php

namespace gudezi\croppic;

/**
 * @author Gustavo Dezi
 * @link   <gudezi@gmail.com>
 */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\base\InvalidConfigException;
use yii\widgets\InputWidget;

class Croppic extends InputWidget
{
    const SELECT_SINGLE = 1;
    const CLICK_ACTIVATE = 1;
    
    /**
     * @var string
     */
    public $idPrefix = 'ft_';

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
            $this->options['style'] = 'display:none;';
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
        
        $this->pluginOptions['onAfterRemoveCroppedImg'] = 'function(){ reloadimg(); }';
        $this->pluginOptions['onBeforeImgUpload'] = 'function(){ openimg(); }';
        $this->pluginOptions['onReset'] = 'function(){ reloadimg(); }';
        $this->pluginOptions['onAfterImgCrop'] = 'function(){ saveurl(); }';
        
        /*$this->pluginOptions['onImgDrag'] = 'function(){ milert8(); }';
        $this->pluginOptions['onImgZoom'] = 'function(){ milert8(); }';
        $this->pluginOptions['onImgRotate'] = 'function(){ milert8(); }';
        
        $this->pluginOptions['onBeforeImgCrop'] = 'function(){ milert4(); }';
        $this->pluginOptions['onBeforeRemoveCroppedImg'] = 'function(){ milert1(); }';
        $this->pluginOptions['onAfterRemoveCroppedImg'] = 'function(){ milert1(); }';
        $this->pluginOptions['onError'] = 'function(){ milert7(); }';*/
        
        parent::init();
    }
    
    /**
     * @inheritdoc
     */
    public function run()
    {
        //echo Html::tag('input', '');
        //echo Html::input('text', 'txtfotocrop', 'nombre', ['class' => 'form-control']);
        $id=$this->id;

        $nametext = $this->hasModel() ? Html::getInputName($this->model, $this->attribute) : $this->name;

        $idtext = $this->hasModel() ? Html::getInputId($this->model, $this->attribute) : $this->getId();

        $this->pluginOptions['outputUrlId'] = $id.'_hidurlid';
        $this->pluginOptions['customUploadButtonId'] = $id.'_openbutton';
        
        $attribute = $this->attribute;
        $value=$this->model->$attribute;
        $pathroot = $this->options['pathroot'];; 
        $loadimg = '';

        echo Html::Input('text', $nametext, $value, ['class' => 'form-control', 'id' => $idtext]);
        echo Html::Input('hidden', $id.'_hidoldval', $value, ['class' => 'form-control', 'id' => $id.'_hidoldval']);
        echo Html::Input('hidden', $id.'_hidurlid', $value, ['class' => 'form-control', 'id' => $id.'_hidurlid']);
        
        if($value!='')
        {
            echo '<div class="croppic" id="'.$id.'_loadimg" style="background-image: url(/'.$pathroot.$value.');">';
            echo '<div class="cropControls cropControlsUpload" id="'.$id.'_buttomimg" > <i class="cropControlUpload mibot" id="'.$id.'_openbutton"></i> </div>';
            echo '</div>';
        }
        else
        {
            echo '<div class="croppic" id="'.$id.'_loadimg" style="background-image: url(/'.$loadimg.');">';
            echo '<div class="cropControls cropControlsUpload" id="'.$id.'_buttomimg" > <i class="cropControlUpload mibot" id="'.$id.'_openbutton"></i> </div>';
            echo '</div>';
        }

        //type, model, model attribute name, options
        //echo Html::Input('text', $this->model, $name, ['class' => 'form-control']);
        
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

        $pluginOptions = str_replace('"function(){ reloadimg(); }"','function(){ reloadimg(); }',$pluginOptions);
        $pluginOptions = str_replace('"function(){ openimg(); }"','function(){ openimg(); }',$pluginOptions);
        $pluginOptions = str_replace('"function(){ saveurl(); }"','function(){ saveurl(); }',$pluginOptions);

        $id = $this->id;
        $idtext = $this->hasModel() ? Html::getInputId($this->model, $this->attribute) : $this->getId();

        //print_r($id);die;
        $js = "var {$this->id} = new Croppic('{$this->id}', {$pluginOptions});";
       
        $view->registerJs($js);
 
        $js='function reloadimg(){ 
        $("#'.$id.'_loadimg").show();
        $("#'.$id.'_buttomimg").show();
        $("#'.$id.'").hide();
        ant = $("#'.$id.'_hidoldval").val();
        $("#'.$idtext.'").val(ant);

        }';
        $view->registerJs($js);

        $js='function openimg(){ 
        $("#'.$id.'_loadimg").hide();
        $("#'.$id.'_buttomimg").hide();
        $("#'.$id.'").show();
        }';
        $view->registerJs($js);
        
        $js='function saveurl(){ 
        str = $("#'.$id.'_hidurlid").val();
        res = str.replace("..", ""); 
        $("#'.$idtext.'").val(res);
        }';
        $view->registerJs($js);
    }
}
