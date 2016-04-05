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
        return response()->json($models);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $model = $this->loadModel($request->route('id'));
        return response()->json($model);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $model = GenericModel::create($request->all());
        if ($model->save()) {
            return response()->json($model);
        }
        return response()->json(['error' => true, 'message' => 'Issue with saving model.']);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $model = $this->loadModel($request->route('id'));
        $model->fill($request->all());
        if ($model->save()) {
            return response()->json($model);
        }

        return response()->json(['error' => true, 'message' => 'Issue with updating model.']);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        $model = $this->loadModel($request->route('id'));

        if ($model->delete()) {
            return response()->json(['success' => true, 'id' => $model->id]);
        }
        return response()->json(['success' => false, 'message' => 'Issue with deleting model.']);
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
            return response()->json(['success' => false, 'message' => 'Model not found.']);
        }

        return $model;
    }
}
