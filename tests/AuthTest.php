<?php

namespace

{
    class AuthTest extends TestCase
    {
        /**
         * Test invalid login attempt
         */
        public function testEmptyRequestOnLogin()
        {
            $this->json('POST', '/api/v1/app/starapi-testing/login', [])
                ->seeJsonEquals([
                    'error' => true,
                    'errors' => ['Invalid credentials.']
                ]);
        }

        /**
         * Test invalid login attempt
         */
        public function testInvalidLogin()
        {
            $this->json('POST', '/api/v1/app/starapi-testing/login', ['name' => 'Sally'])
                ->seeJsonEquals([
                    'error' => true,
                    'errors' => ['Invalid credentials.']
                ]);
        }

        /**
         * Test invalid login attempt
         */
        public function testEmptyRequestOnRegistration()
        {
            $this->markTestIncomplete('Registration test not implemented yet.');

            $this->json('POST', '/api/v1/app/starapi-testing/register', [])
                ->see('oblah');
        }
    }
}
