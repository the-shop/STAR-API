<?php

namespace App\Helpers;

use App\GenericModel;

/**
 * Class Slack
 * @package App\Helpers
 */
class Slack
{
    const HIGH_PRIORITY = 0;
    const MEDIUM_PRIORITY = 1;
    const LOW_PRIORITY = 2;

    /**
     * @param $recipient
     * @param $message
     * @param int $priority
     * @return GenericModel
     */
    public static function sendMessage($recipient, $message, $priority = self::MEDIUM_PRIORITY)
    {
        $oldCollection = GenericModel::getCollection();
        GenericModel::setCollection('slackMessages');
        $record = new GenericModel();
        $record->recipient = $recipient;
        $record->message = $message;
        $record->priority = $priority;
        $record->sent = false;
        $record->timeAdded = (int) (new \DateTime())->format('U');
        $record->save();
        GenericModel::setCollection($oldCollection);

        return $record;
    }
}
