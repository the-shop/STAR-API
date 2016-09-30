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
            return $this->jsonError('Invalid credentials.', 401);
        }

        // Set the token
        JWTAuth::setToken($token);

        // Authenticated user
        $profile = Auth::user();

        return $this->jsonSuccess($profile);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $profiles = Profile::all();
        return $this->jsonSuccess($profiles);
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
            return $this->jsonError($validator->errors()->all(), 400);
        }

        // Create a new profile
        $profile = Profile::create($request->all());

        $credentials = $request->only('email', 'password');
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json('Issue with automatic sign in.', 401);
        }

        JWTAuth::setToken($token);

        // Return newly created user
        return $this->jsonSuccess($profile);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $profile = Profile::find($id);
        if (!$profile) {
            return $this->jsonError('User not found.', 404);
        }
        return $this->jsonSuccess($profile);
    }

    /**
     * Profile update for slack, trello and github user names
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        //Authenticate user
        $profile = Profile::find($id);

        //Input
        $profile->slack = $request->input('slack');
        $profile->trello = $request->input('trello');
        $profile->github = $request->input('github');
        $profile->xp_id = $request->input('xp_id');

        //Validate slack, trello and github input fields
        $validator = Validator::make(
            $request->all(),
            [
                'slack' => 'alpha_dash',
                'trello' => 'alpha_dash',
                'github' => 'alpha_dash',
                'xp_id' => 'alpha_num',
            ]
        );

        if ($validator->fails()) {
            return $this->jsonError($validator->errors()->all(), 400);
        }

        //Save profile changes
        $profile->save();

        //Return updated user
        return $this->jsonSuccess($profile);
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
            return $this->jsonError($validator->errors()->all(), 400);
        }

        $oldPassword = $request->input('oldPassword');
        $newPassword = $request->input('newPassword');
        $repeatNewPassword = $request->input('repeatNewPassword');

        if ($newPassword != $repeatNewPassword) {
            return $this->jsonError('not the same password');
        }

        if (Hash::check($oldPassword, $profile->password) === false) {
            return $this->jsonError('wrong password');
        }

        //Save new password
        $profile->password = $newPassword;

        //Save profile updates
        $profile->save();

        //Return new token
        return $this->jsonSuccess($profile);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    //Deleting users from database
    public function destroy($id)
    {
        $profile = Profile::find($id);

        if (!$profile) {
            return $this->jsonError('User not found.', 404);
        }

        $profile->delete();
        return $this->jsonSuccess(['id' => $profile->id]);

    }
}
