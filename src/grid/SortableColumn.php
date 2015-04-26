<?php

namespace arogachev\sortable\grid;

use arogachev\sortable\assets\SortableColumnAsset;
use arogachev\sortable\behaviors\numerical\BaseNumericalSortableBehavior;
use arogachev\sortable\widgets\Sortable;
use Yii;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\grid\Column;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\i18n\PhpMessageSource;
use yii\web\JsExpression;

class SortableColumn extends Column
{
    /**
     * @var string
     */
    public $template;

    /**
     * @var array
     */
    public $buttons = [];

    /**
     * @var string
     */
    public $gridContainerId;

    /**
     * @var ActiveRecord|BaseNumericalSortableBehavior
     */
    protected $_model;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        /* @var $dataProvider ActiveDataProvider */
        $dataProvider = $this->grid->dataProvider;
        if (!($dataProvider instanceof ActiveDataProvider)) {
            throw new InvalidConfigException('SortableColumn works only with ActiveDataProvider.');
        }

        if (!$this->gridContainerId) {
            throw new InvalidConfigException('$gridContainerId property must be set.');
        }

        Yii::setAlias('@sortable', dirname(__DIR__));
        Yii::$app->i18n->translations['sortable'] = [
            'class' => PhpMessageSource::className(),
            'basePath' => '@sortable/messages',
        ];

        /* @var $query \yii\db\ActiveQuery */
        $query = $dataProvider->query;

        $this->_model = new $query->modelClass;

        $this->contentOptions = function ($model) {
            /* @var $model ActiveRecord|BaseNumericalSortableBehavior */

            return [
                'class' => 'sortable-cell',
                'data-position' => $model->getSortablePosition(),
            ];
        };

        if (!$this->header) {
            $this->header = Yii::t('sortable', 'Sort');
        }

        $this->visible = $this->isVisible();

        if (!$this->template) {
            $this->template = '<div class="sortable-section">{currentPosition}</div>
            <div class="sortable-section">{moveWithDragAndDrop}</div>
            <div class="sortable-section">{moveForward} {moveBack}</div>
            <div class="sortable-section">{moveAsFirst} {moveAsLast}</div>';
        }

        $this->initDefaultButtons();

        if (!Yii::$app->request->isAjax) {
            $this->registerJs();
        }
    }

    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        /* @var $model ActiveRecord|BaseNumericalSortableBehavior */
        if (!$model->isSortable()) {
            return Html::tag('span', Yii::t('sortable', 'Not sortable item'), ['class' => 'label label-danger']);
        }

        return preg_replace_callback('/\\{([\w\-\/]+)\\}/', function ($matches) use ($model, $key, $index) {
            $name = $matches[1];

            if (isset($this->buttons[$name])) {
                return call_user_func($this->buttons[$name], $model, $key, $index);
            } else {
                return '';
            }
        }, $this->template);
    }

    protected function initDefaultButtons()
    {
        if (!isset($this->buttons['currentPosition'])) {
            $this->buttons['currentPosition'] = function ($model) {
                /* @var $model ActiveRecord|BaseNumericalSortableBehavior */

                return Html::tag('span', $model->getSortablePosition(), [
                    'class' => 'label label-info',
                    'contenteditable' => true,
                    'title' => Yii::t('sortable', 'Current position') . ' / ' . Yii::t('sortable', 'Change position'),
                ]);
            };
        }

        if (!isset($this->buttons['moveWithDragAndDrop'])) {
            $this->buttons['moveWithDragAndDrop'] = function () {
                return Html::tag('span', '', [
                    'class' => 'glyphicon glyphicon-sort',
                    'title' => Yii::t('sortable', 'Move with drag and drop'),
                ]);
            };
        }

        if (!isset($this->buttons['moveForward'])) {
            $this->buttons['moveForward'] = function () {
                return Html::tag('span', '', [
                    'class' => 'glyphicon glyphicon-arrow-up',
                    'title' => Yii::t('sortable', 'Move forward'),
                ]);
            };
        }

        if (!isset($this->buttons['moveBack'])) {
            $this->buttons['moveBack'] = function () {
                return Html::tag('span', '', [
                    'class' => 'glyphicon glyphicon-arrow-down',
                    'title' => Yii::t('sortable', 'Move back'),
                ]);
            };
        }

        if (!isset($this->buttons['moveAsFirst'])) {
            $this->buttons['moveAsFirst'] = function () {
                return Html::tag('span', '', [
                    'class' => 'glyphicon glyphicon-fast-backward',
                    'title' => Yii::t('sortable', 'Move as first'),
                ]);
            };
        }

        if (!isset($this->buttons['moveAsLast'])) {
            $this->buttons['moveAsLast'] = function () {
                return Html::tag('span', '', [
                    'class' => 'glyphicon glyphicon-fast-forward',
                    'title' => Yii::t('sortable', 'Move as last'),
                ]);
            };
        }
    }

    protected function isVisible()
    {
        if (!$this->_model->isSortableByCurrentUser()) {
            return false;
        }

        if ($this->grid->filterModel) {
            $scopeAttributes = array_keys($this->_model->getSortableScopeCondition());
            $sortableAttributes = array_keys($this->_model->sortableCondition);
            $formData = Yii::$app->request->get($this->grid->filterModel->formName(), []);

            foreach ($scopeAttributes as $attribute) {
                if (!ArrayHelper::getValue($formData, $attribute) && !Yii::$app->request->get($attribute)) {
                    return false;
                }
            }

            foreach ($formData as $attribute => $value) {
                if ($value && !in_array($attribute, $sortableAttributes)) {
                    return false;
                }
            }
        }

        $sort = $this->grid->dataProvider->getSort();

        return $sort->orders == [$this->_model->sortAttribute => SORT_ASC];
    }

    protected function registerJs()
    {
        SortableColumnAsset::register(Yii::$app->view);

        $model = $this->_model;

        Sortable::widget([
            'id' => $this->gridContainerId,
            'clientOptions' => [
                'items' => 'tbody tr',
                'handle' => '.glyphicon-sort',
                'modelClass' => $model::className(),
                'modelsCount' => $this->grid->dataProvider->getTotalCount(),
                'moveConfirmationText' => Yii::t('sortable', 'Are you sure you want to move this item?'),
            ],
            'clientEvents' => [
                'update' => new JsExpression("function(event, ui) {
                    $(ui.item).yiiGridViewRow('moveWithDragAndDrop');
                }"),
                'blur' => [
                    '.label-info' => new JsExpression("function() {
                        $(this.closest('tr')).yiiGridViewRow('moveToPosition', $(this).text());
                    }"),
                ],
                'keypress' => [
                    '.label-info' => new JsExpression("function(e) {
                        if (e.which == 13) {
                            $(this).trigger('blur');
                        }
                    }"),
                ],
                'click' => [
                    '.sortable-section .glyphicon-arrow-up' => new JsExpression("function() {
                        $(this.closest('tr')).yiiGridViewRow('moveForward');
                    }"),
                    '.sortable-section .glyphicon-arrow-down' => new JsExpression("function() {
                        $(this.closest('tr')).yiiGridViewRow('moveBack');
                    }"),
                    '.sortable-section .glyphicon-fast-backward' => new JsExpression("function() {
                        $(this.closest('tr')).yiiGridViewRow('moveAsFirst');
                    }"),
                    '.sortable-section .glyphicon-fast-forward' => new JsExpression("function() {
                        $(this.closest('tr')).yiiGridViewRow('moveAsLast');
                    }"),
                ],
            ],
        ]);
    }
}
