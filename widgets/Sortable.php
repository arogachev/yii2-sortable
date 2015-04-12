<?php

namespace arogachev\sortable\widgets;

use yii\jui\Sortable as BaseSortable;

class Sortable extends BaseSortable
{
    /**
     * @inheritdoc
     */
    protected function registerClientEvents($name, $id)
    {
        if (!empty($this->clientEvents)) {
            $js = [];
            foreach ($this->clientEvents as $event => $handler) {
                if (!is_array($handler)) {
                    if (isset($this->clientEventMap[$event])) {
                        $eventName = $this->clientEventMap[$event];
                    } else {
                        $eventName = strtolower($name . $event);
                    }
                    $js[] = "jQuery('#$id').on('$eventName', $handler);";
                } else {
                    foreach ($handler as $selector => $singleHandler) {
                        $js[] = "jQuery('#$id').on('$event', '$selector', $singleHandler);";
                    }
                }
            }
            $this->getView()->registerJs(implode("\n", $js));
        }
    }
}
