<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Profile;
use Illuminate\Http\Request;

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

        JWTAuth::setToken($token);

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
    public function store(Request $request)
    {
        $validator = $this->createProfileInputValidator($request);

        if ($validator->fails()) {
            return $this->jsonError($validator->errors()->all(), 400);
        }

        $profile = Profile::create($request->all());

        $credentials = $request->only('email', 'password');
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json('Issue with automatic sign in.', 401);
        }

        JWTAuth::setToken($token);

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
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $profile = Profile::find($id);

        if (!$profile instanceof Profile) {
            return $this->jsonError('Model not found.', 404);
        }

        // TODO: replace with Gate after ACL is implemented
        if ($profile->id !== $this->getCurrentProfile()->id) {
            return $this->jsonError('Not enough permissions.', 403);
        }

        $this->validateInputsForResource($request->all(), 'profiles');

        $profile->fill($request->all());

        $profile->save();

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
        $profile = $this->getCurrentProfile();

        if (!$profile instanceof Profile) {
            return $this->jsonError('User not found.', 404);
        }

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
            return $this->jsonError(['Passwords mismatch']);
        }

        if (Hash::check($oldPassword, $profile->password) === false) {
            return $this->jsonError(['Invalid old password']);
        }

        $profile->password = $newPassword;

        $profile->save();

        return $this->jsonSuccess($profile);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $profile = Profile::find($id);

        if (!$profile instanceof Profile) {
            return $this->jsonError('User not found.', 404);
        }

        // TODO: replace with Gate after ACL is implemented
        if ($profile->id !== $this->getCurrentProfile()->id) {
            return $this->jsonError('Not enough permissions.', 403);
        }

        $profile->delete();
        return $this->jsonSuccess(['id' => $profile->id]);

    }

    /**
     * Helper method to validate mandatory profile fields
     *
     * @param Request $request
     * @return Validator
     */
    protected function createProfileInputValidator(Request $request)
    {
        $messages = [
            'regex' => 'Name has to have at least 2 words.',
        ];

        return Validator::make(
            $request->all(),
            [
                'name' => 'required|regex:/\w+ \w+/',
                'password' => 'required|min:8',
                'email' => 'required|email|unique:profiles',
                'slack' => 'alpha_dash',
                'trello' => 'alpha_dash',
                'github' => 'alpha_dash',
                'xp' => 'alpha_num',
                'xp_id' => 'alpha_num',
            ],
            $messages
        );
    }
}
