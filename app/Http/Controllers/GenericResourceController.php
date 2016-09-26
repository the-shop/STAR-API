<?php

namespace App\Http\Controllers;

use App\GenericModel;
use Illuminate\Http\Request;
use App\Http\Requests;

/**
 * Class GenericResourceController
 * @package App\Http\Controllers
 */
class GenericResourceController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $models = GenericModel::all();
        return $this->jsonSuccess($models);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $model = $this->loadModel($request->route('id'));
        return $this->jsonSuccess($model);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $model = GenericModel::create($request->all());
        if ($model->save()) {
            return $this->jsonSuccess($model);
        }
        return $this->jsonError('Issue with saving resource.');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $model = $this->loadModel($request->route('id'));

        if (!$model instanceof GenericModel) {
            return $this->jsonError(['Resource not found.'], 404);
        }

        $model->fill($request->all());
        if ($model->save()) {
            return $this->jsonSuccess($model);
        }

        return $this->jsonError('Issue with updating resource.');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        $model = $this->loadModel($request->route('id'));

        if ($model->delete()) {
            return $this->jsonSuccess(['id' => $model->id]);
        }
        return $this->jsonError('Issue with deleting resource.');
    }

    /**
     * Helper method to validate model is loaded
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    protected function loadModel($id)
    {
        $model = GenericModel::find($id);

        if (!$model instanceof GenericModel) {
            return $this->jsonError(['Resource not found.'], 404);
        }

        return $model;
    }
}
