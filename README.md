# Yii 2 Sortable

Sortable ActiveRecord for Yii 2 framework

- [Installation](#installation)
- [Features](#features)
- [Behaviors types](#behaviors-types)
- [Preparing table structure](#preparing-table-structure)
- [Attaching behavior](#attaching-behavior)
- [Configuring behavior](#configuring-behavior)
- [Changing order of models inside sortable scope](#changing-order-of-models-inside-sortable-scope)
- [GUI for changing order](#gui-for-changing-order)
- [Custom GUI for changing order](#custom-gui-for-changing-order)

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist arogachev/yii2-sortable
```

or add

```
"arogachev/yii2-sortable": "*"
```

to the require section of your `composer.json` file.

## Features

- Several implemented algorithms. Choose one to fit your needs.
- Setting of sortable scope. In this case the order of models in each scope is managed separately.
- Additional setting of sortable condition. For example, if the model can be marked as active or deleted,
you can additionally specify that condition and it will be considered when changing these attributes values.
- Order auto adjustment when adding new model, removing out of sortable scope,
moving between the sortable scopes.
- Changing order of models inside sortable scope.
- GUI for managing order of models in `GridView` (`SortableColumn`).
- Sort controller for simplifying writing of own GUI

## Behaviors types

There are several behaviors to choose from:

- `ContinuousNumericalSortableBehavior`
- `IntervalNumericalSortableBehavior`
- `LinkedListSortableBehavior` (currently not implemented)

The first two are numerical behaviors and they have one thing in common - they store position of each model as number.

`ContinuousNumericalSortableBehavior`:

Stored number is equal to exact position.

**Advantages:**

- You can get current position without additional queries

**Disadvantages:**

- Amount of `UPDATE` queries can be large depending on amount of sortable models and situation.
It relates to adjustment order. For example no extra queries will be performed in case of switching models
with 3 and 4 position (only 2 `UPDATE` queries). But if you have 1000 models and you move the last model
to the very beginning there will be 1000 `UPDATE` queries (so it depends on interval length).

`IntervalNumericalSortableBehavior`:

The numbers are stored with certain intervals (initially with equal size).
You can see the basic description of the used algorithm [here](http://stackoverflow.com/questions/6804166/what-is-the-most-efficient-way-to-store-a-sort-order-on-a-group-of-records-in-a/6804302#6804302).

**Advantages:**

- For adding or deletion there is no need to adjust order of other models. And for changing order for most of the times
only few `SELECT` and one `UPDATE` query will be executed.
The full adjustment of order of all models inside of sortable scope is only required in case of conflict.
The conflicts don't happen often if you set interval size big enough
and don't move models to the same position over and over again.

**Disadvantages:**

- For getting positions of models extra query is used.

### Preparing table structure

In case of using numerical behaviors add this to migration:

```php
$this->addColumn('table_name', 'sort', Schema::TYPE_INTEGER . ' NOT NULL');
```

## Attaching behavior

Add this to your model for minimal setup:

```php
use arogachev\sortable\behaviors\numerical\ContinuousNumericalSortableBehavior;
```

```php
/**
 * @inheritdoc
 */
public function behaviors()
{
    return [
        [
            'class' => ContinuousNumericalSortableBehavior::className(),
        ],
    ];
}
```

### Configuring behavior

**Common properties for all behaviors:**

`scope` - sortable scope. Specify it if you want to separate models by condition
and manage order independently in each one. It expects closure returning `ActiveQuery`, but `where` part must be
specified as array only. Example:

```php
function () {
    return Question::find()->where(['test_id' => $this->test_id]);
}
```

If this property is not set, all models considered as one sortable scope.

`sortableCondition` - additional property to filter sortable models. You should specify it as conditional array:

```php
[
    'is_active' => 1,
    'is_deleted' => 0,
],
```

`prependAdded` - insert added sortable model to the beginning of sortable scope. Defaults to `false` which means
inserting to the end.

`access` - closure for checking access to sort for current user. Example:

```php
function () {
    return Yii::$app->user->can('questions.sort');
}
```

**Numerical behaviors properties:**

`sortAttribute` - name of the sort attribute column. Defaults to `sort`.

**`IntervalNumericalSortableBehavior` properties:**

`intervalSize` - size of the interval. Defaults to `1000`. When specifying bigger numbers,
conflicts will happen less often.

`increasingLimit` - the number of times user can continuously move item to the end of the sortable scope.
Used to prevent increasing of numbers. Defaults to `10`.

## Changing order of models inside sortable scope

The behavior provides few methods to change any sortable model order:

- `moveToPosition($position)` - basic method for moving model to any position inside sortable scope
- `moveBefore($pk = null)` - move model before another model of this sortable scope.
If `$pk` is not specified it will be moved to the very end
- `moveAfter($pk = null)` - move model after another model of this sortable scope
If `$pk` is not specified it will be moved to the very beginning
- `moveBack()` - move back by one position
- `moveForward()` - move forward by one position
- `moveAsFirst()` - move to the very beginning
- `moveAsLast()` - move to the very end

## GUI for changing order

There is special `SortableColumn` for `GridView`.

**Features:**

- It doesn't force you to use the whole another `GridView`
- No need to attach additional actions every time
- Multiple `GridView` on one page support
- Displaying current position
- Inline editing of current position
- Moving with drag and drop (with `jQuery UI Sortable`) with special handle icon,
so you can interact with other data without triggering sort change
- Moving back and forward by one position
- Moving as first and last

Include once this to your application config:

```php
'controllerMap' => [
    'sort' => [
        'class' => 'arogachev\sortable\controllers\SortController',
    ],
],
```

Then configure `GridView`:

- Wrap it with `Pjax` widget for working without page reload
- Add `id` for unchangeable root container
- Include column in `columns` section

```php
use arogachev\grid\SortableColumn;
```

```php
<div class="question-index" id="question-sortable">
    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        // Other configuration
        'columns' => [
            [
                'class' => SortableColumn::className(),
                'gridContainerId' => 'question-sortable',
            ],
            // Other columns
        ],
    ]) ?>

    <?php Pjax::end(); ?>
</div>
```

You can configure display through `template` and `buttons` properties (similar to [ActionColumn](http://www.yiiframework.com/doc-2.0/yii-grid-actioncolumn.html)).

The available tags are:

- `currentPosition`
- `moveWithDragAndDrop`
- `moveForward`
- `moveBack`
- `moveAsFirst`
- `moveAsLast`

You can extend it with your own. Example of overriding:

```php
'template' => '<div class="sortable-section">{moveWithDragAndDrop}</div>
<div class="sortable-section">{currentPosition}</div>
<div class="sortable-section">{moveForward} {moveBack}</div>',
'buttons' => [
    'moveForward' => function () {
        return Html::tag('i', '', [
            'class' => 'fa fa-arrow-circle-left',
            'title' => Yii::t('sortable', 'Move forward'),
        ]);
    },
    'moveBack' => function () {
        return Html::tag('i', '', [
            'class' => 'fa fa-arrow-circle-right',
            'title' => Yii::t('sortable', 'Move back'),
        ]);
    },
],
```

## Custom GUI for changing order

If you want to write your own GUI for changing order without using `GridView`, you can use the `SortController` actions:

- `move-before` (requires `pk` of the next element after move sent via `POST`)
- `move-after` (requires `pk` of the previous element after move sent via `POST`)
- `move-back`
- `move-forward`
- `move-as-first`
- `move-as-last`
- `move-to-position` (requires `position` sent via `POST`)

For all of the actions these two parameters must exist in `POST`:

- `modelClass` - model full class name with namespace
- `modelPk` - moved model primary key value (pass object in case of primary keys)
