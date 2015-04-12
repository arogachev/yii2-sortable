<?php

namespace arogachev\sortable\behaviors\numerical;

use yii\helpers\ArrayHelper;

class IntervalNumericalSortableBehavior extends BaseNumericalSortableBehavior
{
    const POSITIONS_MAP_KEY_DIVIDER = '-';

    /**
     * @var integer
     */
    public $increasingLimit = 10;

    /**
     * @var array
     */
    protected static $_positionsMap = [];


    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->_intervalSize) {
            $this->_intervalSize = 1000;
        }

        parent::init();
    }

    public function afterFind()
    {
        if (!self::$_positionsMap) {
            $this->initPositionsMap();
        }
    }

    /**
     * @inheritdoc
     */
    public function getSortablePosition()
    {
        return self::$_positionsMap[$this->getPositionMapKey()];
    }

    /**
     * @inheritdoc
     */
    public function moveToPosition($position)
    {
        if (parent::moveToPosition($position)) {
            return;
        }

        if ($position == 1) {
            // Move as first
            $this->moveToInterval([0, $this->getFirstSort()], $position);
        } elseif ($position == $this->getSortableCount()) {
            // Move as last
            if ($this->isIncreasedLimitReached()) {
                $this->resolveConflict($position);
            } else {
                $this->model->updateAttributes([
                    $this->sortAttribute => $this->getLastSort() + $this->_intervalSize,
                ]);
            }
        } else {
            $this->moveToInterval($this->getNewInterval($position), $position);
        }
    }

    /**
     * @param integer $value
     */
    public function setIntervalSize($value)
    {
        $this->_intervalSize = $value;
    }

    /**
     * @inheritdoc
     */
    protected function getInitialSortByPosition($position)
    {
        return $position * $this->_intervalSize;
    }

    /**
     * @inheritdoc
     */
    protected function prependAdded()
    {
        $sort = $this->getSortByInterval([0, $this->getFirstSort()]);
        if (!$sort) {
            $this->resolveConflict(1, false);

            if ($this->model->isNewRecord) {
                $this->setSort($this->getInitialSortByPosition(1));
            }

            return;
        }

        $this->setSort($sort);
    }

    protected function initPositionsMap()
    {
        $elements = $this->query
            ->select($this->model->primaryKey())
            ->asArray()
            ->all();
        $position = 1;

        foreach ($elements as $element) {
            self::$_positionsMap[$this->getPositionMapKey($element)] = $position;

            $position++;
        }
    }

    /**
     * @param null|array $pk
     * @return mixed
     */
    protected function getPositionMapKey($pk = null)
    {
        $pk = $pk ?: $this->model->getPrimaryKey(true);

        if (count($pk) == 1) {
            return reset($pk);
        }

        $key = '';

        foreach ($pk as $name => $value) {
            $key = $name . self::POSITIONS_MAP_KEY_DIVIDER . $value;
        }

        return rtrim($key, self::POSITIONS_MAP_KEY_DIVIDER);
    }

    /**
     * @param array $interval
     * @param integer $position
     */
    protected function moveToInterval($interval, $position)
    {
        $sort = $this->getSortByInterval($interval);
        if (!$sort) {
            $this->resolveConflict($position);

            return;
        }

        $this->model->updateAttributes([$this->sortAttribute => $sort]);
    }

    /**
     * @param array $interval
     * @return boolean|integer
     */
    protected function getSortByInterval($interval)
    {
        $difference = $interval[1] - $interval[0];
        if ($difference < 2) {
            return false;
        }

        return (int) ($interval[0] + round($difference / 2));
    }

    /**
     * @param integer $position
     * @return array
     */
    protected function getNewInterval($position)
    {
        if ($position < $this->getSortablePosition()) {
            // Moving forward
            $offset = $position - 2;
        } else {
            // Moving back
            $offset = $position - 1;
        }

        $result = $this->query
            ->select($this->sortAttribute)
            ->offset($offset)
            ->limit(2)
            ->asArray()
            ->all();

        return ArrayHelper::getColumn($result, $this->sortAttribute);
    }

    /**
     * @return boolean
     */
    protected function isIncreasedLimitReached()
    {
        $sort = $this->getLastSort() + $this->_intervalSize;

        return round($sort / $this->_intervalSize) - $this->getSortableCount() > $this->increasingLimit;
    }
}
