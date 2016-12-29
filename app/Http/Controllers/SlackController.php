<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Class SlackController
 * @package App\Http\Controllers
 */
class SlackController extends Controller
{
    /**
     * Get list of all users within Slack Company
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsers()
    {
        $users = $this->users();
        if ($users === false) {
            return $this->jsonError(['Unable to get users.'], 404);
        }

        return $this->jsonSuccess($users);
    }

    /**
     * Get list of all channels within Slack Company
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChannels()
    {
        $channels = $this->channels();
        if ($channels === false) {
            return $this->jsonError(['Unable to get channels.'], 404);
        }

        return $this->jsonSuccess($channels);
    }

    /**
     * Send message to slack
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        $to = $request->input('to');
        $message = $request->input('message');

        $errors = [];

        $recipients = $this->validateInput($to, $message, $errors);

        if ($recipients === false) {
            return $this->jsonError($errors, 400);
        }

        //send message to list of users
        foreach ($recipients as $recipient) {
            \SlackChat::message($recipient, $message);
        }

        return $this->jsonSuccess(
            [
                'sent' => true,
                'to' => $recipients,
                'message' => $message
            ]
        );
    }

    /**
     * Validate slack users list
     * @return array|bool
     */
    private function users()
    {
        $users = \SlackUser::lists();
        if ($users->ok === true) {
            $result = [];
            foreach ($users->members as $user) {
                $result[] = [
                    'id' => $user->id,
                    'name' => $user->name
                ];
            }
            return $result;
        }
        return false;
    }

    /**
     * Validate slack channels list
     * @return array|bool
     */
    private function channels()
    {
        $channels = \SlackChannel::lists();
        if ($channels->ok === true) {
            $result = [];
            foreach ($channels->channels as $channel) {
                $result[] = [
                    'id' => $channel->id,
                    'name' => $channel->name
                ];
            }
            return $result;
        }
        return false;
    }

    /**
     * Validate input
     * @param $to
     * @param $errors
     * @return bool
     */
    private function validateInput($to, $message, &$errors)
    {
        //check if recipient field is empty
        if (empty($to)) {
            $errors[] = 'Empty recipient field.';
        }

        //check if message field is empty
        if (empty($message)) {
            $errors[] = 'Empty message field.';
        }

        //validate input if recipient is list of IDs
        $recipients = [];
        if (is_array($to)) {
            $users = $this->users();
            $channels = $this->channels();
            if ($users === false || $channels === false) {
                $errors[] = 'Unable to get list of Ids. Check if token is provided.';
            } else {
                foreach ($to as $t) {
                    foreach ($users as $user) {
                        if ($t === $user['id']) {
                            $recipients[] = '@' . $user['name'];
                        }
                    }
                    foreach ($channels as $channel) {
                        if ($t === $channel['id']) {
                            $recipients[] = '#' . $channel['name'];
                        }
                    }
                }
            }
            if (empty($recipients)) {
                $errors[] = 'Invalid recipient input. Ids not found.';
            }
        } else {
            $errors[] = 'Invalid recipient input. Must be type of array.';
        }

        if (count($errors) > 0) {
            return false;
        }

        return $recipients;
    }
}
