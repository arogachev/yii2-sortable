<?php

namespace data;

use arogachev\sortable\behaviors\numerical\IntervalNumericalSortableBehavior;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * @property integer $id
 * @property integer $test_id
 * @property integer $sort
 * @property string $content
 * @property integer $is_active
 */
class IntervalQuestion extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'sort' => [
                'class' => IntervalNumericalSortableBehavior::className(),
                'scope' => function () {
                    return IntervalQuestion::find()->where(['test_id' => $this->test_id]);
                },
                'sortableCondition' => [
                    'is_active' => 1,
                ],
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'interval_questions';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['is_active', 'default', 'value' => 1],
        ];
    }

    /**
     * @return array
     */
    public function getOtherQuestionsSort()
    {
        $questions = static::find()
            ->where(['test_id' => $this->test_id])
            ->andWhere(['<>', 'id', $this->id])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        return ArrayHelper::getColumn($questions, 'sort');
    }
}
