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
        'profile_update_xp_message' => 'Hey, your XP changed by {N}',
        'guestRole' => 'guest',
        'defaultRole' => 'standard',
        'skillSet' => [
            'PHP',
            'React',
            'Cloud',
            'Node'
        ]
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
