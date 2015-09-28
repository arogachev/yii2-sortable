<?php

namespace data;

use arogachev\sortable\behaviors\numerical\ContinuousNumericalSortableBehavior;
use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property integer $parent_id
 * @property string $name
 * @property string $text
 */
class Category extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'sort' => [
                'class' => ContinuousNumericalSortableBehavior::className(),
                'scope' => function ($model) {
                    /* @var $model Category */
                    return $model->getNeighbors();
                },
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'categories';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['name', 'string', 'max' => 255],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNeighbors()
    {
        return static::find()->where(['parent_id' => $this->parent_id]);
    }
}
