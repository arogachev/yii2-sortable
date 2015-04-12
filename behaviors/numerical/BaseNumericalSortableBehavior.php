<?php

namespace arogachev\sortable\behaviors\numerical;

use arogachev\sortable\behaviors\BaseSortableBehavior;
use yii\base\InvalidParamException;
use yii\db\ActiveRecord;

abstract class BaseNumericalSortableBehavior extends BaseSortableBehavior
{
    /**
     * @var string
     */
    public $sortAttribute = 'sort';

    /**
     * @var integer
     */
    protected $_intervalSize;


    /**
     * @param integer $position
     * @return integer
     */
    abstract protected function getInitialSortByPosition($position);

    abstract protected function prependAdded();

    /**
     * @inheritdoc
     */
    public function events()
    {
        return array_merge(parent::events(), [
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
        ]);
    }

    public function beforeInsert()
    {
        if (!$this->isSortable()) {
            $this->resetSort();

            return;
        }

        $this->addSort();
    }

    public function beforeUpdate()
    {
        parent::beforeUpdate();

        if ($this->_sortableDiff == self::SORTABLE_DIFF_SORTABLE) {
            $this->addSort();
        } elseif ($this->_sortableDiff == self::SORTABLE_DIFF_NOT_SORTABLE) {
            $this->resetSort();
        }
    }

    /**
     * @inheritdoc
     */
    public function moveBefore($pk = null)
    {
        if (!$pk) {
            $this->moveAsLast();

            return;
        }

        $prevModel = $this->findModel($pk);

        if ($this->getSortablePosition() > $prevModel->getSortablePosition()) {
            $position = $prevModel->getSortablePosition();
        } else {
            $position = $prevModel->getSortablePosition() - 1;
        }

        $this->moveToPosition($position);
    }

    /**
     * @inheritdoc
     */
    public function moveAfter($pk = null)
    {
        if (!$pk) {
            $this->moveAsFirst();

            return;
        }

        $nextModel = $this->findModel($pk);

        if ($this->getSortablePosition() > $nextModel->getSortablePosition()) {
            $position = $nextModel->getSortablePosition() + 1;
        } else {
            $position = $nextModel->getSortablePosition();
        }

        $this->moveToPosition($position);
    }

    /**
     * @return integer
     */
    public function getSort()
    {
        return $this->model->{$this->sortAttribute};
    }

    public function reindexAll()
    {
        $models = $this->getAllModels();
        $position = 1;

        foreach ($models as $model) {
            $model->updateAttributes([$this->sortAttribute => $this->getInitialSortByPosition($position)]);

            $position++;
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    protected function getQuery()
    {
        return parent::getQuery()
            ->addSelect($this->sortAttribute)
            ->orderBy([$this->sortAttribute => SORT_ASC]);
    }

    /**
     * @return integer
     */
    protected function getFirstSort()
    {
        $model = $this->query->one();

        return $model->getSort();
    }

    /**
     * @return integer
     */
    protected function getLastSort()
    {
        $model = $this->query
            ->orderBy([$this->sortAttribute => SORT_DESC])
            ->one();

        return $model ? $model->getSort() : 0;
    }

    /**
     * @param $value
     */
    protected function setSort($value)
    {
        $this->model->{$this->sortAttribute} = $value;
    }

    protected function addSort()
    {
        if ($this->prependAdded) {
            $this->prependAdded();
        } else {
            $this->setSort($this->getLastSort() + $this->_intervalSize);
        }
    }

    protected function resetSort()
    {
        $this->setSort(0);
    }

    /**
     * @param integer|array $pk
     * @return array
     */
    protected function getPkCondition($pk)
    {
        if (count($this->model->primaryKey()) > 1) {
            return $pk;
        }

        return [$this->model->primaryKey()[0] => $pk];
    }

    /**
     * @param integer|array $pk
     * @return ActiveRecord|BaseNumericalSortableBehavior
     */
    protected function findModel($pk)
    {
        $model = $this->query->andWhere($this->getPkCondition($pk))->one();

        if (!$model) {
            throw new InvalidParamException('The model not found by given primary key.');
        }

        return $model;
    }

    /**
     * @param integer $newPosition
     * @param boolean $updateCurrentModel
     */
    protected function resolveConflict($newPosition, $updateCurrentModel = true)
    {
        $models = $this->getAllModels();
        $position = 1;

        foreach ($models as $model) {
            $isCurrentModel = $model->primaryKey == $this->model->primaryKey;

            if ($position == $newPosition && $position != $this->getSortableCount()) {
                $position++;
            }

            $updatedPosition = $isCurrentModel ? $newPosition : $position;
            $sort = $this->getInitialSortByPosition($updatedPosition);

            if ($isCurrentModel && !$updateCurrentModel) {
                $this->setSort($sort);
            } else {
                $model->updateAttributes([$this->sortAttribute => $sort]);
            }

            if (!$isCurrentModel) {
                $position++;
            }
        }
    }

    /**
     * @return boolean
     */
    protected function isScopeChanged()
    {
        foreach ($this->getSortableScopeCondition() as $attribute => $value) {
            if ($this->model->isAttributeChanged($attribute)) {
                return true;
            }
        }

        return false;
    }
}
