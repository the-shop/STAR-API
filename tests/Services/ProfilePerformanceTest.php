<?php

namespace Tests\Services;

use App\Profile;
use App\Services\ProfilePerformance;
use Tests\Collections\ProjectRelated;
use Tests\TestCase;

class ProfilePerformanceTest extends TestCase
{
    use ProjectRelated;

    private $projectOwner = null;

    public function setUp()
    {
        parent::setUp();

        $this->setTaskOwner(new Profile());

        $this->projectOwner = new Profile();

        $this->projectOwner->save();
        $this->profile->save();
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->profile->delete();
        $this->projectOwner->delete();
    }

    /**
     * Test invalid login attempt
     */
    public function testCheckPerformanceForEmptyHistory()
    {
        $task = $this->getTaskWithEmptyHistory();

        $pp = new ProfilePerformance();

        $out = $pp->perTask($task);

        $this->assertEquals([], $out);
    }

    /**
     * Test invalid login attempt
     */
    public function testCheckPerformanceForTaskAssigned()
    {
        // Assigned 5 minutes ago
        $minutesWorking = 5;
        $assignedAgo = (int) (new \DateTime)->add(new \DateInterval('PT' . $minutesWorking . 'M'))->format('U');
        $task = $this->getTaskWithJustAssignedHistory($assignedAgo);

        $pp = new ProfilePerformance();

        $out = $pp->perTask($task);

        $this->assertCount(1, $out);

        $this->assertArrayHasKey($this->profile->id, $out);

        $profilePerformanceArray = $out[$this->profile->id];
        $this->assertArrayHasKey('taskCompleted', $profilePerformanceArray);
        $this->assertArrayHasKey('workSeconds', $profilePerformanceArray);
        $this->assertArrayHasKey('qaSeconds', $profilePerformanceArray);
        $this->assertArrayHasKey('pauseSeconds', $profilePerformanceArray);

        $this->assertEquals(false, $profilePerformanceArray['taskCompleted']);
        $this->assertEquals($minutesWorking * 60, $profilePerformanceArray['workSeconds']);
        $this->assertEquals(0, $profilePerformanceArray['qaSeconds']);
        $this->assertEquals(0, $profilePerformanceArray['pauseSeconds']);
    }

    /**
     * Test invalid login attempt
     */
    public function testCheckPerformanceForTaskDone()
    {
        // Assigned 5 minutes ago
        $minutesWorking = 5;
        $assignedAgo = (int) (new \DateTime)->add(new \DateInterval('PT' . $minutesWorking . 'M'))->format('U');
        $task = $this->getQaPassedTask($assignedAgo);

        $pp = new ProfilePerformance();

        $out = $pp->perTask($task);

        $this->assertCount(1, $out);

        $this->assertArrayHasKey($this->profile->id, $out);

        $profilePerformanceArray = $out[$this->profile->id];

        var_dump($profilePerformanceArray);

        $this->assertArrayHasKey('taskCompleted', $profilePerformanceArray);
        $this->assertArrayHasKey('workSeconds', $profilePerformanceArray);
        $this->assertArrayHasKey('qaSeconds', $profilePerformanceArray);
        $this->assertArrayHasKey('pauseSeconds', $profilePerformanceArray);

        $this->assertEquals(false, $profilePerformanceArray['taskCompleted']);
        $this->assertEquals($minutesWorking * 60, $profilePerformanceArray['workSeconds']);
        $this->assertEquals(0, $profilePerformanceArray['qaSeconds']);
        $this->assertEquals(0, $profilePerformanceArray['pauseSeconds']);
    }
}
