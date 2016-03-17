<?php

namespace App\Http\Controllers;

use App\Profile;
use App\Users;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;

use App\Http\Requests;

class ProfileController extends Controller
{


    //UserController uses USERS model
    public function index(){
        $user = Profile::all();
        return response()->json($user);
    }
    //store and create
    public function store(Request $request){
        $user = Profile::create($request->all());
        return response()->json($user);

    }

    public function show($id){
        $user = Profile::find($id);
        return response()->json($user);

    }

    public function update(Request $request,  $id){

        $user = Profile::find($id);
        $user->user =$request->input('user');
        $user->save();
        return response()->json($user);

    }
    //delete
    public function destroy($id){
        $user = Profile::find($id);
        $user->delete();
        return response()->json('success');


    }

}
