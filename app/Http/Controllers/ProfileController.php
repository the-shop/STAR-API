<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Profile;
use Illuminate\Http\Request;
use App\Http\Requests;

/**
 * Class ProfileController
 * @package App\Http\Controllers
 */
class ProfileController extends Controller
{
    /**
     * Accepts email and password, returns authentication token on success and (bool) false on failed login
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(false, 403);
        }

        return response()->json(compact('token'));
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $user = Profile::all();
        return response()->json($user);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    //Store and Register
    public function store(Request $request)
    {
        // First let's validate the input
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'password' => 'required|min:8',
                'email' => 'required|email|unique:profiles'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all(), 400]);
        }

        // Create a new profile
        $user = Profile::create($request->all());

        $token = JWTAuth::fromUser($user);

        // Return newly created user
        return response()->json(compact('token'));
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = Profile::find($id);
        return response()->json($user);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */

    //Profile update for slack, trello and github user names
    public function update(Request $request, $id)
    {
        //Authenticate user
        $profile = Auth::user();
        //Input
        $profile->slack = $request->input('slack');
        $profile->trello = $request->input('trello');
        $profile->github = $request->input('github');

        //Validate slack, trello and github input fields
        $validator = Validator::make(
            $request->all(),
            [
                'slack' => 'alpha_dash',
                'trello' => 'alpha_dash',
                'github' => 'alpha_dash'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all(), 400]);
        }

        //Save profile changes
        $profile->save();
        //Return updated user
        return response()->json($profile);
    }

    /**
     * Change password implementation
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        //Authenticate user profile
        $profile = Auth::user();

        //Validation
        $validator = Validator::make(
            $request->all(),
            [
                'oldPassword' => 'required|min:8',
                'newPassword' => 'required|min:8',
                'repeatNewPassword' => 'required|min:8'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all(), 400]);
        }

        $oldPassword = $request->input('oldPassword');
        $newPassword = $request->input('newPassword');
        $repeatNewPassword = $request->input('repeatNewPassword');

        if ($newPassword != $repeatNewPassword) {
            return response()->json(['not the same password']);
        }

        if (Hash::check($oldPassword, $profile->password) === false) {
            return response()->json(['wrong password']);
        }

        //Save new password
        $profile->password = $newPassword;

        $token = JWTAuth::fromUser($profile);

        //Save profile updates
        $profile->save();

        //Return new token
        return response()->json(compact('token'));
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    //Deleting users from database
    public function destroy($id)
    {
        $profile = Profile::find($id);
        $profile->delete();
        return response()->json('success');

    }
}
