<?php

namespace App\Http\Controllers;

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
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // First let's validate the input
        try {
            $this->validate(
                $request,
                [
                    'name' => 'required',
                    'password' => 'required|min:8',
                    'email' => 'required|email|unique:profiles'
                ]
            );
        } catch (\Exception $e) {
            // TODO: Should be more specific, good enough for now
            // Return error(s)
            return response()->json(['error' => $e->getMessage()]);
        }

        // Create a new profile
        $user = Profile::create($request->all());

        $token = JWTAuth::fromUser($user);

        // Return newly created user
        return response()->json(compact('token'));
    }

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
    public function store(Request $request)
    {
        //
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
    public function update(Request $request, $id)
    {

        $user = Profile::find($id);
        $user->user = $request->input('user');
        $user->save();
        return response()->json($user);

    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = Profile::find($id);
        $user->delete();
        return response()->json('success');
    }
}
