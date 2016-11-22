<?php

namespace App\Http\Controllers;

use App\Validation;
use Illuminate\Http\Request;

/**
 * Class ValidationController
 * @package App\Http\Controllers
 */
class ValidationController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $models = Validation::all();
        return $this->jsonSuccess($models);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $model = Validation::create();

        $model->fields = json_decode($request->get('fields'), true);
        $model->resource = $request->get('resource');

        $model->save();

        return $this->jsonSuccess($model);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $model = Validation::find($id);
        if (!$model) {
            return $this->jsonError('Model not found.', 404);
        }
        return $this->jsonSuccess($model);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $model = Validation::find($id);

        if (!$model) {
            return $this->jsonError('Model not found.', 404);
        }

        $model->fields = json_decode($request->get('fields'), true);
        $model->resource = $request->get('resource');

        $model->save();

        return $this->jsonSuccess($model);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $model = Validation::find($id);

        if (!$model) {
            return $this->jsonError('Model not found.', 404);
        }

        $model->delete();
        return $this->jsonSuccess(['id' => $model->id]);
    }
}
