<?php
namespace {

    use Illuminate\Database\Seeder;

    class UserRolesSeeder extends Seeder
    {
        /**
         * Run the database seeds.
         *
         * @return void
         */
        public function run()
        {
            DB::collection('user-roles')->delete();
            DB::collection('user-roles')->insert(
                [
                    [
                        'userRoles' => [
                            'Standard',
                            'Client',
                            'Employee',
                            'Admin',
                            'Accountant'
                        ]
                    ]
                ]
            );
        }
    }
}
