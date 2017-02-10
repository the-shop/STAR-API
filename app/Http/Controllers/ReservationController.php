<?php

namespace App\Http\Controllers;

use App\GenericModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

/**
 * Handles project reservation logic
 *
 * Class ReservationController
 * @package App\Http\Controllers
 */
class ReservationController extends Controller
{
    CONST MAKE_RESERVATION = true;

    /**
     * Make reservation for selected project
     *
     * @param Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     */
    public function makeProjectReservation(Request $request)
    {
        GenericModel::setCollection('projects');
        $project = GenericModel::find($request->route('id'));

        $errors = [];
        $time = (new \DateTime())->getTimestamp();

        if ($this->validateReservation($project, $errors, $time, self::MAKE_RESERVATION) === false) {
            return $this->jsonError($errors, 400);
        }


        $reservationsBy = $project->reservationsBy;
        $reservationsBy[] = ['user_id' => Auth::user()->id, 'timestamp' => $time];
        $project->reservationsBy = $reservationsBy;
        $project->save();

        return $this->jsonSuccess($project);
    }

    /**
     * Accept reserved project
     *
     * @param Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     */
    public function acceptProject(Request $request)
    {
        GenericModel::setCollection('projects');
        $project = GenericModel::find($request->route('id'));

        $errors = [];
        $time = (new \DateTime())->getTimestamp();

        if ($this->validateReservation($project, $errors, $time) === false) {
            return $this->jsonError($errors, 400);
        }

        $project->acceptedBy = Auth::user()->id;
        $project->save();

        return $this->jsonSuccess($project);
    }

    /**
     * Decline reserved project
     *
     * @param Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     */
    public function declineProject(Request $request)
    {
        GenericModel::setCollection('projects');
        $project = GenericModel::find($request->route('id'));

        $errors = [];
        $time = (new \DateTime())->getTimestamp();

        if ($this->validateReservation($project, $errors, $time) === false) {
            return $this->jsonError($errors, 400);
        }

        $declined = $project->declinedBy;
        $declined[] = ['user_id' => \Auth::user()->id, 'timestamp' => $time];
        $project->declinedBy = $declined;
        $project->save();

        return $this->jsonSuccess($project);
    }

    /**
     * Make reservation for selected task
     * @param Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     */
    public function makeTaskReservation(Request $request)
    {
        GenericModel::setCollection('tasks');
        $task = GenericModel::find($request->route('id'));

        $errors = [];
        $time = (new \DateTime())->getTimestamp();

        if ($this->validateReservation($task, $errors, $time, self::MAKE_RESERVATION) === false) {
            return $this->jsonError($errors, 400);
        }

        $reservationsBy = $task->reservationsBy;
        $reservationsBy[] = ['user_id' => Auth::user()->id, 'timestamp' => $time];
        $task->reservationsBy = $reservationsBy;
        $task->save();

        return $task;
    }

    /**
     * Accept reserved task
     * @param Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     */
    public function acceptTask(Request $request)
    {
        GenericModel::setCollection('tasks');
        $task = GenericModel::find($request->route('id'));

        $errors = [];
        $time = (new \DateTime())->getTimestamp();

        if ($this->validateReservation($task, $errors, $time) === false) {
            return $this->jsonError($errors, 400);
        }

        $task->owner = Auth::user()->id;
        $task->save();

        return $task;
    }

    /**
     * Decline reserved task
     * @param Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     */
    public function declineTask(Request $request)
    {
        GenericModel::setCollection('tasks');
        $task = GenericModel::find($request->route('id'));

        $errors = [];
        $time = (new \DateTime())->getTimestamp();

        if ($this->validateReservation($task, $errors, $time) === false) {
            return $this->jsonError($errors, 400);
        }

        $declined = $task->declinedBy;
        $declined[] = ['user_id' => \Auth::user()->id, 'timestamp' => $time];
        $task->declinedBy = $declined;
        $task->save();

        return $task;
    }


    /**
     * Reservation validator - checks if project/task ID exist, checks if project/task has already been
     * accepted or declined
     * @param GenericModel|null $model
     * @param $errors
     * @return bool
     */
    private function validateReservation(GenericModel $model = null, &$errors, $time, $makeReservation = false)
    {
        //error messages
        $errorMessages = [
            'empty' => 'ID not found.',
            'accepted' => '%m already accepted.',
            'declined' => '%m already declined.',
            'reserved' => '%m already reserved',
            'denied' => 'Permission denied.'
        ];

        //check if passed model exists in database
        if (empty($model)) {
            $errors[] = $errorMessages['empty'];
            return false;
        }

        //check if model is already accepted
        if (!empty($model['collection'] === 'projects' ? $model->acceptedBy : $model->owner)) {
            $errors[] = $model['collection'] === 'projects' ?
                str_replace('%m', 'Project', $errorMessages['accepted'])
                : str_replace('%m', 'Task', $errorMessages['accepted']);
        }

        //check if model is already declined by current user
        if (isset($model->declinedBy)) {
            foreach ($model->declinedBy as $declined) {
                if ($declined['user_id'] === Auth::user()->id) {
                    $errors[] = $model['collection'] === 'projects' ?
                        str_replace('%m', 'Project', $errorMessages['declined'])
                        : str_replace('%m', 'Task', $errorMessages['declined']);
                }
            }
        }

        //read reservation time from shared settings
        if ($model['collection'] === 'projects') {
            $reservationTime = Config::get('sharedSettings.internalConfiguration.projects.reservation.maxReservationTime');
        } else {
            $reservationTime = Config::get('sharedSettings.internalConfiguration.tasks.reservation.maxReservationTime');
        }

        if (isset($model->reservationsBy)) {
            foreach ($model->reservationsBy as $reserved) {
                //for makeReservation check time if model is already reserved
                if ($makeReservation === self::MAKE_RESERVATION
                    && ($time - $reserved['timestamp'] <= ($reservationTime * 60))
                    && empty($model['collection'] === 'projects' ? $model->acceptedBy : $model->owner)
                ) {
                    $errors[] = $model['collection'] === 'projects' ?
                        str_replace('%m', 'Project', $errorMessages['reserved'])
                        : str_replace('%m', 'Task', $errorMessages['reserved']);
                }
                //for accept or decline reserved model check time and user ID for permission
                if ($makeReservation !== self::MAKE_RESERVATION
                    && ($time - $reserved['timestamp'] <= ($reservationTime * 60))
                    && ($reserved['user_id'] !== Auth::user()->id)
                ) {
                    $errors[] = $errorMessages['denied'];
                }
            }
        }

        if (count($errors) > 0) {
            return false;
        }

        return true;
    }
}
