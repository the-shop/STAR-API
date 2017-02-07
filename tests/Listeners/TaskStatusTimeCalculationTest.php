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
     * Get assigned task
     * @return GenericModel
     */
    public function getAssignedTask()
    {
        //Assigned 5 minutes ago
        $minutesWorking = 5;
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
                        'workTrackTimestamp' => $assignedAgo
                    ]
                ]
            ]
        );

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

        $this->assertArrayHasKey($this->profile->_id, $task['work']);
    }

    /**
     * Test task status time for task reassigned
     */
    public function testTaskStatusTimeCalculationForTaskReassigned()
    {

        $task = $this->getAssignedTask();
        $newOwner = Profile::create();
        $task->owner = $newOwner->id;

        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);
        $task->save();

        $this->assertCount(2, $task['work']);
        $this->assertArrayHasKey($newOwner->id, $task['work']);
    }

    /**
     * Test task status time for paused
     */
    public function testTaskStatusTimeCalculationForTaskPaused()
    {
        $task = $this->getAssignedTask();
        $task->save();
        $workedTimeBeforeListener = $task->work[$this->profile->id]['worked'];
        $timeStampBeforeListener = $task->work[$this->profile->id]['workTrackTimestamp'];

        $task->paused = true;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);
        $task->save();

        $this->assertGreaterThan($workedTimeBeforeListener, $task->work[$this->profile->id]['worked']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$this->profile->id]['workTrackTimestamp']);
    }

    /**
     * Test task status time for resumed
     */
    public function testTaskStatusTimeCalculationForTaskResumed()
    {
        $task = $this->getAssignedTask();
        $task->paused = true;
        $task->save();
        $pausedTimeBeforeListener = $task->work[$this->profile->id]['paused'];
        $timeStampBeforeListener = $task->work[$this->profile->id]['workTrackTimestamp'];

        $task->paused = false;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);
        $task->save();

        $this->assertGreaterThan($pausedTimeBeforeListener, $task->work[$this->profile->id]['paused']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$this->profile->id]['workTrackTimestamp']);
    }

    /**
     * Test task status time for blocked
     */
    public function testTaskStatusTimeCalculationForBlocked()
    {
        $task = $this->getAssignedTask();
        $task->save();
        $workedTimeBeforeListener = $task->work[$this->profile->id]['worked'];
        $timeStampBeforeListener = $task->work[$this->profile->id]['workTrackTimestamp'];

        $task->blocked = true;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);
        $task->save();

        $this->assertGreaterThan($workedTimeBeforeListener, $task->work[$this->profile->id]['worked']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$this->profile->id]['workTrackTimestamp']);
    }

    /**
     * Test task status time for unblocked
     */
    public function testTaskStatusTimeCalculationForUnBlocked()
    {
        $task = $this->getAssignedTask();
        $task->blocked = true;
        $task->save();
        $blockedTimeBeforeListener = $task->work[$this->profile->id]['blocked'];
        $timeStampBeforeListener = $task->work[$this->profile->id]['workTrackTimestamp'];

        $task->blocked = false;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);
        $task->save();

        $this->assertGreaterThan($blockedTimeBeforeListener, $task->work[$this->profile->id]['blocked']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$this->profile->id]['workTrackTimestamp']);
    }

    /**
     * Test task status time for submitted for QA
     */
    public function testTaskStatusTimeCalculationForSubmittedForQa()
    {
        $task = $this->getAssignedTask();
        $task->save();
        $workedTimeBeforeListener = $task->work[$this->profile->id]['worked'];
        $timeStampBeforeListener = $task->work[$this->profile->id]['workTrackTimestamp'];

        $task->submitted_for_qa = true;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);
        $task->save();

        $this->assertGreaterThan($workedTimeBeforeListener, $task->work[$this->profile->id]['worked']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$this->profile->id]['workTrackTimestamp']);
    }

    /**
     * Test task status time for failed QA
     */
    public function testTaskStatusTimeCalculationForFailedQa()
    {
        $task = $this->getAssignedTask();
        $task->submitted_for_qa = true;
        $task->save();
        $qaTimeBeforeListener = $task->work[$this->profile->id]['qa'];
        $timeStampBeforeListener = $task->work[$this->profile->id]['workTrackTimestamp'];

        $task->submitted_for_qa = false;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);
        $task->save();

        $this->assertGreaterThan($qaTimeBeforeListener, $task->work[$this->profile->id]['qa']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$this->profile->id]['workTrackTimestamp']);
    }

    /**
     * Test task status time for passed QA
     */
    public function testTaskStatusTimeCalculationForPassedQa()
    {
        $task = $this->getAssignedTask();
        $task->submitted_for_qa = true;
        $task->save();
        $qaTimeBeforeListener = $task->work[$this->profile->id]['qa'];
        $timeStampBeforeListener = $task->work[$this->profile->id]['workTrackTimestamp'];

        $task->passed_qa = true;
        $event = new TaskStatusTimeCalculation($task);
        $listener = new \App\Listeners\TaskStatusTimeCalculation();
        $listener->handle($event);
        $task->save();

        $this->assertGreaterThan($qaTimeBeforeListener, $task->work[$this->profile->id]['qa']);
        $this->assertGreaterThan($timeStampBeforeListener, $task->work[$this->profile->id]['workTrackTimestamp']);
    }
}
