<?php

return [
    /**
     * Fixed Star API configuration
     */
    'internal' => [
        'projects' => [
            'reservation' => [
                'maxReservationTime' => env('RESERVATION_TIME', 30)
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
                    'class' => \App\ExternalServices\Slack::class,
                    'method' => 'getTeamInfo'
                ],
                'settingName' => 'teamInfo'// tu spremi return value iz ...\Slack->getNestaId
            ]
        ]
    ]

];