<?php

use data\Category;
use data\ContinuousQuestion;
use yii\codeception\TestCase;
use yii\helpers\ArrayHelper;

class ContinuousTest extends TestCase
{
    /**
     * @inheritdoc
     */
    public $appConfig = '@tests/unit/_config.php';


    public function testCreate()
    {
        $question = new ContinuousQuestion([
            'test_id' => 1,
            'content' => 'Do you have a car?',
        ]);
        $question->save();

        $this->checkQuestion($question, 6, [1, 2, 3, 4, 5]);
    }

    public function testCreateNotActive()
    {
        $question = new ContinuousQuestion([
            'test_id' => 1,
            'content' => 'Do you have a car?',
            'is_active' => false,
        ]);
        $question->save();

        $this->checkQuestion($question, 0, [1, 2, 3, 4, 5]);
    }

    public function testCreatePrependAdded()
    {
        $question = new ContinuousQuestion();
        $behaviorConfig = $question->behaviors()['sort'];
        $behaviorConfig['prependAdded'] = true;
        $question->detachBehavior('sort');
        $question->attachBehavior('sort', $behaviorConfig);
        $question->setAttributes([
            'test_id' => 1,
            'content' => 'Do you have a car?',
        ], false);
        $question->save();

        $this->checkQuestion($question, 1, [2, 3, 4, 5, 6]);
    }

    public function testUpdate()
    {
        $question = $this->findQuestion(3);
        $question->is_active = false;
        $question->save();

        $this->checkQuestion($question, 0, [1, 2, 3, 4]);

        $question->is_active = true;
        $question->save();

        $this->checkQuestion($question, 5, [1, 2, 3, 4]);

        $question->runBeforeSave = true;
        $question->save();

        $this->checkQuestion($question, 0, [1, 2, 3, 4]);

        $question->save();

        $this->checkQuestion($question, 5, [1, 2, 3, 4]);
    }

    public function testDelete()
    {
        $question = $this->findQuestion(3);
        $question->delete();

        $this->checkQuestion($question, null, [1, 2, 3, 4]);
    }

    public function testMoveToOtherScope()
    {
        $question = $this->findQuestion(3);
        $question->test_id = 2;
        $question->save();

        $this->checkQuestion($question, 6, [1, 2, 3, 4, 5], false);
        $this->checkOtherTestQuestions(1, [1, 2, 3, 4]);

        // Model related scope

        /* @var $category Category */
        $category = Category::findOne(3);
        $category->parent_id = 2;
        $category->save();
        $category->moveAfter(7);
        $sort = Category::find()->select('sort')->orderBy(['id' => SORT_ASC])->column();

        $this->assertEquals([1, 1, 3, 2, 2, 1, 2, 4], $sort);
    }

    public function testMoveToPosition()
    {
        $question = $this->findQuestion(3);
        $question->moveToPosition(2);

        $this->checkQuestion($question, 2, [1, 3, 4, 5]);
    }

    public function testMoveBefore()
    {
        $question = $this->findQuestion(3);
        $question->moveBefore(2);

        $this->checkQuestion($question, 2, [1, 3, 4, 5]);
    }

    public function testMoveAfter()
    {
        $question = $this->findQuestion(3);
        $question->moveAfter(4);

        $this->checkQuestion($question, 4, [1, 2, 3, 5]);
    }

    public function testMoveBack()
    {
        $question = $this->findQuestion(3);
        $question->moveBack();

        $this->checkQuestion($question, 4, [1, 2, 3, 5]);
    }

    public function testMoveForward()
    {
        $question = $this->findQuestion(3);
        $question->moveForward();

        $this->checkQuestion($question, 2, [1, 3, 4, 5]);
    }

    public function testMoveAsFirst()
    {
        $question = $this->findQuestion(3);
        $question->moveAsFirst();

        $this->checkQuestion($question, 1, [2, 3, 4, 5]);
    }

    public function testMoveAsLast()
    {
        $question = $this->findQuestion(3);
        $question->moveAsLast();

        $this->checkQuestion($question, 5, [1, 2, 3, 4]);
    }

    /**
     * @param integer $testId
     * @param array $ids
     */
    protected function checkOtherTestQuestions($testId, $ids = [1, 2, 3, 4, 5])
    {
        $questions = ContinuousQuestion::find()
            ->where(['test_id' => $testId])
            ->orderBy(['id' => SORT_ASC])
            ->all();
         $questionsSort = ArrayHelper::getColumn($questions, 'sort');

        $this->assertEquals($ids, $questionsSort, 'Other scope models sort matches');
    }


    /**
     * @param integer $id
     * @return ContinuousQuestion
     */
    protected function findQuestion($id)
    {
        return ContinuousQuestion::findOne($id);
    }

    /**
     * @param ContinuousQuestion $question
     * @param null|integer $questionSort
     * @param array $otherQuestionsSort
     * @param boolean $checkOtherTestQuestions
     */
    protected function checkQuestion($question, $questionSort = null, $otherQuestionsSort, $checkOtherTestQuestions = true)
    {
        if ($questionSort) {
            $this->assertEquals($questionSort, $question->sort, 'Model sort matches');
        }

        $this->assertEquals($otherQuestionsSort, $question->getOtherQuestionsSort(), 'Other models sort matches');

        if ($checkOtherTestQuestions) {
            $this->checkOtherTestQuestions(2);
        }
    }
}
