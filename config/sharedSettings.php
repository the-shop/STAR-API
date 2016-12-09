<?php

return [
    /**
     * Fixed Star API configuration
     */
    'internal' => [
        'projects' => [
            'reservation' => [
                'maxReservationTime' => env('PROJECT_RESERVATION_TIME', 30)
            ]
        ],
//        'slack' => [
//            'teamInfo' => '' // stagod da vrati resolver method
//        ]
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
                'settingName' => 'teamInfo'// tu spremi return value iz ...\Slack->getNestaId
            ]
        ],
        'bla' => [
            [
                'bla' => [
                    'class' => \Vluzrmos\SlackApi\Facades\SlackTeam::class,
                    'method' => 'info'
                ],
                'settingName' => 'teamInfo'// tu spremi return value iz ...\Slack->getNestaId
            ],
            [
                'resolver' => [
                    'class' => \Vluzrmos\SlackApi\Facades\SlackTeam::class,
                    'method' => 'info'
                ],
                'settingName' => 'blabla'// tu spremi return value iz ...\Slack->getNestaId
            ]
        ]
    ]

];