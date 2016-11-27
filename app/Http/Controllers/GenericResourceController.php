<?php

namespace App\Http\Controllers;

use App\GenericModel;
use Illuminate\Http\Request;

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
        $model = GenericModel::find($request->route('id'));

        if (!$model instanceof GenericModel) {
            return $this->jsonError(['Model not found.'], 404);
        }

        return $this->jsonSuccess($model);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->validateInputsForResource($request->all(), $request->route('resource'));

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
        $model = GenericModel::find($request->route('id'));

        if (!$model instanceof GenericModel) {
            return $this->jsonError(['Model not found.'], 404);
        }

        $this->validateInputsForResource($request->all(), $request->route('resource'));

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
        $model = GenericModel::find($request->route('id'));

        if (!$model instanceof GenericModel) {
            return $this->jsonError(['Model not found.'], 404);
        }

        if ($model->delete()) {
            return $this->jsonSuccess(['id' => $model->id]);
        }
        return $this->jsonError('Issue with deleting resource.');
    }
}
