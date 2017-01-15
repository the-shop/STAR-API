<?php

namespace App\Console\Commands;

use App\Helpers\MailSend;
use App\Profile;
use App\Services\ProfilePerformance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

/**
 * Class EmailProfilePerformance
 * @package App\Console\Commands
 */
class EmailProfilePerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:profile:performance {daysAgo : How many days before time of command execution}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregates user performance and sends out emails';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $performance = new ProfilePerformance();

        $profiles = Profile::all();

        $daysAgo = $this->argument('daysAgo');
        $unixNow = (new \DateTime())->format('U');
        $unixAgo = $unixNow - (int) $daysAgo * 24 * 60 * 60;

        $adminAggregation = [];

        foreach ($profiles as $profile) {
            $data = $performance->aggregateForTimeRange($profile, $unixAgo, $unixNow);
            $data['name'] = $profile->name;
            $data['fromDate'] = \DateTime::createFromFormat('U', $unixAgo)->format('Y-m-d');
            $data['toDate'] = \DateTime::createFromFormat('U', $unixNow)->format('Y-m-d');

            $view = 'emails.profile.performance';
            $subject = Config::get('mail.private_mail_subject');

            MailSend::send($view, $data, $profile, $subject);

            $adminAggregation[] = $data;
        }

        $admins = Profile::where('admin', '=', true)->get();

        foreach ($admins as $admin) {
            $view = 'emails.profile.admin-performance-report';
            $subject = Config::get('mail.admin_performance_email_subject');

            MailSend::send($view, ['reports' => $adminAggregation], $admin, $subject);
        }
    }
}
