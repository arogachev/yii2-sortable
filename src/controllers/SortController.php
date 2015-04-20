<?php

namespace arogachev\sortable\controllers;

use arogachev\sortable\behaviors\BaseSortableBehavior;
use Yii;
use yii\db\ActiveRecord;
use yii\filters\ContentNegotiator;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SortController extends Controller
{
    /**
     * @var ActiveRecord|BaseSortableBehavior
     */
    protected $_model;


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => ContentNegotiator::className(),
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (!Yii::$app->request->isAjax) {
            throw new ForbiddenHttpException('This page is not allowed for view.');
        }

        $modelClass = Yii::$app->request->post('modelClass');
        if (!$modelClass) {
            throw new BadRequestHttpException('Model class must be specified in order to find model.');
        }

        $pk = Yii::$app->request->post('modelPk');
        if (!$pk) {
            throw new BadRequestHttpException('Model primary key must be specified in order to find model.');
        }

        $model = $modelClass::findOne($pk);
        if (!$model) {
            throw new NotFoundHttpException('Model not found.');
        }

        if (!($model instanceof ActiveRecord)) {
            throw new BadRequestHttpException('Valid ActiveRecord model class must be specified in order to find model.');
        }

        if (!$model->isSortableByCurrentUser()) {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        }

        $this->_model = $model;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterAction($action, $result)
    {
        $result = parent::afterAction($action, $result);

        return [
            'sort' => [
                'errors' => !empty($result),
            ],
        ];
    }

    public function actionMoveBefore()
    {
        $this->_model->moveBefore(Yii::$app->request->post('pk'));
    }

    public function actionMoveAfter()
    {
        $this->_model->moveAfter(Yii::$app->request->post('pk'));
    }

    public function actionMoveBack()
    {
        $this->_model->moveBack();
    }

    public function actionMoveForward()
    {
        $this->_model->moveForward();
    }

    public function actionMoveAsFirst()
    {
        $this->_model->moveAsFirst();
    }

    public function actionMoveAsLast()
    {
        $this->_model->moveAsLast();
    }

    public function actionMoveToPosition()
    {
        $this->_model->moveToPosition(Yii::$app->request->post('position'));
    }
}
