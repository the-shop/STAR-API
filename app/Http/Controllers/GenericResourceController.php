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

        $fields = $request->all();
        if ($this->validateInputsForResource($fields, $request->route('resource')) === false) {
            return $this->jsonError(['Insufficient permissions.'], 403);
        }

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
        $fields = $request->all();
        if ($this->validateInputsForResource($fields, $request->route('resource')) === false) {
            return $this->jsonError(['Insufficient permissions.'], 403);
        //}

        $model = GenericModel::create($fields);
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

        $updateFields = $request->all();

        if ($this->validateInputsForResource($updateFields, $request->route('resource')) === false) {
            return $this->jsonError(['Insufficient permissions.'], 403);
        }

        $model->fill($updateFields);
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

        $fields = $request->all();
        if ($this->validateInputsForResource($fields, $request->route('resource')) === false) {
            return $this->jsonError(['Insufficient permissions.'], 403);
        }

        if ($model->delete()) {
            return $this->jsonSuccess(['id' => $model->id]);
        }
        return $this->jsonError('Issue with deleting resource.');
    }
}
