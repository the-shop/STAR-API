<?php

namespace {

    use Illuminate\Database\Seeder;

    /**
     * Class AclCollectionSeeder
     */
    class AclCollectionSeeder extends Seeder
    {
        /**
         * Run the database seeds.
         *
         * @return void
         */
        public function run()
        {
            // Delete previously seeded records from acl collection
            DB::collection('acl')->where('name', 'guest')->delete();
            DB::collection('acl')->where('name', 'standard')->delete();

            // Insert records into acl collection
            DB::collection('acl')->insert(
                [
                    [
                        'name' => 'standard',
                        'allows' => [
                            'GET' => [
                                "api/v1/{resource}",
                                "api/v1/{resource}/{id}",
                                "api/v1/configuration",
                                "api/v1/profiles",
                                "api/v1/profiles/{profiles}",
                                "api/v1/slack/users"
                            ],
                            'PUT' => [
                                'api/v1/profiles/changePassword',
                                'api/v1/profiles/{profiles}'
                            ],
                            'POST' => [
                                'api/v1/slack/message'
                            ],
                            'PATCH' => [
                                'api/v1/profiles/{profiles}'
                            ]
                        ]
                    ],
                    [
                        'name' => 'guest',
                        'allows' => [
                            'POST' => [
                                'api/v1/register',
                                'api/v1/login'
                            ],
                        ]
                    ]
                ]
            );
        }
    }
}
