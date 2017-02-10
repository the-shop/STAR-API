<?php

namespace Tests\Listeners;

use App\Events\TaskStatusTimeCalculation;
use App\GenericModel;
use Tests\Collections\ProjectRelated;
use Tests\TestCase;
use App\Profile;

class TaskStatusTimeCalculationTest extends TestCase
{
    use ProjectRelated;

    public function setUp()
    {
        parent::setUp();

        $this->profile = Profile::create();

        $this->profile->save();
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->profile->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Get methods
    |--------------------------------------------------------------------------
    |
    | Here are methods to get new task, new project and new task that is assigned
    */

    /**
     * Get new task without owner
     * @return GenericModel
     */
    public function getNewTask()
    {
        GenericModel::setCollection('tasks');
        return new GenericModel(
            [
                'owner' => '',
                'paused' => false,
                'submitted_for_qa' => false,
                'blocked' => false,
                'passed_qa' => false
            ]
        );
    }

    /**
     * Get new project
     * @return GenericModel
     */
    public function getNewProject()
    {
        GenericModel::setCollection('projects');
        return new GenericModel(
            [
                'owner' => '',
                'paused' => false,
                'submitted_for_qa' => false,
                'blocked' => false,
                'passed_qa' => false
            ]
        );
    }

    /**
     * Get assigned task
     * @return GenericModel
     */
    public function getAssignedTask()
    {
        //Assigned 5 minutes ago
        $minutesWorking = 30;
        $assignedAgo = (int)(new \DateTime())->sub(new \DateInterval('PT' . $minutesWorking . 'M'))->format('U');
        GenericModel::setCollection('tasks');
        return new GenericModel(
            [
                'owner' => $this->profile->id,
                'paused' => false,
                'submitted_for_qa' => false,
                'blocked' => false,
                'passed_qa' => false,
                'work' => [
                    $this->profile->id => [
                        'worked' => 0,
                        'paused' => 0,
                        'qa' => 0,
                        'blocked' => 0,
                        'workTrackTimestamp' => $assignedAgo,
                        'timeAssigned' => $assignedAgo
                    ]
                ]
            ]
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Test methods
    |--------------------------------------------------------------------------
    |
    | Here are methods to test TaskStatusTimeCalculation listener in different cases
    */

    /**
     * Test task status time for model with wrong collection
     */
    public function testTaskStatusTimeWrongCollection()
    {
        $project = $this->getNewProject();
        $event = new TaskStatusTimeCalculation($project);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $out = $listener->handle($event);
        $this->assertEquals(false, $out);
    }

    /**
     * Test task status time for new task without owner
     */
    public function testTaskStatusTimeNewTaskWithoutOwner()
    {
        $task = $this->getNewTask();
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $out = $listener->handle($event);
        $this->assertEquals(false, $out);
    }

    /**
     * Test task status time with new task that has owner
     */
    public function teskTastStatusTimeNewTaskWithOwner()
    {
        $task = $this->getNewTask();
        $task->owner = $this->profile;
        $task->save();
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);

        $this->assertArrayHasKey('worked', $task->work[$task->owner]);
        $this->assertArrayHasKey('paused', $task->work[$task->owner]);
        $this->assertArrayHasKey('qa', $task->work[$task->owner]);
        $this->assertArrayHasKey('workTrackTimestamp', $task->work[$task->owner]);
        $this->assertArrayHasKey('timeAssigned', $task->work[$task->owner]);
        $this->assertEquals(0, $task->work[$task->owner]['worked']);
        $this->assertEquals(0, $task->work[$task->owner]['paused']);
        $this->assertEquals(0, $task->work[$task->owner]['qa']);
        $this->assertEquals(0, $task->work[$task->owner]['blocked']);
        $this->assertEquals($task->work[$task->owner]['workTrackTimestamp'],
            $task->work[$task->owner]['timeAssigned']);
    }

    /**
     * Test task status time for task assigned
     */
    public function testTaskStatusTimeCalculationForTaskAssigned()
    {
        $task = $this->getNewTask();
        $task->owner = $this->profile->id;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);
        $task->save();

        $this->assertArrayHasKey('worked', $task->work[$task->owner]);
        $this->assertArrayHasKey('paused', $task->work[$task->owner]);
        $this->assertArrayHasKey('qa', $task->work[$task->owner]);
        $this->assertArrayHasKey('workTrackTimestamp', $task->work[$task->owner]);
        $this->assertArrayHasKey('timeAssigned', $task->work[$task->owner]);
        $this->assertEquals(0, $task->work[$task->owner]['worked']);
        $this->assertEquals(0, $task->work[$task->owner]['paused']);
        $this->assertEquals(0, $task->work[$task->owner]['qa']);
        $this->assertEquals(0, $task->work[$task->owner]['blocked']);
        $this->assertEquals($task->work[$task->owner]['workTrackTimestamp'],
            $task->work[$task->owner]['timeAssigned']);
    }

    /**
     * Test task status time for task reassigned
     */
    public function testTaskStatusTimeCalculationForTaskReassigned()
    {

        $task = $this->getAssignedTask();
        $oldOwner = $task->owner;
        $oldWorkTrackTimestamp = $task->work[$oldOwner]['workTrackTimestamp'];
        $newOwner = Profile::create();
        $task->owner = $newOwner->id;

        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);
        $task->save();

        $this->assertCount(2, $task->work);
        $this->assertArrayHasKey($newOwner->id, $task->work);

        $this->assertArrayHasKey('worked', $task->work[$oldOwner]);
        $this->assertArrayHasKey('paused', $task->work[$oldOwner]);
        $this->assertArrayHasKey('qa', $task->work[$oldOwner]);
        $this->assertArrayHasKey('workTrackTimestamp', $task->work[$oldOwner]);
        $this->assertArrayHasKey('timeAssigned', $task->work[$oldOwner]);
        $this->assertArrayHasKey('timeRemoved', $task->work[$oldOwner]);
        $this->assertEquals($task->work[$oldOwner]['workTrackTimestamp'] - $oldWorkTrackTimestamp,
            $task->work[$oldOwner]['worked']);
        $this->assertEquals(0, $task->work[$oldOwner]['paused']);
        $this->assertEquals(0, $task->work[$oldOwner]['qa']);
        $this->assertEquals(0, $task->work[$oldOwner]['blocked']);
        $this->assertEquals($task->work[$oldOwner]['workTrackTimestamp'],
            $task->work[$oldOwner]['timeRemoved']);

        $this->assertArrayHasKey('worked', $task->work[$task->owner]);
        $this->assertArrayHasKey('paused', $task->work[$task->owner]);
        $this->assertArrayHasKey('qa', $task->work[$task->owner]);
        $this->assertArrayHasKey('workTrackTimestamp', $task->work[$task->owner]);
        $this->assertArrayHasKey('timeAssigned', $task->work[$task->owner]);
        $this->assertArrayNotHasKey('timeRemoved', $task->work[$task->owner]);
        $this->assertEquals(0, $task->work[$task->owner]['worked']);
        $this->assertEquals(0, $task->work[$task->owner]['paused']);
        $this->assertEquals(0, $task->work[$task->owner]['qa']);
        $this->assertEquals(0, $task->work[$task->owner]['blocked']);
        $this->assertEquals($task->work[$task->owner]['workTrackTimestamp'],
            $task->work[$task->owner]['timeAssigned']);

    }

    /**
     * Test task status time for paused
     */
    public function testTaskStatusTimeCalculationForTaskPaused()
    {
        $task = $this->getAssignedTask();
        $task->save();
        $workedTimeBeforeListener = $task->work[$task->owner]['worked'];
        $timeStampBeforeListener = $task->work[$task->owner]['workTrackTimestamp'];

        $task->paused = true;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);
        $task->save();

        $this->assertGreaterThan($workedTimeBeforeListener, $task->work[$task->owner]['worked']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$task->owner]['workTrackTimestamp']);
        $this->assertEquals($task->work[$task->owner]['workTrackTimestamp'] - $timeStampBeforeListener,
            $task->work[$task->owner]['worked']);
    }

    /**
     * Test task status time for resumed
     */
    public function testTaskStatusTimeCalculationForTaskResumed()
    {
        $task = $this->getAssignedTask();
        $task->paused = true;
        $task->save();
        $pausedTimeBeforeListener = $task->work[$task->owner]['paused'];
        $timeStampBeforeListener = $task->work[$task->owner]['workTrackTimestamp'];

        $task->paused = false;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);
        $task->save();

        $this->assertGreaterThan($pausedTimeBeforeListener, $task->work[$task->owner]['paused']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$task->owner]['workTrackTimestamp']);
        $this->assertEquals($task->work[$task->owner]['workTrackTimestamp'] - $timeStampBeforeListener,
            $task->work[$task->owner]['paused']);
    }

    /**
     * Test task status time for blocked
     */
    public function testTaskStatusTimeCalculationForBlocked()
    {
        $task = $this->getAssignedTask();
        $task->save();
        $workedTimeBeforeListener = $task->work[$task->owner]['worked'];
        $timeStampBeforeListener = $task->work[$task->owner]['workTrackTimestamp'];

        $task->blocked = true;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);
        $task->save();

        $this->assertGreaterThan($workedTimeBeforeListener, $task->work[$task->owner]['worked']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$task->owner]['workTrackTimestamp']);
        $this->assertEquals($task->work[$task->owner]['workTrackTimestamp'] - $timeStampBeforeListener,
            $task->work[$task->owner]['worked']);
    }

    /**
     * Test task status time for unblocked
     */
    public function testTaskStatusTimeCalculationForUnBlocked()
    {
        $task = $this->getAssignedTask();
        $task->blocked = true;
        $task->save();
        $blockedTimeBeforeListener = $task->work[$task->owner]['blocked'];
        $timeStampBeforeListener = $task->work[$task->owner]['workTrackTimestamp'];

        $task->blocked = false;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);
        $task->save();

        $this->assertGreaterThan($blockedTimeBeforeListener, $task->work[$task->owner]['blocked']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$task->owner]['workTrackTimestamp']);
        $this->assertEquals($task->work[$task->owner]['workTrackTimestamp'] - $timeStampBeforeListener,
            $task->work[$task->owner]['blocked']);
    }

    /**
     * Test task status time for submitted for QA
     */
    public function testTaskStatusTimeCalculationForSubmittedForQa()
    {
        $task = $this->getAssignedTask();
        $task->save();
        $workedTimeBeforeListener = $task->work[$task->owner]['worked'];
        $timeStampBeforeListener = $task->work[$task->owner]['workTrackTimestamp'];

        $task->submitted_for_qa = true;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);
        $task->save();

        $this->assertGreaterThan($workedTimeBeforeListener, $task->work[$task->owner]['worked']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$task->owner]['workTrackTimestamp']);
        $this->assertEquals($task->work[$task->owner]['workTrackTimestamp'] - $timeStampBeforeListener,
            $task->work[$task->owner]['worked']);
    }

    /**
     * Test task status time for failed QA
     */
    public function testTaskStatusTimeCalculationForFailedQa()
    {
        $task = $this->getAssignedTask();
        $task->submitted_for_qa = true;
        $task->save();
        $qaTimeBeforeListener = $task->work[$task->owner]['qa'];
        $timeStampBeforeListener = $task->work[$task->owner]['workTrackTimestamp'];

        $task->submitted_for_qa = false;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);
        $task->save();

        $this->assertGreaterThan($qaTimeBeforeListener, $task->work[$task->owner]['qa']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$task->owner]['workTrackTimestamp']);
        $this->assertEquals($task->work[$task->owner]['workTrackTimestamp'] - $timeStampBeforeListener,
            $task->work[$task->owner]['qa']);
    }

    /**
     * Test task status time for passed QA
     */
    public function testTaskStatusTimeCalculationForPassedQa()
    {
        $task = $this->getAssignedTask();
        $task->submitted_for_qa = true;
        $task->save();
        $qaTimeBeforeListener = $task->work[$task->owner]['qa'];
        $timeStampBeforeListener = $task->work[$task->owner]['workTrackTimestamp'];

        $task->passed_qa = true;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);
        $task->save();

        $this->assertGreaterThan($qaTimeBeforeListener, $task->work[$task->owner]['qa']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$task->owner]['workTrackTimestamp']);
        $this->assertEquals($task->work[$task->owner]['workTrackTimestamp'] - $timeStampBeforeListener,
            $task->work[$task->owner]['qa']);

    }

    /**
     * Test task status time calculation complex flow (Reassigned, paused, blocked, qa, fail qa and finally done)
     */
    public function testTaskStatusTimeComplexFlowTaskDone()
    {
        //user that assigned task after 5 mins pause task
        $task = $this->getAssignedTask();
        $task->save();
        $workedTimeBeforeListener = $task->work[$task->owner]['worked'];
        $timeStampBeforeListener = $task->work[$task->owner]['workTrackTimestamp'];

        $task->paused = true;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);


        $this->assertGreaterThan($workedTimeBeforeListener, $task->work[$task->owner]['worked']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$task->owner]['workTrackTimestamp']);
        $this->assertEquals($task->work[$task->owner]['workTrackTimestamp'] - $timeStampBeforeListener,
            $task->work[$task->owner]['worked']);

        $modifyTimeStamp = $task->work;
        $modifyTimeStamp[$task->owner]['workTrackTimestamp'] = (new \DateTime())->format('U') - 20 * 60;
        $task->work = $modifyTimeStamp;
        $task->save();
        //task resumed
        $pausedTimeBeforeListener = $task->work[$task->owner]['paused'];
        $timeStampBeforeResumed = $task->work[$task->owner]['workTrackTimestamp'];

        $task->paused = false;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);


        $this->assertGreaterThan($pausedTimeBeforeListener, $task->work[$task->owner]['paused']);
        $this->assertGreaterThan($timeStampBeforeResumed, $task->work[$task->owner]['workTrackTimestamp']);
        $this->assertEquals(($task->work[$task->owner]['workTrackTimestamp'] - $timeStampBeforeResumed),
            $task->work[$task->owner]['paused']);

        $modifyTimeStamp = $task->work;
        $modifyTimeStamp[$task->owner]['workTrackTimestamp'] = (new \DateTime())->format('U') - 15 * 60;
        $task->work = $modifyTimeStamp;
        $task->save();

        //task reassigned
        $oldOwner = $task->owner;
        $oldWorkTrackTimestamp = $task->work[$oldOwner]['workTrackTimestamp'];
        $oldWorked = $task->work[$oldOwner]['worked'];
        $oldWorkPaused = $task->work[$oldOwner]['paused'];
        $oldWorkQa = $task->work[$oldOwner]['qa'];
        $oldWorkBlocked = $task->work[$oldOwner]['blocked'];
        $newOwner = Profile::create();
        $task->owner = $newOwner->id;

        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);

        $this->assertCount(2, $task->work);
        $this->assertArrayHasKey($newOwner->id, $task->work);

        $this->assertArrayHasKey('worked', $task->work[$oldOwner]);
        $this->assertArrayHasKey('paused', $task->work[$oldOwner]);
        $this->assertArrayHasKey('qa', $task->work[$oldOwner]);
        $this->assertArrayHasKey('workTrackTimestamp', $task->work[$oldOwner]);
        $this->assertArrayHasKey('timeAssigned', $task->work[$oldOwner]);
        $this->assertArrayHasKey('timeRemoved', $task->work[$oldOwner]);
        $this->assertEquals($task->work[$oldOwner]['workTrackTimestamp'] - $oldWorkTrackTimestamp + $oldWorked,
            $task->work[$oldOwner]['worked']);
        $this->assertEquals($oldWorkPaused, $task->work[$oldOwner]['paused']);
        $this->assertEquals($oldWorkQa, $task->work[$oldOwner]['qa']);
        $this->assertEquals($oldWorkBlocked, $task->work[$oldOwner]['blocked']);
        $this->assertEquals($task->work[$oldOwner]['workTrackTimestamp'],
            $task->work[$oldOwner]['timeRemoved']);

        $this->assertArrayHasKey('worked', $task->work[$task->owner]);
        $this->assertArrayHasKey('paused', $task->work[$task->owner]);
        $this->assertArrayHasKey('qa', $task->work[$task->owner]);
        $this->assertArrayHasKey('workTrackTimestamp', $task->work[$task->owner]);
        $this->assertArrayHasKey('timeAssigned', $task->work[$task->owner]);
        $this->assertArrayNotHasKey('timeRemoved', $task->work[$task->owner]);
        $this->assertEquals(0, $task->work[$task->owner]['worked']);
        $this->assertEquals(0, $task->work[$task->owner]['paused']);
        $this->assertEquals(0, $task->work[$task->owner]['qa']);
        $this->assertEquals(0, $task->work[$task->owner]['blocked']);
        $this->assertEquals($task->work[$task->owner]['workTrackTimestamp'],
            $task->work[$task->owner]['timeAssigned']);

        $modifyTimeStamp = $task->work;
        $modifyTimeStamp[$task->owner]['workTrackTimestamp'] = (new \DateTime())->format('U') - 13 * 60;
        $modifyTimeStamp[$task->owner]['assignedTime'] = (new \DateTime())->format('U') - 13 * 60;
        $task->work = $modifyTimeStamp;
        $task->save();

        //qa ready
        $workedTimeBeforeListener = $task->work[$task->owner]['worked'];
        $timeStampBeforeListener = $task->work[$task->owner]['workTrackTimestamp'];

        $task->submitted_for_qa = true;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);

        $this->assertGreaterThan($workedTimeBeforeListener, $task->work[$task->owner]['worked']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$task->owner]['workTrackTimestamp']);
        $this->assertEquals($task->work[$task->owner]['workTrackTimestamp'] - $timeStampBeforeListener,
            $task->work[$task->owner]['worked']);

        $modifyTimeStamp = $task->work;
        $modifyTimeStamp[$task->owner]['workTrackTimestamp'] = (new \DateTime())->format('U') - 11 * 60;
        $task->work = $modifyTimeStamp;
        $task->save();

        //qa failed
        $qaTimeBeforeListener = $task->work[$task->owner]['qa'];
        $timeStampBeforeListener = $task->work[$task->owner]['workTrackTimestamp'];

        $task->submitted_for_qa = false;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);

        $this->assertGreaterThan($qaTimeBeforeListener, $task->work[$task->owner]['qa']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$task->owner]['workTrackTimestamp']);
        $this->assertEquals($task->work[$task->owner]['workTrackTimestamp'] - $timeStampBeforeListener,
            $task->work[$task->owner]['qa']);

        $modifyTimeStamp = $task->work;
        $modifyTimeStamp[$task->owner]['workTrackTimestamp'] = (new \DateTime())->format('U') - 9 * 60;
        $task->work = $modifyTimeStamp;
        $task->paused = true;
        $task->save();

        //task resumed

        $pausedTimeBeforeListener = $task->work[$task->owner]['paused'];
        $timeStampBeforeResumed = $task->work[$task->owner]['workTrackTimestamp'];

        $task->paused = false;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);


        $this->assertGreaterThan($pausedTimeBeforeListener, $task->work[$task->owner]['paused']);
        $this->assertGreaterThan($timeStampBeforeResumed, $task->work[$task->owner]['workTrackTimestamp']);
        $this->assertEquals(($task->work[$task->owner]['workTrackTimestamp'] - $timeStampBeforeResumed),
            $task->work[$task->owner]['paused']);

        $modifyTimeStamp = $task->work;
        $modifyTimeStamp[$task->owner]['workTrackTimestamp'] = (new \DateTime())->format('U') - 7 * 60;
        $task->work = $modifyTimeStamp;
        $task->save();

        //task submitted for qa again
        $workedTimeBeforeListener = $task->work[$task->owner]['worked'];
        $timeStampBeforeListener = $task->work[$task->owner]['workTrackTimestamp'];

        $task->submitted_for_qa = true;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);

        $this->assertGreaterThan($workedTimeBeforeListener, $task->work[$task->owner]['worked']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$task->owner]['workTrackTimestamp']);
        $this->assertEquals($task->work[$task->owner]['workTrackTimestamp'] - $timeStampBeforeListener
            + $workedTimeBeforeListener, $task->work[$task->owner]['worked']);

        $modifyTimeStamp = $task->work;
        $modifyTimeStamp[$task->owner]['workTrackTimestamp'] = (new \DateTime())->format('U') - 5 * 60;
        $task->work = $modifyTimeStamp;
        $task->save();

        //finally QA passed
        $qaTimeBeforeListener = $task->work[$task->owner]['qa'];
        $timeStampBeforeListener = $task->work[$task->owner]['workTrackTimestamp'];

        $task->passed_qa = true;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);

        $this->assertGreaterThan($qaTimeBeforeListener, $task->work[$task->owner]['qa']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$task->owner]['workTrackTimestamp']);
        $this->assertEquals($task->work[$task->owner]['workTrackTimestamp'] - $timeStampBeforeListener
            + $qaTimeBeforeListener, $task->work[$task->owner]['qa']);
    }
}
