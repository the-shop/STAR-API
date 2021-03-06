<?php

namespace App\Listeners;

use App\GenericModel;
use Illuminate\Support\Facades\Auth;

class GenericModelHistory
{
    /**
     * Handle the event.
     * @param \App\Events\GenericModelHistory $event
     */
    public function handle(\App\Events\GenericModelHistory $event)
    {
        $model = $event->model;
        if ($model->isDirty()) {
            $newAllAttributes = $model->getAttributes();
            $newValues = $model->getDirty();
            $oldValues = $model->getOriginal();

            $modelHistoryRecord = $this->getHistoryRecord($model);
            $history = $modelHistoryRecord->history;
            $unixTime = (new \DateTime())->format('U');

            foreach ($newValues as $newField => $newValue) {
                if (key_exists($newField, $oldValues)) {
                    $history[] = [
                        'profileId' => Auth::user()->id,
                        'filedName' => $newField,
                        'oldValue' => $oldValues[$newField],
                        'newValue' => $newValue,
                        'timestamp' => (int) ($unixTime . '000') // Microtime
                    ];
                } else {
                    $history[] = [
                        'profileId' => Auth::user()->id,
                        'fieldName' => $newField,
                        'oldValue' => null,
                        'newValue' => $newValue,
                        'timestamp' => (int) ($unixTime . '000')
                    ];
                }
            }

            foreach ($oldValues as $oldFieldName => $oldFieldValue) {
                if (!key_exists($oldFieldName, $newAllAttributes)) {
                    $history[] = [
                        'profileId' => Auth::user()->id,
                        'fieldName' => $oldFieldName,
                        'oldValue' => $oldFieldValue,
                        'newValue' => null,
                        'timestamp' => (int) ($unixTime . '000')
                    ];
                }
            }
            $modelHistoryRecord->history = $history;
            $modelHistoryRecord->save();
        }
    }

    /**
     * Helper to get model history record from document-history collection
     * @param GenericModel $model
     * @return GenericModel
     */
    private function getHistoryRecord(GenericModel $model)
    {
        $oldCollection = GenericModel::getCollection();
        GenericModel::setCollection('document-history');
        if (!$model->history_id) {
            $modelHistoryRecord = new GenericModel(['history' => []]);
            $modelHistoryRecord->save();
            $model->history_id = $modelHistoryRecord->_id;
        } else {
            $modelHistoryRecord = GenericModel::find($model->history_id);
        }
        GenericModel::setCollection($oldCollection);

        return $modelHistoryRecord;
    }
}
