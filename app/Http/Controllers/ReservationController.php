<?php

namespace App\Http\Controllers;

use App\GenericModel;
use Faker\Provider\DateTime;
use Illuminate\Http\Request;

use App\Http\Requests;


class ReservationController extends Controller
{
    /**
     * Reservation validator - checks if project ID exist, checks if project has already been
     * accepted or declined
     * @param null $project
     * @return bool|\Illuminate\Http\JsonResponse
     */
    private function validateReservation($project = null)
    {
        //check if project ID exist
        if (empty($project)) {
            return $this->jsonError('ID not found.', 404);
        }

        //check if project has been accepted already
        if (isset($project->acceptedBy)) {
            return $this->jsonError('Project already accepted.', 403);
        }

        //check if project has been declined already by current user
        if (isset($project->declinedBy)) {
            foreach ($project->declinedBy as $declined) {
                if ($declined['user_id'] == \Auth::user()->id) {
                    return $this->jsonError('Project already declined.', 403);
                }
            }
        }
        return false;
    }

    /**
     * Make reservation for selected project
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function make($id)
    {
        GenericModel::setCollection('projects');
        $project = GenericModel::find($id);

        if ($this->validateReservation($project) !== false) {
            return $this->validateReservation($project);
        }

        //check if project has been reserved within last 30 minutes
        $date = new \DateTime();
        $time = $date->getTimestamp();
        if (isset($project->reservationsBy)) {
            foreach ($project->reservationsBy as $reserved) {
                if ($time - $reserved['timestamp'] <= 1800) {
                    return $this->jsonError('Project already reserved.', 403);
                }
            }
        }
        
        //make project reservation
        $reservationsBy = $project->reservationsBy;
        $reservationsBy[] = ['user_id' => \Auth::user()->id, 'timestamp' => $time];
        $project->reservationsBy = $reservationsBy;
        $project->save();

        return $this->jsonSuccess($project);
    }

    /**
     * Accept reservation for selected project
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function accept($id)
    {
        GenericModel::setCollection('projects');
        $project = GenericModel::find($id);

        if ($this->validateReservation($project) !== false) {
            return $this->validateReservation($project);
        }

        //check if project has been reserved within last 30 minutes by current user
        $date = new \DateTime();
        $time = $date->getTimestamp();
        if (isset($project->reservationsBy)) {
            foreach ($project->reservationsBy as $reserved) {
                if (($time - $reserved['timestamp']) <= 1800 && !($reserved['user_id'] == \Auth::user()->id)) {
                    return $this->jsonError('Permission denied.', 403);
                }
            }
        }

        //accept reserved project
        $accepted = $project->acceptedBy;
        $accepted[] = \Auth::user()->id;
        $project->acceptedBy = $accepted;
        $project->save();

        return $this->jsonSuccess($project);
    }

    /**
     * Decline reservation for selected project
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function decline($id)
    {
        GenericModel::setCollection('projects');
        $project = GenericModel::find($id);

        if ($this->validateReservation($project) !== false) {
            return $this->validateReservation($project);
        }

        //check if project has been reserved within last 30 minutes by current user
        $date = new \DateTime();
        $time = $date->getTimestamp();
        if (isset($project->reservationsBy)) {
            foreach ($project->reservationsBy as $reserved) {
                if (($time - $reserved['timestamp']) <= 1800 && !($reserved['user_id'] == \Auth::user()->id)) {
                    return $this->jsonError('Permission denied.', 403);
                }
            }
        }

        //decline reserved project
        $declined = $project->declinedBy;
        $declined[] = ['user_id' => \Auth::user()->id, 'timestamp' => $time];
        $project->declinedBy = $declined;
        $project->save();

        return $this->jsonSuccess($project);
    }
}
