<?php

namespace App\Helpers;


class MailSend
{
    /**
     * Send email to user
     * @param $view
     * @param $data
     * @param $profileEmail
     * @param $profileName
     * @param $subject
     * @return bool
     */
    public static function send($view, $data, $profileEmail, $profileName, $subject)
    {
        $mailConfig = \Config::get('mail.private_mail_send');

        if ($mailConfig === true) {
            $emailFrom = \Config::get('mail.private_mail_from');
            $emailName = \Config::get('mail.private_mail_name');

            \Mail::send($view, $data, function ($message) use (
                $profileEmail, $profileName, $emailFrom, $emailName,
                $subject
            ) {
                $message->from($emailFrom, $emailName);
                $message->to($profileEmail, $profileName)->subject($emailName . ' - ' . $subject);
            });

            return true;
        }

        return false;
    }
}