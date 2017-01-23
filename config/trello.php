<?php

return [

    'lists' => ['Information', 'Pending', 'Doing', 'Done'],
    'cards' => [
        [
            'list' => 'Information',
            'name' => 'Trello Instructions',
            'description' => 'Unassigned list contains newly created items that are currently waiting for someone to tackle them.
                                           Pending list is for newly created items that are assigned to someone but not started yet.
                                           Move the item to "Doing" list once you start working on it
                                           Once done, move the item to "Done" list'
        ],
        [
            'list' => 'Information',
            'name' => 'Payouts and estimated time',
            'description' => 'Each paid ticket will have this information in the ticket description.
                                                  Process around this is that when ticket is ready for execution information will be added to the ticket and whoever takes the ticket will communicate the start / end date with @mladen on Slack.
                                                  Once the delivery date is set, person assigned to the ticket moves the ticket to Doing list and sets the delivery time - this way it\'s visible who accepted what task at what time with exact delivery time.
                                                  Final step in ticket handling (other than archiving tickets) is that person assigned to ticket has to move the ticket to Done list once completed.
                                                  Final price will be calculated based on timeliness after everything is signed of as done by @mladen.
                                                  There will be positive and negative impacts on XP and the total price based on timeliness defined by chart that can be found on company wiki page for payouts'
        ]
    ],
    'trello_username' => getenv('TRELLO_USERNAME'),
    'trello_key' => getenv('TRELLO_KEY'),
    'trello_secret' => getenv('TRELLO_SECRET')
];
