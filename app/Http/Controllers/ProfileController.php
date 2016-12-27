<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Profile;
use Illuminate\Http\Request;
use App\Helpers\Configuration;

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
        $fields = $request->all();
        $this->validateInputsForResource($fields, 'profiles');

        $profile = Profile::create($fields);

        $credentials = $request->only('email', 'password');
        if (!$token = JWTAuth::attempt($credentials)) {
            return $this->jsonError(['Issue with automatic sign in.'], 401);
        }

        JWTAuth::setToken($token);

        //send confirmation E-mail upon profile creation on the platform

        $teamSlackInfo = Configuration::getConfiguration(true);
        if ($teamSlackInfo === false) {
            $teamSlackInfo = [];
        }

        $data = [
            'name' => $profile->name,
            'email' => $profile->email,
            'github' => $profile->github,
            'trello' => $profile->trello,
            'slack' => $profile->slack,
            'teamSlack' => $teamSlackInfo
        ];

        $emailFrom = \Config::get('mail.private_mail_from');
        $emailName = \Config::get('mail.private_mail_name');

        \Mail::send('emails.registration', $data, function ($message) use ($profile, $emailFrom, $emailName) {
            $message->from($emailFrom, $emailName);
            $message->to($profile->email, $profile->name)->subject($emailName . ' - Welcome to The Shop platform!');
        });

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
        if ($profile->id !== $this->getCurrentProfile()->id && $this->getCurrentProfile()->admin !== true) {
            return $this->jsonError('Not enough permissions.', 403);
        }

        $oldXp = $profile->xp;

        $fields = $request->all();
        $this->validateInputsForResource($fields, 'profiles', ['email' => 'required|email']);

        $profile->fill($fields);

        $profile->save();

        // Send email with XP status change
        if ($request->has('xp')) {
            $xpDifference = $profile->xp - $oldXp;
            $emailMessage = \Config::get('sharedSettings.internalConfiguration.email_xp_message');
            $data = [
                'xpDifference' => $xpDifference,
                'emailMessage' => $emailMessage
            ];
            $emailFrom = \Config::get('mail.private_mail_from');
            $emailName = \Config::get('mail.private_mail_name');

            \Mail::send('emails.xp', $data, function ($message) use ($profile, $emailFrom, $emailName) {
                $message->from($emailFrom, $emailName);
                $message->to($profile->email, $profile->name)->subject($emailName . ' - Xp status changed!');
            });
        }

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
        if ($profile->id !== $this->getCurrentProfile()->id && $this->getCurrentProfile()->admin !== true) {
            return $this->jsonError('Not enough permissions.', 403);
        }

        $profile->delete();
        return $this->jsonSuccess(['id' => $profile->id]);

    }
}
