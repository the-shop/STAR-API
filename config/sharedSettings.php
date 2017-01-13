<?php

return [
    /**
     * Fixed Star API configuration
     */
    'internalConfiguration' => [
        'projects' => [
            'reservation' => [
                'maxReservationTime' => env('PROJECT_RESERVATION_TIME', 30)
            ]
        ],
        'taskHistoryStatuses' => [
            'assigned' => 'Task assigned to %s',
            'claimed' => 'Task claimed by %s',
            'paused' => 'Task paused by %s because of: "%s"',
            'qa_ready' => 'Task ready for QA',
            'qa_fail' => 'Task failed QA',
            'qa_success' => 'Task passed QA',
        ],
        'profile_update_xp_message' => 'Hey, your XP changed by {N}',
        'guestRole' => 'guest',
        'defaultRole' => 'standard',
        'skills' => [
            'PHP',
            'React',
            'Cloud',
            'Node'
        ],
        'webDomain' => env('WEB_DOMAIN', 'http://the-shop.io:3000/')
    ],

    /**
     * Dynamic configuration that depends on external services
     */
    'externalConfiguration' => [
        'slack' => [
            [
                'resolver' => [
                    'class' => \Vluzrmos\SlackApi\Facades\SlackTeam::class,
                    'method' => 'info'
                ],
                'settingName' => 'teamInfo'
            ]
        ],
    ]

];
