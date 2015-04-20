<?php

namespace arogachev\sortable\assets;

use yii\web\AssetBundle;

class SortableColumnAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@vendor/arogachev/yii2-sortable/src/assets/src';

    /**
     * @inheritdoc
     */
    public $publishOptions = [
        'forceCopy' => YII_DEBUG,
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/sortable-column.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'js/sortable-column.js',
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        'yii\jui\JuiAsset',
    ];
}
