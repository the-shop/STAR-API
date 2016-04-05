<?php

namespace App\Http\Controllers;

use App\GenericModel;
use Illuminate\Http\Request;

use App\Http\Requests;

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
        $model = GenericModel::find($request->route('id'));
        return response()->json($model);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $model = GenericModel::create($request->all());
        $model->save();
        return response()->json($model);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $model = GenericModel::find($request->route('id'));
        $model->fill($request->all());
        $model->save();

        return response()->json($model);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        $model = GenericModel::find($request->route('id'));
        if ($model->delete()) {
            return response()->json(['success' => true, 'id' => $model->id]);
        }
        return response()->json(['success' => false]);
    }
}
