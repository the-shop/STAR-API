<?php

namespace App\Http\Controllers;

use App\Task;

use Illuminate\Http\Request;

use App\Http\Requests;

class TaskController extends Controller
{
    //TaskController uses TASK model
    public function index()
    {
        $task = Task::all();
        return response()->json($task);
    }

    //store and create
    public function store(Request $request)
    {
        $task = Task::create($request->all());
        return response()->json($task);
    }

    public function show($id)
    {
        $task = Task::find($id);
        response()->json($task);
    }

    public function update(Request $request)
    {
        $task = Task::find($id);
        $task->task = $request->input('task');
        $task->save();
        return response()->json($task);
    }

    //delete
    public function destroy($id)
    {
        $task = Task::find($id);
        $task->delete();
        return response()->json('success');
    }
}
