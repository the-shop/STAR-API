<?php

namespace App\Http\Controllers;

use App\GenericModel;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Helpers\MailSend;

class EmailController extends Controller
{
    public function sendEmail(Request $request)
    {
        $to = $request->input('to');
        $message = $request->input('message');

        $errors = [];

        $recipient = $this->validateInput($to, $message, $errors);

        if ($recipient === false) {
            return $this->jsonError($errors, 400);
        }

        //send email to list of users
        $data = [
            'message' => $message
        ];
        $view = 'emails.message';
        $subject = 'This is an email!';

        if (is_array($recipient)) {
            foreach ($recipient as $user) {
                MailSend::send($view, $data, $user, $subject);
            }
        } else {
            MailSend::send($view, $data, $user, $subject);
        }

    }

    private function validateInput($to, $message, &$errors)
    {
        if (empty($to)) {
            $errors[] = 'Empty recipient field.';
        }

        //check if message field is empty
        if (empty($message)) {
            $errors[] = 'Empty message field.';
        }

        GenericModel::setCollection('profiles');
        $recipient = [];

        if (is_array($to)) {
            foreach ($to as $t) {
                $profile = GenericModel::find($t);
                if ($profile !== null) {
                    $recipient['name'] = $profile->name;
                    $recipient['email'] = $profile->email;
                }
            }
            if (empty($recipient)) {
                $errors[] = 'Users does not exist.';
            }

            if (count($errors) > 0) {
                return false;
            }

            return $recipient;
        }

        $profile = GenericModel::find($to);

        if ($profile === null) {
            $errors[] = 'User does not exist.';
        }

        if (count($errors) > 0) {
            return false;
        }

        $recipient['name'] = $profile->name;
        $recipient['email'] = $profile->email;

        return $recipient;
    }
}
