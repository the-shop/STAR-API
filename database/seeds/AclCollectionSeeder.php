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
                                "api/v1/app/{appName}/{resource}",
                                "api/v1/app/{appName}/{resource}/{id}",
                                "api/v1/app/{appName}/configuration",
                                "api/v1/app/{appName}/profiles",
                                "api/v1/app/{appName}/profiles/{profiles}",
                                "api/v1/app/{appName}/slack/users",
                                "api/v1/app/{appName}/projects/{id}/uploads"
                            ],
                            'PUT' => [
                                'api/v1/app/{appName}/profiles/changePassword',
                                'api/v1/app/{appName}/profiles/{profiles}',
                                "api/v1/app/{appName}/{resource}/{id}",
                            ],
                            'POST' => [
                                "api/v1/app/{appName}/{resource}",
                                'api/v1/app/{appName}/slack/message'
                            ],
                            'PATCH' => [
                                'api/v1/app/{appName}/profiles/{profiles}'
                            ]
                        ]
                    ],
                    [
                        'name' => 'guest',
                        'allows' => [
                            'POST' => [
                                'api/v1/app/{appName}/register',
                                'api/v1/app/{appName}/login'
                            ],
                        ]
                    ]
                ]
            );
        }
    }
}
