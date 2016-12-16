<?php

namespace App\Http\Controllers;

use App\Exceptions\DynamicValidationException;
use App\Profile;
use App\Validation;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Class Controller
 * @package App\Http\Controllers
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param $data
     * @param array $headers
     * @return \Illuminate\Http\JsonResponse
     */
    protected function jsonSuccess($data, array $headers = [])
    {
        $headers = $this->appendAuthHeaders($headers);

        return response()->json($data, 200, $headers);
    }

    /**
     * @param $errors
     * @param int $statusCode
     * @param array $headers
     * @return \Illuminate\Http\JsonResponse
     */
    protected function jsonError($errors, $statusCode = 400, array $headers = [])
    {
        $response = [];
        if (!is_array($errors)) {
            $errors = [$errors];
        }

        $response['error'] = true;
        $response['errors'] = $errors;

        $headers = $this->appendAuthHeaders($headers);

        return response()->json($response, $statusCode, $headers);
    }

    /**
     * @param array $headers
     * @return array
     */
    private function appendAuthHeaders(array $headers)
    {
        //Authenticate user
        $token = JWTAuth::getToken();
        if ($token) {
            $headers = array_merge(
                $headers,
                [
                    'Authorization' => 'bearer ' . $token
                ]
            );
        }

        return $headers;
    }

    /**
     * @param $fields
     * @param $resourceName
     * @param array $inputOverrides
     * @return bool
     * @throws DynamicValidationException
     */
    protected function validateInputsForResource($fields, $resourceName, array $inputOverrides = [])
    {
        $validationModel = Validation::where('resource', $resourceName)
            ->first();

        if (!$validationModel instanceof Validation) {
            return false;
        }

        $validations = $validationModel->getFields();

        foreach ($inputOverrides as $field => $value) {
            $validations[$field] = $value;
        }

        $checkValidations = [];

        foreach ($validations as $field => $value) {
            if (!isset($fields[$field])) {
                continue;
            }

            $checkValidations[$field] = $value;
        }

        $validator = Validator::make(
            $fields,
            $checkValidations,
            $validationModel->getMessages()
        );

        if ($validator->fails()) {
            throw new DynamicValidationException($validator->errors()->all(), 400);
        }

        return true;
    }

    /**
     * @return Profile|null
     */
    protected function getCurrentProfile()
    {
        try {
            $profile = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            $profile = null;
        }

        return $profile;
    }
}
