<?php

namespace {

    use Illuminate\Database\Seeder;

    class ValidationsSeeder extends Seeder
    {
        /**
         * Run the database seeds.
         *
         * @return void
         */
        public function run()
        {
            // delete records from acl collection
            DB::collection('validations')->delete();

            // insert records into acl collection
            DB::collection('validations')->insert(
                [
                    [
                        'fields' => [
                            'name' => 'required|regex:/\\w+ \\w+/',
                            'password' => 'required|min:8',
                            'email' => 'required|email|unique:profiles',
                            'slack' => 'alpha_dash',
                            'trello' => 'alpha_dash',
                            'github' => 'alpha_dash',
                            'xp' => 'alpha_num',
                            'xp_id' => 'alpha_num',
                            'active' => 'boolean',
                            'employee' => 'boolean'

                        ],
                        'messages' => [
                            'name.regex' => 'Full name needed, at least 2 words.'
                        ],
                        'resource' => 'profiles',
                        'acl' => [
                            'standard' => [
                                'editable' => [
                                    'name',
                                    'password',
                                    'email',
                                    'slack',
                                    'trello',
                                    'github',
                                    'active'
                                ],
                                'GET' => true,
                                'DELETE' => false,
                                'POST' => false
                            ],
                            'guest' => [
                                'editable' => [],
                                'GET' => true,
                                'DELETE' => false,
                                'POST' => true
                            ]
                        ]
                    ], [
                    'fields' => [
                        'name' => 'required|alpha_num',
                        'description' => 'alpha_num',
                        'start' => 'date',
                        'end' => 'date',
                        'price' => 'alpha_num',
                        'trello_link' => 'alpha_dash',
                        'acceptedBy' => 'alpha_num',
                        'sprints' => 'alpha_num',
                        'members' => 'alpha_num',
                    ],
                    'resource' => 'projects',
                    'acl' => [
                        'standard' => [
                            'editable' => [],
                            'GET' => true,
                            'DELETE' => false,
                            'POST' => false
                        ]
                    ]
                    ], [
                    'fields' => [
                    ],
                    'resource' => 'comments',
                    'acl' => [
                        'standard' => [
                            'editable' => [],
                            'GET' => true,
                            'DELETE' => false,
                            'POST' => false
                        ]
                    ]
                    ], [
                    'fields' => [
                    ],
                    'resource' => 'xp',
                    'acl' => [
                        'standard' => [
                            'editable' => [],
                            'GET' => true,
                            'DELETE' => false,
                            'POST' => false
                        ]
                    ]
                    ], [
                    'fields' => [
                    ],
                    'resource' => 'sprints',
                    'acl' => [
                        'standard' => [
                            'editable' => [],
                            'GET' => true,
                            'DELETE' => false,
                            'POST' => false
                        ]
                    ]
                    ], [
                    'fields' => [
                        'submitted_for_qa' => 'boolean',
                    ],
                    'resource' => 'tasks',
                    'acl' => [
                        'standard' => [
                            'editable' => [
                                'submitted_for_qa',
                                'task_history',
                            ],
                            'GET' => true,
                            'DELETE' => false,
                            'POST' => false
                        ]
                    ]
                    ]
                ]
            );
        }
    }
}
