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

        $recipient = $this->validateInput($to, $message, $errors);

        if ($recipient === false) {
            return $this->jsonError($errors, 404);
        }
        
        \SlackChat::message($recipient, $message);
        return $this->jsonSuccess('Message sent.');
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
                $result[] = $user->name;
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
                $result[] = $channel->name;
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

        //validate input
        if (strpos($to, '@') !== false) {
            $users = $this->users();
            if ($users === false) {
                $errors[] = 'Unable to get users.';
            }
            elseif (!in_array(substr($to, 1), $users)) {
                $errors[] = 'User does not exist.';
            }
        } elseif (strpos($to, '#') !== false) {
            $channels = $this->channels();
            if ($channels === false) {
                $errors[] = 'Unable to get channels.';
            }
            elseif (!in_array(substr($to, 1), $channels)) {
                $errors[] = 'Channel does not exist.';
            }
        } else {
            $errors[] = 'Invalid recipient input.';
        }
        
        if (count($errors) > 0) {
            return false;
        }
        
        return $to;
    }
}
