<?php

use data\IntervalQuestion;
use yii\codeception\TestCase;
use yii\helpers\ArrayHelper;

class IntervalText extends TestCase
{
    /**
     * @inheritdoc
     */
    public $appConfig = '@tests/unit/_config.php';


    public function testCreate()
    {
        $question = new IntervalQuestion([
            'test_id' => 1,
            'content' => 'Do you have a car?',
        ]);
        $question->save();

        $this->checkQuestion($question, 6000, [1000, 2000, 3000, 4000, 5000]);
    }

    public function testCreateNotActive()
    {
        $question = new IntervalQuestion([
            'test_id' => 1,
            'content' => 'Do you have a car?',
            'is_active' => false,
        ]);
        $question->save();

        $this->checkQuestion($question, 0, [1000, 2000, 3000, 4000, 5000]);
    }

    public function testCreatePrependAdded()
    {
        $question = new IntervalQuestion();
        $behaviorConfig = $question->behaviors()['sort'];
        $behaviorConfig['prependAdded'] = true;
        $question->detachBehavior('sort');
        $question->attachBehavior('sort', $behaviorConfig);
        $question->setAttributes([
            'test_id' => 1,
            'content' => 'Do you have a car?',
        ], false);
        $question->save();

        $this->checkQuestion($question, 500, [1000, 2000, 3000, 4000, 5000]);
    }

    public function testUpdate()
    {
        $question = $this->findQuestion(3);
        $question->is_active = false;
        $question->save();

        $this->checkQuestion($question, 0, [1000, 2000, 4000, 5000]);

        $question->is_active = true;
        $question->save();

        $this->checkQuestion($question, 6000, [1000, 2000, 4000, 5000]);

        $question->runBeforeSave = true;
        $question->save();

        $this->checkQuestion($question, 0, [1000, 2000, 4000, 5000]);

        $question->save();

        $this->checkQuestion($question, 6000, [1000, 2000, 4000, 5000]);
    }

    public function testDelete()
    {
        $question = $this->findQuestion(3);
        $question->delete();

        $this->checkQuestion($question, null, [1000, 2000, 4000, 5000]);
    }

    public function testMoveToOtherScope()
    {
        $question = $this->findQuestion(3);
        $question->test_id = 2;
        $question->save();

        $this->checkQuestion($question, 6000, [1000, 2000, 3000, 4000, 5000], false);
        $this->checkOtherTestQuestions(1, [1000, 2000, 4000, 5000]);
    }

    public function testMoveToPosition()
    {
        $question = $this->findQuestion(3);
        $question->moveToPosition(2);

        $this->checkQuestion($question, 1500, [1000, 2000, 4000, 5000]);
    }

    public function testMoveBefore()
    {
        $question = $this->findQuestion(3);
        $question->moveBefore(2);

        $this->checkQuestion($question, 1500, [1000, 2000, 4000, 5000]);
    }

    public function testMoveAfter()
    {
        $question = $this->findQuestion(3);
        $question->moveAfter(4);

        $this->checkQuestion($question, 4500, [1000, 2000, 4000, 5000]);
    }

    public function testMoveBack()
    {
        $question = $this->findQuestion(3);
        $question->moveBack();

        $this->checkQuestion($question, 4500, [1000, 2000, 4000, 5000]);
    }

    public function testMoveForward()
    {
        $question = $this->findQuestion(3);
        $question->moveForward();

        $this->checkQuestion($question, 1500, [1000, 2000, 4000, 5000]);
    }

    public function testMoveAsFirst()
    {
        $question = $this->findQuestion(3);
        $question->moveAsFirst();

        $this->checkQuestion($question, 500, [1000, 2000, 4000, 5000]);
    }

    public function testMoveAsLast()
    {
        $question = $this->findQuestion(3);
        $question->moveAsLast();

        $this->checkQuestion($question, 6000, [1000, 2000, 4000, 5000]);
    }

    /**
     * @param integer $testId
     * @param array $ids
     */
    protected function checkOtherTestQuestions($testId, $ids = [1000, 2000, 3000, 4000, 5000])
    {
        $questions = IntervalQuestion::find()
            ->where(['test_id' => $testId])
            ->orderBy(['id' => SORT_ASC])
            ->all();
        $questionsSort = ArrayHelper::getColumn($questions, 'sort');

        $this->assertEquals($ids, $questionsSort, 'Other scope models sort matches');
    }


    /**
     * @param integer $id
     * @return IntervalQuestion
     */
    protected function findQuestion($id)
    {
        return IntervalQuestion::findOne($id);
    }

    /**
     * @param IntervalQuestion $question
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
